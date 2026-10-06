<?php

namespace Tests\Feature;

use App\Models\CommerceRecord as Record;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DeliveryZoneTest extends TestCase
{
    use RefreshDatabase;

    private function signIn(): void
    {
        $user = User::create(['name' => 'Admin', 'email' => 'zones@example.com', 'password' => Hash::make('DeliveryTest!2026'), 'role' => 'Admin', 'status' => 'Active']);
        $this->withToken($this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'DeliveryTest!2026'])->assertOk()->json('token'));
    }

    private function payload(): array
    {
        return ['name' => 'Metro zone', 'country_code' => 'KE', 'county_codes' => ['047', '022'], 'charge' => 500, 'free_threshold' => 50000, 'status' => 'Active'];
    }

    public function test_multiple_counties_persist_with_rates_and_can_be_updated(): void
    {
        $this->signIn();
        $zone = $this->postJson('/api/v1/delivery-zones', $this->payload())->assertOk()->assertJsonPath('country_name', 'Kenya')->assertJsonPath('county_names', ['Nairobi', 'Kiambu'])->json();
        $this->assertSame(['047', '022'], Record::findOrFail($zone['id'])->data['county_codes']);
        $this->patchJson('/api/v1/delivery-zones/'.$zone['id'], array_merge($zone, ['county_codes' => ['001'], 'status' => 'Inactive']))->assertOk()->assertJsonPath('county_names', ['Mombasa'])->assertJsonPath('charge', 500)->assertJsonPath('free_threshold', 50000)->assertJsonPath('status', 'Inactive');
        $this->getJson('/api/v1/workspace')->assertOk()->assertJsonPath('shipping_locations.countries.KE.name', 'Kenya');
        $this->assertCount(47, config('shipping.countries.KE.counties'));
    }

    public function test_country_county_and_rate_validation_reject_invalid_submissions(): void
    {
        $this->signIn();
        $this->postJson('/api/v1/delivery-zones', array_merge($this->payload(), ['country_code' => 'US']))->assertUnprocessable()->assertJsonValidationErrors('country_code');
        $this->postJson('/api/v1/delivery-zones', array_merge($this->payload(), ['county_codes' => []]))->assertUnprocessable()->assertJsonValidationErrors('county_codes');
        $this->postJson('/api/v1/delivery-zones', array_merge($this->payload(), ['county_codes' => ['999']]))->assertUnprocessable()->assertJsonValidationErrors('county_codes.0');
        $this->postJson('/api/v1/delivery-zones', array_merge($this->payload(), ['county_codes' => ['047', '047']]))->assertUnprocessable()->assertJsonValidationErrors('county_codes.0');
        $this->postJson('/api/v1/delivery-zones', array_merge($this->payload(), ['charge' => -1, 'free_threshold' => -1]))->assertUnprocessable()->assertJsonValidationErrors(['charge', 'free_threshold']);
        $this->assertSame(0, Record::where('resource', 'delivery-zones')->count());
    }

    public function test_legacy_location_migration_preserves_delivery_rates_and_flags_unknown_locations(): void
    {
        $zone = Record::create(['resource' => 'delivery-zones', 'data' => ['name' => 'Legacy', 'towns' => ['Nairobi', 'Westlands', 'Kiambu', 'Unknown location'], 'charge' => 700, 'free_threshold' => 25000, 'status' => 'Active']]);
        $migration = require database_path('migrations/2026_10_06_053852_structure_kenya_delivery_zone_counties.php');
        $migration->up();
        $data = $zone->fresh()->data;
        $this->assertSame('KE', $data['country_code']);
        $this->assertSame(['047', '022'], $data['county_codes']);
        $this->assertSame(['Unknown location'], $data['legacy_unmapped_locations']);
        $this->assertSame(700, $data['charge']);
        $this->assertSame(25000, $data['free_threshold']);
        $migration->down();
        $this->assertSame(['Nairobi', 'Westlands', 'Kiambu', 'Unknown location'], $zone->fresh()->data['towns']);
    }
}
