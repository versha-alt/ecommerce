<?php

namespace Database\Seeders;

use App\Models\CommerceRecord as Record;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StorefrontContentSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $this->completeSettings();
            $this->seedBanners();
            $this->seedHomepageSections();
            $this->seedArticles();
            $this->seedPages();
            $this->seedCoupons();
            $this->completeCategoryMerchandising();
            $this->completeProductMerchandising();
        });
    }

    private function seedCoupons(): void
    {
        $coupons = [
            ['name' => 'Welcome home', 'code' => 'WELCOME10', 'description' => 'Save 10% on an order of KES 20,000 or more.', 'discount_kind' => 'Order', 'discount_type' => 'Percentage', 'value' => 10, 'eligibility' => 'All', 'scope' => 'All', 'minimum_type' => 'Amount', 'minimum_amount' => 20000, 'usage_limit' => 500, 'once_per_customer' => true],
            ['name' => 'Big appliance saving', 'code' => 'SAVE2500', 'description' => 'Save KES 2,500 when your order reaches KES 50,000.', 'discount_kind' => 'Order', 'discount_type' => 'Fixed', 'value' => 2500, 'eligibility' => 'All', 'scope' => 'All', 'minimum_type' => 'Amount', 'minimum_amount' => 50000, 'usage_limit' => 250, 'once_per_customer' => false],
            ['name' => 'Free delivery offer', 'code' => 'FREESHIP', 'description' => 'Free delivery on qualifying orders of KES 30,000 or more.', 'discount_kind' => 'Shipping', 'discount_type' => 'Free', 'value' => 0, 'eligibility' => 'All', 'scope' => 'All', 'minimum_type' => 'Amount', 'minimum_amount' => 30000, 'max_shipping' => 3000, 'usage_limit' => 300, 'once_per_customer' => false],
        ];
        foreach ($coupons as $coupon) {
            $this->createWhenMissing('coupons', 'code', $coupon['code'], $coupon + ['usage' => 0, 'status' => 'Active']);
        }
    }

    private function completeSettings(): void
    {
        $record = Record::where('resource', 'settings')->first();
        if (! $record) {
            return;
        }
        $record->data = array_merge(['announcement' => 'Thoughtfully chosen. Delivered to your door.', 'footer_tagline' => 'Good brands. Thoughtful choices. A little better, every day.', 'site_title' => 'LEEKAV | A better everyday', 'site_description' => 'Shop home appliances from Kärcher, Midea and LG. Thoughtful choices, delivered across selected Kenyan counties.'], $record->data);
        $record->save();
    }

    private function createWhenMissing(string $resource, string $field, string $value, array $data): void
    {
        if (! Record::where('resource', $resource)->where('data->'.$field, $value)->exists()) {
            Record::create(['resource' => $resource, 'data' => $data]);
        }
    }

    private function seedBanners(): void
    {
        $slides = [
            ['name' => 'Everyday home', 'eyebrow' => 'LEEKAV HOME ESSENTIALS · KENYA', 'headline' => 'Upgrade your home. Elevate your everyday.', 'description' => 'Shop reliable appliances from trusted brands, priced clearly and delivered with support that keeps everyday life moving.', 'image' => '/assets/banners/everyday-appliances.webp', 'link' => '/products', 'cta_label' => 'Shop the range', 'sort_order' => 10],
            ['name' => 'Kitchen essentials', 'eyebrow' => 'LEEKAV KITCHEN ESSENTIALS · KENYA', 'headline' => 'Better cooking. Brighter everyday.', 'description' => 'Refresh your kitchen with practical appliances chosen for everyday cooking, storage and simple routines.', 'image' => '/assets/banners/kitchen-appliances.webp', 'link' => '/categories/cookers-microwaves', 'cta_label' => 'Shop kitchen', 'sort_order' => 20],
            ['name' => 'Living room upgrades', 'eyebrow' => 'LEEKAV HOME ENTERTAINMENT · KENYA', 'headline' => 'Big moments. Better together.', 'description' => 'Bring comfort, entertainment and easy living together with dependable home technology.', 'image' => '/assets/banners/living-entertainment.webp', 'link' => '/categories/tvs-audio', 'cta_label' => 'Shop entertainment', 'sort_order' => 30],
        ];
        foreach ($slides as $slide) {
            $this->createWhenMissing('banners', 'name', $slide['name'], $slide + ['placement' => 'Hero slider', 'status' => 'Active']);
        }
    }

    private function seedHomepageSections(): void
    {
        $sections = [
            ['key' => 'categories', 'name' => 'Shop by category', 'eyebrow' => 'SHOP BY CATEGORY', 'heading' => 'Find what fits your home.', 'link' => '/products', 'link_label' => 'Explore all categories', 'sort_order' => 10],
            ['key' => 'featured-products', 'name' => 'Featured products', 'eyebrow' => 'A FEW EVERYDAY FAVOURITES', 'heading' => 'Good choices. Great brands.', 'link' => '/products?featured=1', 'link_label' => 'Shop all products', 'sort_order' => 20],
            ['key' => 'deals', 'name' => 'Homepage deals', 'eyebrow' => 'MORE VALUE FOR YOUR HOME', 'heading' => 'Deals worth bringing home.', 'body' => 'Limited-time prices on the appliances your home needs.', 'link' => '/deals', 'link_label' => 'Shop all deals', 'sort_order' => 30],
            ['key' => 'deals-page', 'name' => 'Deals page', 'eyebrow' => 'LIMITED-TIME VALUE - LEEKAV KENYA', 'heading' => 'Better prices for a better everyday.', 'body' => 'Explore current savings on practical appliances from trusted brands. Every offer, price and stock level shown here is managed live from the admin panel.', 'image' => '/assets/banners/kitchen-appliances.webp', 'link' => '/products', 'link_label' => 'Browse all products', 'sort_order' => 35],
            ['key' => 'editorial', 'name' => 'Cleaning editorial', 'eyebrow' => 'A FRESH PERSPECTIVE', 'heading' => 'Less time on chores. More time for you.', 'body' => 'Meet the hardworking helpers that make a clean home feel effortless, from quick daily tidy-ups to deep weekend cleans.', 'image' => '/assets/banners/modern-smart-home-panorama.webp', 'link' => '/categories/vacuum-cleaners', 'link_label' => 'Discover cleaning essentials', 'sort_order' => 40],
            ['key' => 'new-arrivals', 'name' => 'New arrivals', 'eyebrow' => 'YOUR NEXT GREAT FIND', 'heading' => 'Fresh additions to your home.', 'link' => '/products?sort=newest', 'link_label' => "See what's new", 'sort_order' => 50],
            ['key' => 'brands', 'name' => 'Brand collection', 'eyebrow' => 'OUR BRANDS', 'heading' => 'Brands you know and trust', 'body' => 'Genuine products from leading names, backed by warranty and local support.', 'sort_order' => 60],
            ['key' => 'journal', 'name' => 'Journal', 'eyebrow' => 'IDEAS FOR A BETTER EVERYDAY', 'heading' => 'The LEEKAV journal.', 'body' => 'Helpful guides and a little inspiration for your home.', 'sort_order' => 70],
        ];
        foreach ($sections as $section) {
            $this->createWhenMissing('homepage-sections', 'key', $section['key'], $section + ['status' => 'Active']);
        }
    }

    private function seedArticles(): void
    {
        $articles = [
            ['slug' => 'choosing-appliances-for-your-home', 'name' => 'Find the right appliances for your home', 'category' => 'Buying guides', 'excerpt' => 'A few practical checks to make your next everyday upgrade a confident choice.', 'image' => '/assets/banners/everyday-appliances.webp', 'image_alt' => 'Modern home with thoughtfully arranged appliances', 'minutes' => 3, 'body' => '<h2>Start with your space</h2><p>Measure the available width, height and depth before choosing an appliance. Leave room for ventilation, doors, cables and connections according to the product manual.</p><h2>Choose for your everyday routine</h2><p>Consider household size, how often you will use the appliance and the features you actually need. Compare capacity, dimensions and power requirements in the product specifications.</p><h2>Check the details before checkout</h2><p>Review stock availability, warranty information and delivery options for your county. Contact our team if you need clarification before ordering.</p>', 'sort_order' => 10],
            ['slug' => 'a-more-organised-kitchen', 'name' => 'Make your kitchen work better for you', 'category' => 'At home', 'excerpt' => 'Create an easier daily routine with a little planning and the right kitchen essentials.', 'image' => '/assets/banners/kitchen-appliances.webp', 'image_alt' => 'Contemporary kitchen with built-in appliances', 'minutes' => 3, 'body' => '<h2>Plan around how you cook</h2><p>Think about your most frequent meals and how much preparation space you need. Keep commonly used tools within easy reach and reserve safe, stable surfaces for small appliances.</p><h2>Pick a capacity that fits</h2><p>A larger appliance is useful only if it suits your household and space. Compare usable capacity and dimensions, and check installation requirements before making your choice.</p><h2>Keep care simple</h2><p>Follow the manufacturer’s cleaning and maintenance instructions for every appliance.</p>', 'sort_order' => 20],
            ['slug' => 'planning-your-home-entertainment-space', 'name' => 'Plan a comfortable home entertainment space', 'category' => 'Living well', 'excerpt' => 'Find a screen and room layout that fit the way you relax, watch and unwind.', 'image' => '/assets/banners/living-entertainment.webp', 'image_alt' => 'Comfortable living room and home entertainment setup', 'minutes' => 3, 'body' => '<h2>Match the screen to your room</h2><p>Consider viewing distance, available wall or stand space and where people will sit.</p><h2>Consider connections and light</h2><p>Check that the inputs and features support the devices you use. Position the screen to reduce glare.</p><h2>Install with care</h2><p>Use a stand or mounting system suitable for the television and follow its installation instructions.</p>', 'sort_order' => 30],
        ];
        foreach ($articles as $article) {
            $this->createWhenMissing('articles', 'slug', $article['slug'], $article + ['status' => 'Active']);
        }
    }

    private function seedPages(): void
    {
        $pages = [
            ['slug' => 'about', 'name' => 'About LEEKAV', 'seo_title' => 'About LEEKAV', 'seo_description' => 'Learn about LEEKAV and our approach to home appliances.', 'body' => '<h2>Good brands. Thoughtful choices.</h2><p>LEEKAV brings together dependable appliances and home essentials selected for everyday life in Kenya.</p><p>Our team can help with product questions, delivery options and order enquiries.</p>'],
            ['slug' => 'faq', 'name' => 'Frequently asked questions', 'seo_title' => 'Frequently asked questions', 'seo_description' => 'Answers about shopping, delivery, payment, returns and warranty.', 'body' => '<h2>Where do you deliver?</h2><p>Delivery availability and rates are shown after selecting your county at checkout.</p><h2>How do I pay?</h2><p>Checkout displays the payment methods currently enabled for the store.</p><h2>How do I track my order?</h2><p>Sign in to your account to view orders and their latest payment and delivery statuses.</p><h2>How do I request a return?</h2><p>For an eligible delivered and paid order, sign in, open the order and submit a return request.</p>'],
            ['slug' => 'privacy', 'name' => 'Privacy policy', 'seo_title' => 'Privacy policy', 'seo_description' => 'How LEEKAV handles customer and order information.', 'body' => '<h2>Information used to serve you</h2><p>We collect contact details, delivery addresses, order information and enquiries to manage purchases and customer support.</p><p>Newsletter subscriptions are optional. Contact the store to request assistance with your information or unsubscribe.</p>'],
            ['slug' => 'shipping-returns', 'name' => 'Shipping and returns', 'seo_title' => 'Shipping and returns', 'seo_description' => 'Delivery areas, charges, returns and refunds at LEEKAV.', 'body' => '<h2>Delivery in Kenya</h2><p>Select your county during checkout. The final delivery charge and any free-delivery threshold are shown before you place the order.</p><h2>Returns and refunds</h2><p>Request a return from your customer account for an eligible delivered and paid order. Our team reviews each request and keeps its status updated.</p>'],
            ['slug' => 'terms-conditions', 'name' => 'Terms and conditions', 'seo_title' => 'Terms and conditions', 'seo_description' => 'Terms that apply when shopping with LEEKAV.', 'body' => '<h2>Orders and payment</h2><p>Review product details, availability, payment instructions and final totals before placing an order.</p><h2>Product information and support</h2><p>Specifications, warranty details and manuals are provided where available. Contact us if you need clarification.</p>'],
            ['slug' => 'contact', 'name' => 'Contact us', 'seo_title' => 'Contact LEEKAV', 'seo_description' => 'Get help with products, delivery or an existing LEEKAV order.', 'body' => '<h2>How we can help</h2><p>Contact our team with product questions, delivery enquiries or an order reference. We will respond using the contact details you provide.</p>'],
        ];
        foreach ($pages as $page) {
            $this->createWhenMissing('pages', 'slug', $page['slug'], $page + ['status' => 'Active']);
        }
    }

    private function completeCategoryMerchandising(): void
    {
        foreach (Record::where('resource', 'categories')->get() as $index => $record) {
            $data = $record->data;
            if (! empty($data['parent_id'])) {
                continue;
            }
            $categoryImageSlug = ($data['slug'] ?? '') === 'small-appliances' ? 'kitchen-small-appliances' : ($data['slug'] ?? 'category');
            $defaults = ['sort_order' => ($index + 1) * 10, 'nav_label' => $data['name'] ?? '', 'image' => '/images/categories/'.$categoryImageSlug.'-realistic.webp', 'promo_label' => 'Explore the collection', 'promo_headline' => $data['name'] ?? '', 'promo_description' => $data['description'] ?? '', 'promo_image' => $data['image'] ?? null];
            $record->data = array_merge($defaults, $data);
            $record->save();
        }
    }

    private function completeProductMerchandising(): void
    {
        $products = Record::where('resource', 'products')->orderBy('created_at')->get();
        foreach ($products as $index => $record) {
            $data = $record->data;
            $defaults = ['featured' => $index < 4, 'deal' => isset($data['sale_price']) && $data['sale_price'] !== null && $index < 12, 'new_arrival' => $index >= max(0, $products->count() - 4)];
            $record->data = array_merge($defaults, $data);
            $record->save();
        }
    }
}
