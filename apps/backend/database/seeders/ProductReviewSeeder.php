<?php

namespace Database\Seeders;

use App\Models\CommerceRecord as Record;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductReviewSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            Record::where('resource', 'settings')->lockForUpdate()->first();
            $products = Record::where('resource', 'products')->where('data->status', 'Active')->oldest()->get();
            $customers = Record::where('resource', 'customers')->where('data->status', 'Active')->oldest()->get();
            if ($products->isEmpty() || $customers->isEmpty()) {
                throw new \RuntimeException('Sample reviews require at least one active product and customer.');
            }
            $samples = [
                ['Pending', 5, 'Great first impression', 'The product is easy to use and works well for everyday tasks.'],
                ['Pending', 4, 'Good value for money', 'A useful addition to our home. Setup was straightforward.'],
                ['Pending', 3, 'Works well with a few improvements', 'Performance is good, although clearer instructions would help.'],
                ['Pending', 5, 'Very happy with this purchase', 'Reliable performance and a neat design. I would recommend it.'],
                ['Approved', 5, 'Excellent everyday performance', 'This product has been dependable and convenient to use.'],
                ['Approved', 4, 'Solid quality', 'The finish feels good and the controls are easy to understand.'],
                ['Approved', 5, 'A practical choice', 'It does exactly what we needed and fits our space nicely.'],
                ['Approved', 4, 'Happy customer', 'Good performance overall. The included guide was useful.'],
                ['Rejected', 2, 'Sample off-topic review', 'This dummy entry is intended to demonstrate rejected review moderation.'],
                ['Rejected', 1, 'Sample inappropriate submission', 'This dummy entry is not suitable for publication and should remain hidden.'],
            ];
            $created = 0;
            foreach ($samples as $index => [$status, $rating, $title, $body]) {
                $product = $products[$index % $products->count()];
                $customer = $customers[$index % $customers->count()];
                $submittedAt = now()->subDays(10 - $index);
                $history = [['from' => null, 'to' => 'Pending', 'actor' => 'Demo customer submission', 'at' => $submittedAt->toISOString()]];
                if ($status !== 'Pending') {
                    $history[] = ['from' => 'Pending', 'to' => $status, 'actor' => 'Demo moderation', 'at' => $submittedAt->copy()->addHour()->toISOString(), 'note' => 'Sample moderation decision for testing.'];
                }
                $review = Record::firstOrCreate(['id' => 'a10d0000-0000-4000-8000-'.str_pad((string) ($index + 1), 12, '0', STR_PAD_LEFT)], ['resource' => 'reviews', 'created_at' => $submittedAt, 'data' => ['name' => '[Demo] '.$title, 'title' => '[Demo] '.$title, 'body' => $body, 'rating' => $rating, 'product_id' => $product->id, 'product_name' => $product->data['name'], 'customer_id' => $customer->id, 'customer_name' => $customer->data['name'], 'status' => $status, 'notes' => $status === 'Pending' ? '' : 'Sample moderation decision for testing.', 'status_history' => $history, 'demo' => true]]);
                $created += (int) $review->wasRecentlyCreated;
            }
            $this->command?->info("Added {$created} dummy reviews: 4 Pending, 4 Approved, 2 Rejected. Existing reviews preserved.");
        });
    }
}
