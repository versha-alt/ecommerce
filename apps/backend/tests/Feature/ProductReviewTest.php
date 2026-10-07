<?php

namespace Tests\Feature;

use App\Models\CommerceRecord as Record;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class ProductReviewTest extends TestCase
{
    use RefreshDatabase;

    private function fixtures(): array
    {
        Record::create(['resource' => 'settings', 'data' => ['store_name' => 'Store']]);
        $product = Record::create(['resource' => 'products', 'data' => ['name' => 'Appliance', 'slug' => 'review-appliance', 'status' => 'Active']]);
        $customer = Record::create(['resource' => 'customers', 'data' => ['name' => 'Buyer', 'email' => 'buyer@example.com', 'status' => 'Active']]);

        Record::create(['resource' => 'orders', 'data' => ['customer_id' => $customer->id, 'status' => 'Delivered', 'lines' => [['product_id' => $product->id, 'quantity' => 1]]]]);

        return [$product, $customer];
    }

    private function signIn(string $role = 'Admin'): void
    {
        $user = User::create(['name' => 'Reviewer', 'email' => 'moderator@example.com', 'password' => Hash::make('ReviewsTest!2026'), 'role' => $role, 'status' => 'Active']);
        $token = $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'ReviewsTest!2026'])->assertOk()->json('token');
        $this->withToken($token);
    }

    private function submit(array $input, bool $signed = true): TestResponse
    {
        config(['commerce.review_events_secret' => str_repeat('r', 40)]);
        $body = json_encode($input);
        $timestamp = (string) time();

        return $this->call('POST', '/api/v1/review-submissions', [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json', 'HTTP_X_REVIEW_TIMESTAMP' => $timestamp, 'HTTP_X_REVIEW_SIGNATURE' => $signed ? hash_hmac('sha256', $timestamp.'.'.$body, str_repeat('r', 40)) : 'invalid'], $body);
    }

    private function input(Record $product, Record $customer): array
    {
        return ['submission_id' => (string) Str::uuid(), 'product_id' => $product->id, 'customer_id' => $customer->id, 'rating' => 5, 'title' => 'Great appliance', 'body' => 'Works well.'];
    }

    public function test_customer_submission_is_pending_idempotent_and_admin_can_approve_and_reject_publication(): void
    {
        [$product, $customer] = $this->fixtures();
        $input = $this->input($product, $customer) + ['status' => 'Approved'];
        $reviewId = $this->submit($input)->assertOk()->assertJsonPath('status', 'Pending')->json('id');
        $this->submit($input)->assertOk()->assertJsonPath('id', $reviewId);
        $this->assertSame(1, Record::where('resource', 'reviews')->count());
        $url = '/api/v1/products/'.$product->id.'/reviews';
        $this->getJson($url)->assertOk()->assertJsonCount(0, 'data');
        $this->signIn();
        $this->getJson('/api/v1/workspace')->assertOk()->assertJsonPath('records.reviews.0.status', 'Pending');
        $this->patchJson('/api/v1/reviews/'.$reviewId, ['version' => 1, 'status' => 'Approved', 'notes' => 'Private moderation note'])->assertOk();
        $this->getJson($url)->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.rating', 5)->assertJsonMissingPath('data.0.notes')->assertJsonMissingPath('data.0.customer_id');
        $this->patchJson('/api/v1/reviews/'.$reviewId, ['version' => 1, 'status' => 'Rejected'])->assertConflict();
        $this->patchJson('/api/v1/reviews/'.$reviewId, ['version' => 2, 'status' => 'Rejected', 'notes' => 'Rejected after review'])->assertOk();
        $this->getJson($url)->assertOk()->assertJsonCount(0, 'data');
        $this->assertCount(3, Record::findOrFail($reviewId)->data['status_history']);
    }

    public function test_submission_requires_trusted_signature_and_valid_rating(): void
    {
        [$product, $customer] = $this->fixtures();
        $input = $this->input($product, $customer);
        $this->submit($input, false)->assertUnauthorized();
        $this->submit(array_merge($input, ['rating' => 6]))->assertUnprocessable()->assertJsonValidationErrors('rating');
        $this->assertSame(0, Record::where('resource', 'reviews')->count());
        $customer->data = array_merge($customer->data, ['status' => 'Inactive']);
        $customer->save();
        $this->submit($input)->assertUnprocessable();
    }

    public function test_admin_cannot_create_reviews_or_rewrite_customer_content(): void
    {
        [$product, $customer] = $this->fixtures();
        $id = $this->submit($this->input($product, $customer))->assertOk()->json('id');
        $this->signIn();
        $this->postJson('/api/v1/reviews', [])->assertStatus(405);
        $this->patchJson('/api/v1/reviews/'.$id, ['version' => 1, 'status' => 'Approved', 'rating' => 1])->assertUnprocessable();
        $this->assertSame(5, Record::findOrFail($id)->data['rating']);
        $this->postJson('/api/v1/reviews/'.$id.'/retire', ['version' => 1])->assertStatus(405);
    }

    public function test_purchase_required_and_existing_review_can_be_edited(): void
    {
        [$product, $customer] = $this->fixtures();
        $order = Record::where('resource', 'orders')->firstOrFail();
        $order->data = array_merge($order->data, ['status' => 'Dispatched']);
        $order->save();
        $input = $this->input($product, $customer);
        $this->submit($input)->assertForbidden();
        $order->data = array_merge($order->data, ['status' => 'Completed']);
        $order->save();
        $id = $this->submit($input)->assertOk()->json('id');
        $this->submit($this->input($product, $customer))->assertConflict();
        $review = Record::findOrFail($id);
        $review->data = array_merge($review->data, ['status' => 'Approved']);
        $review->save();
        $this->getJson('/api/v1/products/'.$product->id.'/reviews')->assertJsonPath('data.0.verified_purchase', true);
        $this->submit(array_merge($this->input($product, $customer), ['version' => 1, 'rating' => 3, 'body' => 'Edited review', 'photos' => []]))->assertOk()->assertJsonPath('id', $id);
        $this->assertSame(1, Record::where('resource', 'reviews')->count());
        $this->assertSame('Pending', $review->fresh()->data['status']);
        $this->assertSame(3, $review->fresh()->data['rating']);
        $this->getJson('/api/v1/products/'.$product->id.'/reviews')->assertJsonCount(0, 'data');
        $this->submit(array_merge($this->input($product, $customer), ['version' => 2, 'photos' => ['https://example.com/photo.jpg']]))->assertUnprocessable();
        $other = Record::create(['resource' => 'customers', 'data' => ['name' => 'Other', 'status' => 'Active']]);
        $this->submit($this->input($product, $other))->assertForbidden();
    }

    public function test_photos_and_database_uniqueness(): void
    {
        Storage::fake('local');
        [$product, $customer] = $this->fixtures();
        $image = imagecreatetruecolor(10, 10);
        ob_start();
        imagepng($image);
        $photo = 'data:image/png;base64,'.base64_encode(ob_get_clean());
        imagedestroy($image);
        $id = $this->submit($this->input($product, $customer) + ['photos' => [$photo]])->assertOk()->json('id');
        $review = Record::findOrFail($id);
        $url = $review->data['photos'][0];
        Storage::disk('local')->assertExists('uploads/'.basename($url));
        $this->submit($this->input($product, $customer) + ['version' => 1, 'photos' => [$url]])->assertOk();
        $this->assertSame([$url], $review->fresh()->data['photos']);
        $this->submit($this->input($product, $customer) + ['version' => 2, 'photos' => ['data:image/png;base64,bm90YW5pbWFnZQ==']])->assertUnprocessable();
        $this->submit($this->input($product, $customer) + ['version' => 2, 'photos' => [$photo, $photo, $photo, $photo]])->assertUnprocessable();
        $this->expectException(QueryException::class);
        Record::create(['resource' => 'reviews', 'data' => $review->fresh()->data]);
    }

    public function test_sales_staff_cannot_moderate_customer_reviews(): void
    {
        [$product, $customer] = $this->fixtures();
        $id = $this->submit($this->input($product, $customer))->assertOk()->json('id');
        $this->signIn('Sales');
        $this->patchJson('/api/v1/reviews/'.$id, ['version' => 1, 'status' => 'Approved'])->assertForbidden();
    }
}
