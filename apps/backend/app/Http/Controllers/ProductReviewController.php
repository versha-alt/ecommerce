<?php

namespace App\Http\Controllers;

use App\Models\CommerceRecord as Record;
use App\Services\Commerce;
use Illuminate\Http\Request;

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
        $input = $request->validate(['submission_id' => 'required|uuid', 'product_id' => 'required|uuid', 'customer_id' => 'required|uuid', 'rating' => 'required|integer|min:1|max:5', 'title' => 'nullable|string|max:180', 'body' => 'required|string|max:5000']);

        return $this->create($input, $commerce);
    }

    public function create(array $input, Commerce $commerce): array
    {
        return $commerce->idempotent('review-submission:'.$input['submission_id'], $input, function () use ($input, $commerce) {
            $product = $commerce->active('products', $input['product_id']);
            $customer = $commerce->active('customers', $input['customer_id']);
            $review = Record::create(['resource' => 'reviews', 'data' => ['name' => $input['title'] ?? 'Product review', 'title' => $input['title'] ?? '', 'body' => $input['body'], 'rating' => (int) $input['rating'], 'product_id' => $product->id, 'product_name' => $product->data['name'], 'customer_id' => $customer->id, 'customer_name' => $customer->data['name'], 'status' => 'Pending', 'notes' => '', 'status_history' => [['from' => null, 'to' => 'Pending', 'actor' => 'Customer submission', 'at' => now()->toISOString()]]]]);

            return ['id' => $review->id, 'status' => 'Pending', 'message' => 'Your review has been submitted for moderation.'];
        });
    }

    public function published(Request $request, string $product): array
    {
        $record = app(Commerce::class)->find('products', $product);
        abort_unless(($record->data['status'] ?? '') === 'Active', 404);
        $request->validate(['page' => 'nullable|integer|min:1']);
        $reviews = Record::where('resource', 'reviews')->where('data->product_id', $product)->where('data->status', 'Approved')->latest()->paginate(20);

        return ['data' => $reviews->getCollection()->map(fn (Record $review) => ['id' => $review->id, 'rating' => $review->data['rating'], 'title' => $review->data['title'], 'body' => $review->data['body'], 'customer_name' => $review->data['customer_name'], 'created_at' => $review->created_at?->toISOString()])->all(), 'current_page' => $reviews->currentPage(), 'last_page' => $reviews->lastPage(), 'total' => $reviews->total()];
    }
}
