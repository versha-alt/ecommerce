<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $counties = config('shipping.countries.KE.counties');
        $byName = [];
        foreach ($counties as $code => $name) {
            $byName[strtolower($name)] = $code;
        }
        $byName += ['nairobi city' => '047', 'westlands' => '047', 'kilimani' => '047', 'karen' => '047', 'ruiru' => '022', 'thika' => '022', 'nyali' => '001', 'taita taveta' => '006', 'elgeyo marakwet' => '028'];
        DB::transaction(function () use ($counties, $byName): void {
            foreach (DB::table('commerce_records')->where('resource', 'delivery-zones')->get() as $record) {
                $data = json_decode($record->data, true);
                $codes = [];
                $unmapped = [];
                foreach ($data['towns'] ?? [] as $location) {
                    $code = $byName[strtolower(trim($location))] ?? null;
                    if ($code) {
                        $codes[] = $code;
                    } else {
                        $unmapped[] = $location;
                    }
                }
                $data['legacy_towns'] = $data['towns'] ?? [];
                $data['country_code'] = 'KE';
                $data['country_name'] = 'Kenya';
                $data['county_codes'] = array_values(array_unique($codes));
                $data['county_names'] = array_map(fn (string $code): string => $counties[$code], $data['county_codes']);
                if ($unmapped) {
                    $data['legacy_unmapped_locations'] = $unmapped;
                }
                DB::table('commerce_records')->where('id', $record->id)->update(['data' => json_encode($data)]);
            }
        });
        Cache::forget('commerce.records.delivery-zones');
    }

    public function down(): void
    {
        foreach (DB::table('commerce_records')->where('resource', 'delivery-zones')->get() as $record) {
            $data = json_decode($record->data, true);
            $data['towns'] = $data['legacy_towns'] ?? $data['towns'] ?? [];
            foreach (['country_code', 'country_name', 'county_codes', 'county_names', 'legacy_towns', 'legacy_unmapped_locations'] as $key) {
                unset($data[$key]);
            }
            DB::table('commerce_records')->where('id', $record->id)->update(['data' => json_encode($data)]);
        }
        Cache::forget('commerce.records.delivery-zones');
    }
};
