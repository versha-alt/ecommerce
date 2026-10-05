<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['orders', 'returns'] as $resource) {
            $events = DB::table('audit_events')->where('resource', $resource)->orderBy('created_at')->get();
            foreach (DB::table('commerce_records')->where('resource', $resource)->get() as $record) {
                $data = json_decode($record->data, true);
                if (! empty($data['status_history'])) {
                    continue;
                }
                $history = [];
                foreach ($events as $event) {
                    $before = $event->before ? json_decode($event->before, true) : null;
                    $after = $event->after ? json_decode($event->after, true) : null;
                    if (($after['id'] ?? '') === $record->id && ($before['status'] ?? null) !== ($after['status'] ?? null)) {
                        $history[] = ['from' => $before['status'] ?? null, 'to' => $after['status'], 'actor' => $event->actor, 'at' => Carbon::parse($event->created_at)->toISOString(), 'note' => $event->action];
                    }
                }
                if (! $history) {
                    $history[] = ['from' => null, 'to' => $data['status'], 'actor' => 'Legacy record', 'at' => Carbon::parse($record->updated_at)->toISOString(), 'note' => 'Status at migration; earlier transitions are unavailable.'];
                }
                $data['status_history'] = $history;
                DB::table('commerce_records')->where('id', $record->id)->update(['data' => json_encode($data), 'version' => $record->version + 1]);
            }
            Cache::forget('commerce.records.'.$resource);
        }
    }

    public function down(): void {}
};
