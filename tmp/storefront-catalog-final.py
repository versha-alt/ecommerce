from pathlib import Path
p=Path('apps/backend/routes/web.php');s=p.read_text();s=s.replace("throttle:120,1')->group", "throttle:120,1,store:')->group");s=s.replace("throttle:10,1');\n        Route", "throttle:10,1,store-auth:');\n        Route",2);s=s.replace("'throttle:20,1'", "'throttle:20,1,checkout:'").replace("'throttle:30,1');\n        Route::get('orders", "'throttle:30,1,quote:');\n        Route::get('orders");s=s.replace("'throttle:10,1');\n        Route::post('newsletter'", "'throttle:10,1,enquiry:');\n        Route::post('newsletter'");s=s.replace("[StorefrontController::class, 'newsletter'])->middleware('throttle:10,1')", "[StorefrontController::class, 'newsletter'])->middleware('throttle:10,1,newsletter:')");p.write_text(s)
p=Path('apps/backend/app/Http/Controllers/StorefrontController.php');s=p.read_text();needle="        return ['products' => $products, 'brands'";insert="""        $sold = [];
        foreach ($commerce->rows('orders') as $order) {
            if (($order['status'] ?? '') === 'Delivered') {
                foreach ($order['lines'] ?? [] as $line) {
                    $sold[$line['product_id']] = ($sold[$line['product_id']] ?? 0) + $line['quantity'];
                }
            }
        }
        foreach ($products as &$product) {
            $product['sold_count'] = $sold[$product['id']] ?? 0;
        }
        unset($product);
        $expose = fn (string $resource, array $fields): array => array_map(fn (array $row): array => array_intersect_key($row, array_flip($fields)), $active($resource));

""";s=s.replace(needle,insert+needle);s=s.replace("'brands' => $active('brands')", "'brands' => $expose('brands', ['id', 'name', 'description', 'image', 'banner'])");s=s.replace("'categories' => $active('categories')", "'categories' => $expose('categories', ['id', 'name', 'slug', 'parent_id', 'description'])");s=s.replace("'banners' => $active('banners')", "'banners' => $expose('banners', ['id', 'name', 'placement', 'headline', 'description', 'image', 'link'])");s=s.replace("'delivery_zones' => $active('delivery-zones')", "'delivery_zones' => $expose('delivery-zones', ['id', 'name', 'country_code', 'county_codes', 'county_names', 'charge', 'free_threshold'])");s=s.replace("'returns' => Record::where('resource', 'returns')->where('data->customer_id', $customer->id)->latest()->get()->map(fn ($row) => $row->row())->all()", "'returns' => Record::where('resource', 'returns')->where('data->customer_id', $customer->id)->latest()->get()->map(fn ($row) => array_intersect_key($row->row(), array_flip(['id', 'order_id', 'reason', 'refund_amount', 'refund_status', 'status', 'created_at'])))->all()");p.write_text(s)
p=Path('apps/storefront/lib/store.ts');s=p.read_text();s=s.replace('stock:number;reserved:number;','stock:number;reserved:number;sold_count?:number;');p.write_text(s)
p=Path('apps/storefront/lib/filter.ts');s=p.read_text();s=s.replace("if(query.sort==='name')", "if(query.sort==='popular')result.sort((a,b)=>(b.sold_count||0)-(a.sold_count||0));if(query.sort==='name')");p.write_text(s)
p=Path('apps/storefront/components/listing.tsx');s=p.read_text();s=s.replace('<option value="newest">','<option value="popular">Best selling</option><option value="newest">');p.write_text(s)
