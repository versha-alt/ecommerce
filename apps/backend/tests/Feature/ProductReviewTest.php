<?php

namespace Tests\Feature;

use App\Models\CommerceRecord as Record;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
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

    public function test_sales_staff_cannot_moderate_customer_reviews(): void
    {
        [$product, $customer] = $this->fixtures();
        $id = $this->submit($this->input($product, $customer))->assertOk()->json('id');
        $this->signIn('Sales');
        $this->patchJson('/api/v1/reviews/'.$id, ['version' => 1, 'status' => 'Approved'])->assertForbidden();
    }
}
