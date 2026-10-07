<?php

namespace App\Http\Controllers;

use App\Models\CommerceRecord as Record;
use App\Services\Commerce;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProductReviewController extends Controller
{
    public function submit(Request $request, Commerce $commerce): array
    {
        $secret = config('commerce.review_events_secret');
        abort_unless(is_string($secret) && strlen($secret) >= 32, 503, 'Review submission integration has not been configured.');
        $timestamp = $request->header('X-Review-Timestamp', '');
        abort_unless(ctype_digit($timestamp) && abs(time() - (int) $timestamp) <= 300, 401, 'Review submission timestamp is invalid.');
        $signature = hash_hmac('sha256', $timestamp.'.'.$request->getContent(), $secret);
        abort_unless(hash_equals($signature, $request->header('X-Review-Signature', '')), 401, 'Review submission signature is invalid.');
        $input = $request->validate(['submission_id' => 'required|uuid', 'product_id' => 'required|uuid', 'customer_id' => 'required|uuid', 'rating' => 'required|integer|min:1|max:5', 'title' => 'nullable|string|max:180', 'body' => 'required|string|max:5000', 'photos' => 'sometimes|array|max:3', 'photos.*' => 'required|string|max:3000000', 'version' => 'nullable|integer|min:1']);

        return $this->create($input, $commerce);
    }

    public function eligibility(string $customer, string $product): ?Record
    {
        return Record::where('resource', 'orders')->where('data->customer_id', $customer)
            ->whereIn('data->status', ['Delivered', 'Completed'])->get()
            ->first(fn (Record $order) => collect($order->data['lines'] ?? [])->contains(fn ($line) => ($line['product_id'] ?? '') === $product && ($line['quantity'] ?? 0) > 0));
    }

    public function create(array $input, Commerce $commerce): array
    {
        $product = $commerce->active('products', $input['product_id']);
        $customer = $commerce->active('customers', $input['customer_id']);
        abort_unless($this->eligibility($customer->id, $product->id), 403, 'Reviews require a delivered or completed purchase of this product.');

        return $commerce->idempotent('review-submission:'.$input['submission_id'], $input, function () use ($input, $product, $customer) {
            Record::whereKey($customer->id)->lockForUpdate()->firstOrFail();
            $order = $this->eligibility($customer->id, $product->id);
            abort_unless($order, 403, 'Reviews require a delivered or completed purchase of this product.');
            $review = Record::where('resource', 'reviews')->where('data->customer_id', $customer->id)->where('data->product_id', $product->id)->lockForUpdate()->first();
            abort_if($review && ($input['version'] ?? null) !== $review->version, 409, 'You already reviewed this product. Reload your review before editing it.');
            $photos = $review->data['photos'] ?? [];
            if (array_key_exists('photos', $input)) {
                $photos = [];
                foreach ($input['photos'] as $photo) {
                    if (in_array($photo, $review->data['photos'] ?? [], true)) {
                        $photos[] = $photo;

                        continue;
                    }
                    if (! preg_match('/^data:image\/(jpeg|png|webp);base64,(.+)$/s', $photo, $match)) {
                        throw ValidationException::withMessages(['photos' => 'Use JPG, PNG or WebP photos.']);
                    }
                    $bytes = base64_decode($match[2], true);
                    $size = $bytes ? @getimagesizefromstring($bytes) : false;
                    if (! $size || strlen($bytes) > 2097152 || $size[0] * $size[1] > 16000000 || ! in_array($size['mime'], ['image/jpeg', 'image/png', 'image/webp'], true)) {
                        throw ValidationException::withMessages(['photos' => 'Each photo must be a valid image under 2 MB and 16 megapixels.']);
                    }
                    $image = imagecreatefromstring($bytes);
                    ob_start();
                    imagewebp($image, null, 82);
                    $output = ob_get_clean();
                    imagedestroy($image);
                    $name = Str::uuid().'.webp';
                    Storage::disk('local')->put('uploads/'.$name, $output);
                    $photos[] = '/api/v1/media/'.$name;
                }
            }
            $history = $review->data['status_history'] ?? [];
            $history[] = ['from' => $review->data['status'] ?? null, 'to' => 'Pending', 'actor' => 'Customer submission', 'at' => now()->toISOString()];
            $data = array_merge($review->data ?? [], ['name' => $input['title'] ?? 'Product review', 'title' => $input['title'] ?? '', 'body' => $input['body'], 'rating' => (int) $input['rating'], 'product_id' => $product->id, 'product_name' => $product->data['name'], 'customer_id' => $customer->id, 'customer_name' => $customer->data['name'], 'status' => 'Pending', 'notes' => $review->data['notes'] ?? '', 'status_history' => $history, 'photos' => $photos, 'verified_purchase' => true, 'order_id' => $order->id]);
            if ($review) {
                $review->data = $data;
                $review->version++;
                $review->save();
            } else {
                $review = Record::create(['resource' => 'reviews', 'data' => $data]);
            }

            return ['id' => $review->id, 'status' => 'Pending', 'message' => 'Your review has been submitted for moderation.'];
        });
    }

    public function published(Request $request, string $product): array
    {
        $record = app(Commerce::class)->find('products', $product);
        abort_unless(($record->data['status'] ?? '') === 'Active', 404);
        $request->validate(['page' => 'nullable|integer|min:1']);
        $reviews = Record::where('resource', 'reviews')->where('data->product_id', $product)->where('data->status', 'Approved')->latest()->paginate(20);

        return ['data' => $reviews->getCollection()->map(fn (Record $review) => ['id' => $review->id, 'rating' => $review->data['rating'], 'title' => $review->data['title'], 'body' => $review->data['body'], 'customer_name' => $review->data['customer_name'], 'created_at' => $review->created_at?->toISOString(), 'verified_purchase' => (bool) ($review->data['verified_purchase'] ?? false), 'photos' => $review->data['photos'] ?? []])->all(), 'current_page' => $reviews->currentPage(), 'last_page' => $reviews->lastPage(), 'total' => $reviews->total()];
    }
}
