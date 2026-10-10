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
        $paymentMethods = [
            ['name' => 'PayPal', 'category' => 'Online', 'provider' => 'PayPal', 'environment' => 'Sandbox', 'credentials' => '', 'instructions' => 'Pay securely with PayPal.', 'status' => 'Inactive'],
            ['name' => 'Stripe', 'category' => 'Online', 'provider' => 'Stripe', 'environment' => 'Sandbox', 'credentials' => '', 'instructions' => 'Pay securely by card through Stripe.', 'status' => 'Inactive'],
            ['name' => 'Razorpay', 'category' => 'Online', 'provider' => 'Razorpay', 'environment' => 'Sandbox', 'credentials' => '', 'instructions' => 'Pay securely through Razorpay.', 'status' => 'Inactive'],
            ['name' => 'Cash on delivery', 'category' => 'Manual', 'provider' => 'COD', 'environment' => 'Production', 'instructions' => 'Pay the courier when your order is delivered.', 'status' => 'Active'],
        ];
        $configuredProviders = Record::where('resource', 'payment-methods')->get()->pluck('data')->pluck('provider')->all();
        foreach ($paymentMethods as $paymentMethod) {
            if (! in_array($paymentMethod['provider'], $configuredProviders, true)) {
                Record::create(['resource' => 'payment-methods', 'data' => $paymentMethod]);
            }
        }
        $this->call(RealProductCatalogSeeder::class);
        $this->call(StorefrontContentSeeder::class);
        if (filter_var(env('DEMO_WORKSPACE', false), FILTER_VALIDATE_BOOL)) {
            $this->call(OperationalDemoSeeder::class);
        }
    }
}
