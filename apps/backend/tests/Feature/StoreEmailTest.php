<?php

namespace Tests\Feature;

use App\Jobs\SendStoreEmail;
use App\Models\CommerceRecord as Record;
use App\Models\User;
use App\Services\StoreEmails;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Mailer;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Mockery;
use Symfony\Component\Mime\Email;
use Tests\TestCase;

class StoreEmailTest extends TestCase
{
    use RefreshDatabase;

    private function setupMail(string $role = 'Admin'): void
    {
        config(['queue.default' => 'sync']);
        $u = User::create(['name' => 'Tester', 'email' => 'mail@example.com', 'password' => Hash::make('MailTest!2026'), 'role' => $role, 'status' => 'Active']);
        $token = $this->postJson('/api/v1/auth/login', ['email' => $u->email, 'password' => 'MailTest!2026'])->assertOk()->json('token');
        $this->withToken($token);
        Record::create(['resource' => 'settings', 'data' => ['store_name' => 'LEEKAV', 'mail_transport' => 'SMTP', 'smtp_host' => 'smtp.example.com', 'smtp_port' => 587, 'smtp_username' => 'sender@example.com', 'smtp_password' => Crypt::encryptString('private-test-password'), 'mail_from' => 'sender@example.com']]);
    }

    public function test_admin_test_mail_uses_saved_encrypted_settings(): void
    {
        $this->setupMail();
        $mailer = Mockery::mock(Mailer::class);
        Mail::shouldReceive('build')->once()->withArgs(fn ($config) => $config['password'] === 'private-test-password' && $config['transport'] === 'smtp' && $config['port'] === 587)->andReturn($mailer);
        $mailer->shouldReceive('html')->once()->andReturnUsing(function ($body, $callback) {
            $email = new Email;
            $callback(new Message($email));
            $this->assertSame('recipient@example.com', $email->getTo()[0]->getAddress());
        });
        $this->postJson('/api/v1/settings/test-email', ['recipient' => 'recipient@example.com'])->assertOk()->assertJsonPath('status', 'Sent');
        $this->assertDatabaseHas('email_deliveries', ['recipient' => 'recipient@example.com', 'status' => 'Sent']);
    }

    public function test_confirmation_is_sent_only_once_and_escapes_customer_values(): void
    {
        $this->setupMail();
        $customer = Record::create(['resource' => 'customers', 'data' => ['email' => 'customer@example.com']]);
        $mailer = Mockery::mock(Mailer::class);
        Mail::shouldReceive('build')->once()->andReturn($mailer);
        $mailer->shouldReceive('html')->once()->andReturnUsing(function ($body) {
            $this->assertStringContainsString('&lt;script&gt;', $body);
        });
        $order = ['id' => (string) Str::uuid(), 'customer_id' => $customer->id, 'customer_name' => '<script>test</script>', 'reference' => 'ORD-TEST', 'total' => 1000];
        $first = app(StoreEmails::class)->confirmation($order);
        $second = app(StoreEmails::class)->confirmation($order);
        $this->assertSame('Sent', $first['status']);
        $this->assertSame($first['id'], $second['id']);
        $this->assertDatabaseCount('email_deliveries', 1);
    }

    public function test_smtp_failures_are_reported_without_leaking_secrets(): void
    {
        $this->setupMail();
        Mail::shouldReceive('build')->once()->andThrow(new \RuntimeException('private-test-password'));
        $response = $this->postJson('/api/v1/settings/test-email', ['recipient' => 'recipient@example.com'])->assertUnprocessable()->assertJsonPath('status', 'Failed');
        $this->assertStringNotContainsString('private-test-password', $response->getContent());
        $this->assertDatabaseHas('email_deliveries', ['status' => 'Failed']);
    }

    public function test_sales_cannot_send_test_emails(): void
    {
        $this->setupMail('Sales');
        $this->postJson('/api/v1/settings/test-email', ['recipient' => 'recipient@example.com'])->assertForbidden();
        $this->assertDatabaseCount('email_deliveries', 0);
    }

    public function test_status_payment_and_admin_alerts_are_deduplicated(): void
    {
        $this->setupMail();
        $settings = Record::where('resource', 'settings')->first();
        $settings->data = array_merge($settings->data, ['email' => 'operations@leekav.com']);
        $settings->save();
        $mailer = Mockery::mock(Mailer::class);
        Mail::shouldReceive('build')->times(3)->andReturn($mailer);
        $mailer->shouldReceive('html')->times(3)->andReturnUsing(function ($body) {
            $this->assertStringContainsString('ORD-EVENT', $body);
        });
        $order = ['id' => (string) Str::uuid(), 'customer_email' => 'buyer@example.com', 'reference' => 'ORD-EVENT', 'status' => 'Dispatched', 'payment_status' => 'Paid', 'status_history' => [['to' => 'Dispatched']], 'payment_status_history' => [['to' => 'Paid']]];
        $emails = app(StoreEmails::class);
        $emails->orderChanged($order, null);
        $emails->orderChanged($order, ['status' => 'Confirmed', 'payment_status' => 'Pending']);
        $emails->orderChanged($order, ['status' => 'Confirmed', 'payment_status' => 'Pending']);
        $emails->orderChanged($order, $order);
        $this->assertDatabaseCount('email_deliveries', 3);
        $this->assertDatabaseHas('email_deliveries', ['event' => 'Admin new order', 'recipient' => 'operations@leekav.com', 'status' => 'Sent']);
        $this->assertDatabaseHas('email_deliveries', ['event' => 'Order status update', 'status' => 'Sent']);
        $this->assertDatabaseHas('email_deliveries', ['event' => 'Payment successful', 'status' => 'Sent']);
    }

    public function test_failed_confirmation_can_be_retried_without_duplicate_records(): void
    {
        $this->setupMail();
        $customer = Record::create(['resource' => 'customers', 'data' => ['email' => 'buyer@example.com']]);
        Mail::shouldReceive('build')->once()->andThrow(new \RuntimeException('SMTP unavailable'));
        $order = ['id' => (string) Str::uuid(), 'customer_id' => $customer->id, 'customer_name' => 'Buyer', 'reference' => 'ORD-RETRY', 'total' => 500];
        $this->assertSame('Failed', app(StoreEmails::class)->confirmation($order)['status']);
        $mailer = Mockery::mock(Mailer::class);
        Mail::shouldReceive('build')->once()->andReturn($mailer);
        $mailer->shouldReceive('html')->once();
        $this->assertSame('Sent', app(StoreEmails::class)->confirmation($order)['status']);
        $this->assertDatabaseCount('email_deliveries', 1);
    }

    public function test_newsletter_sends_welcome_once_for_normalized_email(): void
    {
        $this->setupMail();
        $mailer = Mockery::mock(Mailer::class);
        Mail::shouldReceive('build')->once()->andReturn($mailer);
        $mailer->shouldReceive('html')->once()->andReturnUsing(function ($body, $callback) {
            $this->assertStringContainsString('Thanks for subscribing', $body);
            $email = new Email;
            $callback(new Message($email));
            $this->assertSame('subscriber@example.com', $email->getTo()[0]->getAddress());
        });
        $this->postJson('/api/v1/store/newsletter', ['email' => 'Subscriber@example.com'])->assertOk()->assertJsonPath('email_delivery.status', 'Sent');
        $this->postJson('/api/v1/store/newsletter', ['email' => 'subscriber@example.com'])->assertOk()->assertJsonPath('email_delivery.status', 'Sent');
        $this->assertSame(1, Record::where('resource', 'newsletter')->count());
        $this->assertDatabaseCount('email_deliveries', 1);
    }

    public function test_newsletter_keeps_subscription_on_smtp_failure_and_allows_retry(): void
    {
        $this->setupMail();
        Mail::shouldReceive('build')->once()->andThrow(new \RuntimeException('private-test-password'));
        $this->postJson('/api/v1/store/newsletter', ['email' => 'subscriber@example.com'])->assertOk()->assertJsonPath('email_delivery.status', 'Failed');
        $this->assertSame(1, Record::where('resource', 'newsletter')->count());
        $mailer = Mockery::mock(Mailer::class);
        Mail::shouldReceive('build')->once()->andReturn($mailer);
        $mailer->shouldReceive('html')->once();
        $this->postJson('/api/v1/store/newsletter', ['email' => 'subscriber@example.com'])->assertOk()->assertJsonPath('email_delivery.status', 'Sent');
        $this->assertDatabaseCount('email_deliveries', 1);
    }

    public function test_email_templates_render_literal_newlines_as_line_breaks(): void
    {
        $this->setupMail();
        Record::create(['resource' => 'emails', 'data' => ['event' => 'Order status update', 'status' => 'Active', 'subject' => 'Update {{order_reference}}', 'body' => 'Hi {{customer_name}},\\n\\nOrder update.\\r\\nThank you.']]);
        $mailer = Mockery::mock(Mailer::class);
        Mail::shouldReceive('build')->once()->andReturn($mailer);
        $mailer->shouldReceive('html')->once()->andReturnUsing(function ($body) {
            $this->assertStringNotContainsString('\\n', $body);
            $this->assertStringNotContainsString('\\r', $body);
            $this->assertStringContainsString("Hi Buyer,<br />\n<br />\nOrder update.<br />\nThank you.", $body);
        });
        app(StoreEmails::class)->orderChanged(['id' => (string) Str::uuid(), 'customer_email' => 'buyer@example.com', 'customer_name' => 'Buyer', 'reference' => 'ORD-FORMAT', 'status' => 'Dispatched', 'payment_status' => 'Pending'], ['status' => 'Confirmed', 'payment_status' => 'Pending']);
        $this->assertDatabaseHas('email_deliveries', ['event' => 'Order status update', 'status' => 'Sent']);
    }

    public function test_database_queue_stores_one_job_without_contacting_smtp(): void
    {
        $this->setupMail();
        config(['queue.default' => 'database']);
        Mail::shouldReceive('build')->never();
        $first = $this->postJson('/api/v1/store/newsletter', ['email' => 'queued@example.com'])->assertOk()->assertJsonPath('email_delivery.status', 'Queued')->json('email_delivery');
        $this->postJson('/api/v1/store/newsletter', ['email' => 'queued@example.com'])->assertOk()->assertJsonPath('email_delivery.id', $first['id']);
        $this->assertDatabaseCount('jobs', 1);
        $this->assertDatabaseHas('jobs', ['queue' => 'emails']);
        $this->assertDatabaseHas('email_deliveries', ['id' => $first['id'], 'status' => 'Queued', 'attempts' => 0]);
        $payload = DB::table('jobs')->first()->payload;
        $this->assertStringNotContainsString('private-test-password', $payload);
    }

    public function test_queued_delivery_retries_safely_and_does_not_resend_successful_mail(): void
    {
        $this->setupMail();
        config(['queue.default' => 'database']);
        $subscriber = Record::create(['resource' => 'newsletter', 'data' => ['email' => 'queued@example.com']]);
        $delivery = app(StoreEmails::class)->newsletter($subscriber);
        $job = new SendStoreEmail($delivery['id']);
        $this->assertSame(3, $job->tries);
        $this->assertSame([10, 60], $job->backoff());
        Mail::shouldReceive('build')->once()->andThrow(new \RuntimeException('private-test-password'));
        try {
            $job->handle(app(StoreEmails::class));
            $this->fail('Failed SMTP must release the job for another attempt.');
        } catch (\RuntimeException $exception) {
            $this->assertStringNotContainsString('private-test-password', $exception->getMessage());
        }
        $this->assertDatabaseHas('email_deliveries', ['id' => $delivery['id'], 'status' => 'Retrying', 'attempts' => 1]);
        $mailer = Mockery::mock(Mailer::class);
        Mail::shouldReceive('build')->once()->andReturn($mailer);
        $mailer->shouldReceive('html')->once();
        $job->handle(app(StoreEmails::class));
        $job->handle(app(StoreEmails::class));
        $job->failed(new \RuntimeException('late failure'));
        $this->assertDatabaseHas('email_deliveries', ['id' => $delivery['id'], 'status' => 'Sent', 'attempts' => 2, 'error' => null]);
    }

    public function test_queue_and_delivery_are_rolled_back_together(): void
    {
        $this->setupMail();
        config(['queue.default' => 'database']);
        $subscriber = Record::create(['resource' => 'newsletter', 'data' => ['email' => 'rollback@example.com']]);
        DB::beginTransaction();
        app(StoreEmails::class)->newsletter($subscriber);
        DB::rollBack();
        $this->assertDatabaseCount('jobs', 0);
        $this->assertDatabaseCount('email_deliveries', 0);
    }
}
