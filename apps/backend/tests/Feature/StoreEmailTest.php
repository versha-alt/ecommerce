<?php

namespace Tests\Feature;

use App\Models\CommerceRecord as Record;
use App\Models\User;
use App\Services\StoreEmails;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Mailer;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Crypt;
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
        $this->assertDatabaseCount('email_deliveries',0);
    }
}
