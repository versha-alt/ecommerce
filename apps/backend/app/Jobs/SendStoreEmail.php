<?php

namespace App\Jobs;

use App\Services\StoreEmails;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class SendStoreEmail implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(public string $deliveryId) {}

    public function backoff(): array
    {
        return [10, 60];
    }

    public function middleware(): array
    {
        return [(new WithoutOverlapping('store-email:'.$this->deliveryId))->releaseAfter(10)->expireAfter(60)];
    }

    public function handle(StoreEmails $emails): void
    {
        $result = $emails->sendDelivery($this->deliveryId, true);
        if ($result['status'] === 'Failed') {
            throw new RuntimeException($result['error']);
        }
    }

    public function failed(?Throwable $exception): void
    {
        DB::table('email_deliveries')->where('id', $this->deliveryId)->whereNotIn('status', ['Sent', 'Logged'])->update(['status' => 'Failed', 'error' => 'Email could not be sent after queue retries. Check SMTP settings before retrying.', 'updated_at' => now()]);
    }
}
