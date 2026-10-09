<?php

namespace Database\Seeders;

use App\Models\CommerceRecord as Record;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('ADMIN_EMAIL', 'admin@olive.local');
        $password = env('ADMIN_PASSWORD');
        if (! $password || strlen($password) < 12) {
            throw new \RuntimeException('Configure ADMIN_PASSWORD (12+ characters).');
        }
        User::firstOrCreate(['email' => $email], ['name' => 'Alex Morgan', 'password' => Hash::make($password), 'role' => 'Admin', 'status' => 'Active']);
        Record::firstOrCreate(['resource' => 'settings'], ['data' => ['store_name' => 'Leekav', 'email' => 'hello@example.com', 'phone' => '+254 700 000 000', 'address' => 'Nairobi, Kenya', 'mail_transport' => 'Log (local preview)', 'smtp_port' => 587]]);
        if (! Record::where('resource', 'payment-methods')->exists()) {
            Record::create(['resource' => 'payment-methods', 'data' => ['name' => 'Cash on delivery', 'category' => 'Manual', 'provider' => 'COD', 'environment' => 'Production', 'instructions' => 'Pay the courier on delivery.', 'status' => 'Inactive']]);
            Record::create(['resource' => 'payment-methods', 'data' => ['name' => 'M-Pesa', 'category' => 'Online', 'provider' => 'M-Pesa', 'environment' => 'Sandbox', 'credentials' => '', 'status' => 'Inactive']]);
        }
        $this->call(RealProductCatalogSeeder::class);
    }
}
