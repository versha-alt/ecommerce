<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Commerce;
use Illuminate\Http\Request;

class OrderEventController extends Controller
{
    public function __invoke(Request $request, Commerce $commerce): array
    {
        $secret = config('commerce.order_events_secret');
        abort_unless(is_string($secret) && strlen($secret) >= 32, 503, 'Order event integration has not been configured.');
        $timestamp = $request->header('X-Order-Timestamp', '');
        abort_unless(ctype_digit($timestamp) && abs(time() - (int) $timestamp) <= 300, 401, 'Order event timestamp is invalid.');
        $expected = hash_hmac('sha256', $timestamp.'.'.$request->getContent(), $secret);
        abort_unless(hash_equals($expected, $request->header('X-Order-Signature', '')), 401, 'Order event signature is invalid.');
        $input = $request->validate(['event_id' => 'required|string|max:100', 'order_id' => 'required|uuid', 'version' => 'required|integer|min:1', 'stage' => 'required|in:Placed,Processing,Shipping,Delivered,Cancelled', 'evidence' => 'required|string|max:10000']);

        return $commerce->idempotent('order-event:'.$input['event_id'], $input, function () use ($input, $commerce) {
            $order = $commerce->find('orders', $input['order_id'], true);
            abort_if($order->version !== $input['version'], 409, 'Order changed; reload its current version before retrying the lifecycle event.');
            $target = ['Placed' => 'Pending', 'Processing' => 'Confirmed', 'Shipping' => 'Dispatched', 'Delivered' => 'Delivered', 'Cancelled' => 'Cancelled'][$input['stage']];
            if ($target === $order->data['status']) {
                return $order->row();
            }
            $action = ['Confirmed' => 'confirm', 'Dispatched' => 'dispatch', 'Delivered' => 'deliver', 'Cancelled' => 'cancel'][$target] ?? null;
            abort_unless($action, 422, 'An existing order cannot be placed again.');
            $actor = new User(['name' => 'Storefront lifecycle', 'email' => 'storefront-system@olive.local', 'role' => 'Admin']);

            return $commerce->orderAction($order->id, ['version' => $order->version, 'action' => $action, 'evidence' => $input['evidence']], 'lifecycle:'.$input['event_id'], $actor);
        });
    }
}
