<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private function rename(mixed $value): mixed
    {
        if (is_string($value)) {
            return str_ireplace('Olive'.' Electronics', 'Leekav', $value);
        }
        if (is_array($value)) {
            return array_map(fn ($item) => $this->rename($item), $value);
        }

        return $value;
    }

    public function up(): void
    {
        DB::table('commerce_records')->orderBy('id')->chunkById(100, function ($records): void {
            foreach ($records as $record) {
                $data = json_decode($record->data, true);
                $renamed = $this->rename($data);
                if ($renamed !== $data) {
                    DB::table('commerce_records')->where('id', $record->id)->update(['data' => json_encode($renamed), 'version' => $record->version + 1, 'updated_at' => now()]);
                    Cache::forget('commerce.records.'.$record->resource);
                }
            }
        });
        DB::table('email_deliveries')->orderBy('id')->chunkById(100, function ($deliveries): void {
            foreach ($deliveries as $delivery) {
                $subject = $this->rename($delivery->subject);
                $body = $this->rename($delivery->body);
                if ($subject !== $delivery->subject || $body !== $delivery->body) {
                    DB::table('email_deliveries')->where('id', $delivery->id)->update(['subject' => $subject, 'body' => $body, 'updated_at' => now()]);
                }
            }
        });
    }

    public function down(): void {}
};
