<?php

namespace App\Http\Controllers;

use App\Models\CommerceRecord as Record;
use App\Models\User;
use App\Services\Commerce;
use App\Services\CustomerPayments;
use App\Services\InventoryImport;
use App\Services\ProductImport;
use App\Services\StoreEmails;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminController extends Controller
{
    public function __construct(private Commerce $commerce) {}

    public function login(Request $r)
    {
        $r->validate(['email' => 'required|email', 'password' => 'required|string']);
        $u = User::where('email', strtolower($r->email))->first();
        abort_unless($u && $u->status === 'Active' && Hash::check($r->password, $u->password), 401, 'Email or password is incorrect.');
        $token = Str::random(64);
        DB::table('admin_tokens')->where('expires_at', '<', now())->delete();
        DB::table('admin_tokens')->insert(['user_id' => $u->id, 'token' => hash('sha256', $token), 'expires_at' => now()->addHours(8)]);

        return ['token' => $token, 'user' => $u->toArray()];
    }

    public function logout(Request $r)
    {
        DB::table('admin_tokens')->where('token', hash('sha256', $r->bearerToken()))->delete();

        return ['ok' => true];
    }

    public function workspace(Request $r)
    {
        $permissions = $this->commerce->permissions($r->user());
        $records = [];
        foreach (Commerce::RESOURCES as $resource) {
            if ($resource !== 'settings' && (in_array($resource, $permissions) || ($resource === 'products' && in_array('inventory', $permissions)))) {
                $records[$resource] = $this->commerce->rows($resource, true);
            }
        }
        if (in_array('orders', $permissions)) {
            $records['delivery-zones'] = $this->commerce->rows('delivery-zones', true);
        }
        if (in_array('users', $permissions)) {
            $records['users'] = User::orderByDesc('id')->get()->toArray();
        }
        // Inventory and products share one source of stock truth.
        foreach ($records['products'] ?? [] as $i => $p) {
            $records['products'][$i]['brand_name'] = collect($records['brands'] ?? [])->firstWhere('id', $p['brand_id'] ?? null)['name'] ?? '';
        }
        $activity = $r->user()->role === 'Admin' ? DB::table('audit_events')->orderByDesc('created_at')->limit(100)->get()->map(fn ($e) => array_merge((array) $e, ['before' => $e->before ? json_decode($e->before, true) : null, 'after' => $e->after ? json_decode($e->after, true) : null]))->all() : [];
        $settings = in_array('settings', $permissions) ? $this->commerce->settings() : ['store_name' => $this->commerce->settings()['store_name'] ?? 'Olive Electronics'];

        return response()->json(['shipping_locations' => config('shipping'), 'records' => $records, 'user' => $r->user()->toArray(), 'permissions' => $permissions, 'settings' => $settings, 'activity' => $activity, 'demo' => config('commerce.demo')])->header('Cache-Control', 'no-store, private');
    }

    public function save(Request $r, string $resource, ?string $id = null)
    {
        $this->commerce->authorize($r->user(), $resource, true);

        return $resource === 'users' ? $this->commerce->saveUser($r->all(), $r->user(), $id) : $this->commerce->save($resource, $r->all(), $r->user(), $id);
    }

    public function retire(Request $r, string $resource, string $id)
    {
        $this->commerce->authorize($r->user(), $resource, true);
        if ($resource === 'users') {
            $u = User::findOrFail($id);

            return $this->commerce->saveUser(array_merge($u->toArray(), ['status' => 'Retired', 'password' => '', 'version' => $r->input('version')]), $r->user(), $id);
        }

        return $this->commerce->retire($resource, $id, (int) $r->input('version'), $r->user());
    }

    public function inventory(Request $r)
    {
        $this->commerce->authorize($r->user(), 'inventory', true);

        return $this->commerce->inventory($r->all(), $r->user());
    }

    public function order(Request $r)
    {
        $this->commerce->authorize($r->user(), 'orders', true);
        abort(405, 'Orders are created by customers through the storefront.');
    }

    public function orderAction(Request $r, string $id)
    {
        $this->commerce->authorize($r->user(), 'orders', true);

        return $this->commerce->orderAction($id, $r->all(), $r->header('Idempotency-Key', ''), $r->user());
    }

    public function orderStatuses(Request $request, string $id): array
    {
        abort_unless($request->user()->role === 'Admin', 403, 'Only administrators can override order and payment statuses.');

        return $this->commerce->updateOrderStatuses($id, $request->all(), $request->header('Idempotency-Key', ''), $request->user());
    }

    public function testEmail(Request $request): JsonResponse
    {
        $this->commerce->authorize($request->user(), 'settings', true);
        $input = $request->validate(['recipient' => 'required|email|max:180']);
        $delivery = app(StoreEmails::class)->test($input['recipient']);
        $this->commerce->audit($request->user(), 'Test email '.$delivery['status'], 'emails', null, ['recipient' => $input['recipient'], 'delivery_id' => $delivery['id'], 'status' => $delivery['status']]);

        return response()->json($delivery + ['message' => $delivery['status'] === 'Sent' ? 'Test email accepted by the SMTP server. Check the inbox and spam folder.' : $delivery['error']], $delivery['status'] === 'Sent' ? 200 : 422);
    }

    public function settings(Request $r)
    {
        $this->commerce->authorize($r->user(), 'settings', true);

        return $this->commerce->saveSettings($r->all(), $r->user());
    }

    public function importProducts(Request $request): JsonResponse
    {
        $this->commerce->authorize($request->user(), 'products', true);
        $request->validate(['file' => 'required|file|mimes:csv,txt|max:2048', 'commit' => 'required|boolean']);
        abort_unless(strtolower($request->file('file')->getClientOriginalExtension()) === 'csv', 422, 'Select a .csv file.');
        $commit = $request->boolean('commit');
        $key = $request->header('Idempotency-Key', '');
        if ($commit && ! $key) {
            $this->commerce->fail('An idempotency key is required for import.');
        }
        $report = app(ProductImport::class)->run($request->file('file'), $commit, $key, $request->user());

        return response()->json($report + ['message' => $report['can_import'] ? 'Products validated successfully.' : 'Fix every invalid row before importing.'], $commit && ! $report['can_import'] ? 422 : 200);
    }

    public function importInventory(Request $request): JsonResponse
    {
        $this->commerce->authorize($request->user(), 'inventory', true);
        $request->validate(['file' => 'required|file|mimes:csv,txt|max:2048', 'commit' => 'required|boolean', 'versions' => 'nullable|json']);
        abort_unless(strtolower($request->file('file')->getClientOriginalExtension()) === 'csv', 422, 'Select a .csv file.');
        $versions = json_decode($request->input('versions', '{}'), true);
        abort_unless(is_array($versions), 422, 'Preview the file before importing.');
        $report = app(InventoryImport::class)->run($request->file('file'), $request->boolean('commit'), $request->header('Idempotency-Key', ''), $request->user(), $versions);

        return response()->json($report, $request->boolean('commit') && ! $report['can_import'] ? 422 : 200);
    }

    public function upload(Request $r)
    {
        abort_unless($r->user()->role === 'Admin' || $r->user()->role === 'Store Manager', 403, 'Your role cannot upload files.');
        $r->validate(['file' => 'required|file|mimes:jpg,jpeg,png,webp,pdf|max:5120']);
        $file = $r->file('file');
        $name = Str::uuid().'.'.$file->extension();
        if (str_starts_with($file->getMimeType(), 'image/')) {
            $size = getimagesize($file->getRealPath());
            if (! $size || $size[0] * $size[1] > 40000000) {
                abort(422, 'Image dimensions are too large.');
            }
            $source = imagecreatefromstring(file_get_contents($file->getRealPath()));
            $ratio = min(1, 1600 / max($size[0], $size[1]));
            $width = max(1, (int) round($size[0] * $ratio));
            $height = max(1, (int) round($size[1] * $ratio));
            $image = imagecreatetruecolor($width, $height);
            imagealphablending($image, false);
            imagesavealpha($image, true);
            imagecopyresampled($image, $source, 0, 0, 0, 0, $width, $height, $size[0], $size[1]);
            $name = Str::uuid().'.webp';
            Storage::disk('local')->makeDirectory('uploads');
            imagewebp($image, Storage::disk('local')->path('uploads/'.$name), 82);
            imagedestroy($image);
            imagedestroy($source);
        } else {
            $file->storeAs('uploads', $name, 'local');
        }

        return ['url' => '/api/v1/media/'.$name];
    }

    public function media(string $file)
    {
        abort_unless(preg_match('/^[a-f0-9-]+\.(jpg|jpeg|png|webp|pdf)$/', $file), 404);
        $path = Storage::disk('local')->path('uploads/'.$file);
        abort_unless(file_exists($path), 404);

        return response()->file($path, ['X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'public, max-age=86400']);
    }

    public function refund(Request $r, string $id)
    {
        abort_unless($r->user()->role === 'Admin', 403, 'Refund recording requires Admin approval.');
        $r->validate(['version' => 'required|integer', 'evidence' => 'required|string|max:200']);

        return DB::transaction(function () use ($r, $id) {
            Record::where('resource', 'settings')->lockForUpdate()->first();
            $return = $this->commerce->find('returns', $id, true);
            abort_if($return->version !== $r->input('version'), 409, 'Return changed. Reload before continuing.');
            $before = $return->row();
            $d = $return->data;
            if ($d['status'] !== 'Received') {
                $this->commerce->fail('Inspect and receive the returned item before confirming a refund.');
            }if (($d['refund_status'] ?? '') === 'Refunded') {
                $this->commerce->fail('This refund is already recorded.');
            }foreach ($this->commerce->rows('returns') as $item) {
                if (($item['refund_reference'] ?? '') === $r->input('evidence')) {
                    $this->commerce->fail('Refund reference already exists.');
                }
            }$order = $this->commerce->find('orders', $d['order_id'], true);
            $d['refund_status'] = 'Refunded';
            $d['refund_reference'] = $r->input('evidence');
            $d['refund_recorded_at'] = now()->toISOString();
            $return->data = $d;
            $return->version++;
            $return->save();
            $this->commerce->audit($r->user(), 'Verified refund recorded', 'returns', $before, $return->row());
            $total = collect($this->commerce->rows('returns'))->where('order_id', $order->id)->where('refund_status', 'Refunded')->sum('refund_amount');
            app(CustomerPayments::class)->synchronizeRefunds($order, (float) $total);
            $ob = $order->row();
            $order->data = array_merge($order->data, ['payment_status' => $total >= $order->data['total'] ? 'Refunded' : 'Partially refunded']);
            $order->version++;
            $order->save();
            $this->commerce->audit($r->user(), 'Refund status updated', 'orders', $ob, $order->row());

            return $return->row();
        });
    }
}
