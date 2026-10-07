<?php

namespace Database\Seeders;

use App\Models\CommerceRecord as Record;
use App\Models\User;
use App\Services\Commerce;
use App\Services\CustomerPayments;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(fn () => $this->seedWorkspace());
    }

    private function seedWorkspace(): void
    {
        $email = env('ADMIN_EMAIL', 'admin@olive.local');
        $password = env('ADMIN_PASSWORD');
        if (! $password || strlen($password) < 12) {
            throw new \RuntimeException('Configure ADMIN_PASSWORD (12+ characters).');
        }
        $admin = User::firstOrCreate(['email' => $email], ['name' => 'Alex Morgan', 'password' => Hash::make($password), 'role' => 'Admin', 'status' => 'Active']);
        if (Record::exists()) {
            $this->command?->info('Existing workspace preserved. No demo data added.');

            return;
        }
        $make = fn ($resource, $data) => Record::create(['resource' => $resource, 'data' => $data + ['status' => 'Active']]);
        $make('settings', ['store_name' => 'Leekav', 'email' => 'hello@example.com', 'phone' => '+254 700 000 000', 'address' => 'Nairobi, Kenya', 'mail_transport' => 'Log (local preview)', 'smtp_port' => 587]);
        $make('payment-methods', ['name' => 'Cash on delivery', 'category' => 'Manual', 'provider' => 'COD', 'environment' => 'Production', 'instructions' => 'Pay the courier on delivery.', 'status' => 'Inactive']);
        $make('payment-methods', ['name' => 'M-Pesa', 'category' => 'Online', 'provider' => 'M-Pesa', 'environment' => 'Sandbox', 'credentials' => '', 'status' => 'Inactive']);
        if (! config('commerce.demo')) {
            return;
        }
        $brands = [];
        foreach (['Kärcher', 'Midea', 'LG'] as $name) {
            $brands[] = $make('brands', ['name' => $name, 'description' => ['Kärcher' => 'Cleaning technology for a better everyday.', 'Midea' => 'Smart, reliable appliances for every home.', 'LG' => 'Thoughtful innovation. Life’s good.'][$name], 'website' => '', 'image' => '']);
        }
        $root = $make('categories', ['name' => 'Home appliances', 'slug' => 'home-appliances', 'parent_id' => null, 'description' => 'Appliances that make a house a home.']);
        $cats = [];
        foreach (['Laundry', 'Kitchen', 'Cleaning', 'Climate control', 'Entertainment'] as $name) {
            $cats[] = $make('categories', ['name' => $name, 'slug' => Str::slug($name), 'parent_id' => $root->id, 'description' => '']);
        }
        $make('categories', ['name' => 'Washing machines', 'slug' => 'washing-machines', 'parent_id' => $cats[0]->id, 'description' => '']);
        foreach ([['Capacity', 'capacity', 'Number', 'kg'], ['Power', 'power', 'Number', 'W'], ['Colour', 'colour', 'Single choice', ''], ['Dimensions', 'dimensions', 'Text', 'cm']] as $a) {
            $make('attributes', ['name' => $a[0], 'code' => $a[1], 'input_type' => $a[2], 'unit' => $a[3], 'options' => $a[2] === 'Single choice' ? ['White', 'Silver', 'Black'] : [], 'filterable' => true, 'required' => false]);
        }
        $zone = $make('delivery-zones', ['name' => 'Nairobi metro', 'country_code' => 'KE', 'country_name' => 'Kenya', 'county_codes' => ['047'], 'county_names' => ['Nairobi'], 'towns' => ['Nairobi', 'Westlands', 'Kilimani', 'Karen'], 'charge' => 500, 'free_threshold' => 50000]);
        $make('delivery-zones', ['name' => 'Kiambu & satellite towns', 'country_code' => 'KE', 'country_name' => 'Kenya', 'county_codes' => ['022'], 'county_names' => ['Kiambu'], 'towns' => ['Kiambu', 'Ruiru', 'Thika'], 'charge' => 900, 'free_threshold' => 75000]);
        $make('delivery-zones', ['name' => 'Mombasa', 'country_code' => 'KE', 'country_name' => 'Kenya', 'county_codes' => ['001'], 'county_names' => ['Mombasa'], 'towns' => ['Mombasa', 'Nyali'], 'charge' => 1800, 'free_threshold' => 100000]);
        $make('taxes', ['name' => 'Standard VAT · demo configuration', 'rate' => 16, 'inclusive' => true]);
        $make('coupons', ['name' => 'A warm welcome', 'code' => 'WELCOME10', 'discount_kind' => 'Order', 'scope' => 'All', 'eligibility' => 'All', 'minimum_type' => 'Amount', 'minimum_amount' => 10000, 'discount_type' => 'Percentage', 'value' => 10, 'min_order' => 10000, 'usage_limit' => 100, 'usage' => 0, 'starts_at' => now()->startOfMonth()->toDateString(), 'ends_at' => now()->addMonths(2)->toDateString()]);
        $products = [];
        $items = [
            ['Midea 8 kg front load washer', 'MD-WM-008', 1, 0, 54990, 49990, 'washer'], ['Kärcher K4 pressure washer', 'KA-K4-001', 0, 2, 42900, null, 'cleaner'], ['LG 43-inch UHD smart TV', 'LG-TV-043', 2, 4, 45990, null, 'tv'], ['Midea 210 L refrigerator', 'MD-RF-210', 1, 1, 38900, null, 'fridge'], ['LG NeoChef microwave', 'LG-MW-025', 2, 1, 21900, 19900, 'microwave'], ['Kärcher WD 3 vacuum cleaner', 'KA-WD3-001', 0, 2, 18400, null, 'vacuum'], ['Midea inverter air conditioner', 'MD-AC-012', 1, 3, 72900, null, 'ac'], ['LG 9 kg washing machine', 'LG-WM-009', 2, 0, 89900, null, 'washer'], ['Midea 4-burner gas cooker', 'MD-CK-004', 1, 1, 32900, null, 'cooker'], ['Kärcher steam cleaner SC 2', 'KA-SC2-001', 0, 2, 27900, null, 'cleaner'], ['LG 55-inch OLED television', 'LG-TV-055', 2, 4, 159900, null, 'tv'], ['Midea electric kettle', 'MD-KT-017', 1, 1, 3900, null, 'kettle'],
        ];
        foreach ($items as $i => $p) {
            $products[] = $make('products', ['name' => $p[0], 'sku' => $p[1], 'type' => 'Simple', 'brand_id' => $brands[$p[2]]->id, 'category_ids' => [$cats[$p[3]]->id], 'price' => $p[4], 'sale_price' => $p[5], 'stock' => 60, 'reserved' => 0, 'low_stock_threshold' => 5, 'image' => '/assets/'.$p[6].'.svg', 'description' => 'Reliable performance, thoughtful design and everyday convenience. Demo catalog description.', 'specifications' => 'Colour: Silver\nWarranty: 12 months', 'warranty' => '12-month demo manufacturer warranty', 'slug' => Str::slug($p[0]), 'seo_title' => $p[0], 'seo_description' => 'Discover '.$p[0].' at Leekav.']);
        }
        $customers = [];
        foreach ([['Grace Wanjiku', 'grace.w@example.com'], ['Brian Otieno', 'brian.o@example.com'], ['Sarah Mwangi', 'sarah.m@example.com'], ['Daniel Kimani', 'daniel.k@example.com'], ['Faith Njeri', 'faith.n@example.com'], ['James Ochieng', 'james.o@example.com'], ['Amina Hassan', 'amina.h@example.com'], ['Peter Kamau', 'peter.k@example.com']] as $i => $c) {
            $r = $make('customers', ['name' => $c[0], 'email' => $c[1], 'phone' => '+254 700 000 00'.$i, 'account_type' => 'Individual', 'address' => 'Demo address, Nairobi', 'county' => 'Nairobi', 'marketing_consent' => false, 'consent_history' => [], 'notes' => 'Sample customer record.']);
            $r->created_at = now()->subDays($i * 2);
            $r->save();
            $customers[] = $r;
        }
        $commerce = app(Commerce::class);
        for ($i = 0; $i < 25; $i++) {
            $p = $products[$i % count($products)];
            $o = $commerce->createOrder(['customer_id' => $customers[$i % 8]->id, 'delivery_zone_id' => $zone->id, 'delivery_address' => 'Demo address, Nairobi', 'lines' => [['product_id' => $p->id, 'quantity' => $i % 3 === 0 ? 2 : 1]], 'coupon_code' => $i % 5 === 0 ? 'WELCOME10' : '', 'notes' => 'Demo order for local review.'], (string) Str::uuid(), $admin);
            if ($i % 5 !== 0) {
                $o = $commerce->orderAction($o['id'], ['version' => $o['version'], 'action' => 'confirm', 'evidence' => 'Demo confirmation'], (string) Str::uuid(), $admin);
                $provider = $i % 2 ? 'M-Pesa' : 'Card';
                $demoMethod = new Record(['id' => (string) Str::uuid(), 'data' => ['name' => $provider, 'provider' => $provider, 'status' => 'Active']]);
                $payment = app(CustomerPayments::class)->begin(Record::find($o['id']), $demoMethod);
                app(CustomerPayments::class)->event(['event_id' => 'DEMO-'.Str::uuid(), 'payment_id' => $payment->id, 'method' => $provider, 'reference' => 'DEMO-'.$provider.'-'.Str::random(12), 'amount' => $o['total'], 'currency' => 'KES', 'status' => 'Successful']);
                $o = Record::find($o['id'])->row();
                if ($i % 4 !== 0) {
                    $o = $commerce->orderAction($o['id'], ['version' => $o['version'], 'action' => 'dispatch', 'evidence' => 'DEMO-HANDOVER-'.$i], (string) Str::uuid(), $admin);
                    if ($i % 3 !== 0) {
                        $o = $commerce->orderAction($o['id'], ['version' => $o['version'], 'action' => 'deliver', 'evidence' => 'Demo signed delivery receipt'], (string) Str::uuid(), $admin);
                    }
                }
            }
            $record = Record::find($o['id']);
            $day = $i < 15 ? now()->startOfMonth()->addDays($i % max(1, now()->day)) : now()->subDays(10 + $i);
            $record->created_at = $day->setTime(9 + $i % 8, $i * 2 % 60);
            $record->save();
            Record::where('resource', 'payments')->get()->filter(fn ($r) => $r->data['order_id'] === $o['id'])->each(function ($r) use ($record) {
                $r->created_at = $record->created_at;
                $r->save();
            });
        }
        foreach ([1 => 3, 4 => 2, 5 => 0] as $idx => $available) {
            $p = $products[$idx]->fresh();
            $p->data = array_merge($p->data, ['stock' => $p->data['reserved'] + $available]);
            $p->save();
        }
        foreach (['About', 'Contact', 'FAQ', 'Shipping & Returns', 'Privacy', 'Terms & Conditions'] as $name) {
            $make('pages', ['name' => $name, 'slug' => Str::slug($name), 'body' => 'Draft content for '.$name.'. Replace with your approved business information before publishing.', 'status' => 'Inactive', 'seo_title' => $name]);
        }
        $make('banners', ['name' => 'A fresh start for your home', 'placement' => 'Hero slider', 'headline' => 'Everyday living, thoughtfully upgraded.', 'description' => 'Discover appliances from Midea, LG and Kärcher.', 'image' => '', 'link' => '/products']);
        $make('banners', ['name' => 'Clean smarter with Kärcher', 'placement' => 'Promotional banner', 'headline' => 'Make room for a cleaner everyday.', 'image' => '', 'link' => '/brands/karcher']);
        foreach (['Order confirmation', 'Order status update', 'Admin new order', 'Payment successful', 'Password reset'] as $event) {
            $make('emails', ['name' => $event, 'event' => $event, 'subject' => $event.' · {{order_reference}}', 'body' => "Hi {{customer_name}},\n\nYour order {{order_reference}} has an update. Order total: {{total}}.\n\nThank you for choosing Leekav."]);
        }
        $make('enquiries', ['name' => 'Mary Achieng', 'email' => 'mary@example.com', 'subject' => 'Does the washer include installation?', 'message' => 'I would like to know about delivery and installation options.', 'status' => 'Open']);
        $make('enquiries', ['name' => 'David Mutua', 'email' => 'david@example.com', 'subject' => 'Kärcher pressure washer warranty', 'message' => 'Please share the warranty details and accessories.', 'status' => 'In progress']);
        $unpaid = collect($commerce->rows('orders'))->firstWhere('payment_status', 'Unpaid');
        if ($unpaid) {
            app(CustomerPayments::class)->begin(Record::find($unpaid['id']), new Record(['data' => ['name' => 'M-Pesa', 'provider' => 'M-Pesa', 'status' => 'Active']]));
        }
        $this->command?->info('Demo workspace seeded. All records are samples; no provider requests were made.');
    }
}
