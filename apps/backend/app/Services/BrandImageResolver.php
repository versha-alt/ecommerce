<?php

namespace App\Services;

use App\Models\CommerceRecord as Record;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BrandImageResolver
{
    public function ensure(Record $brand, bool $refresh = false): array
    {
        $data = $brand->data;
        if (! empty($data['image'])) {
            return $this->payload($data['image'], null, null, null);
        }
        if (! $refresh && ! empty($data['fetched_image'])) {
            return $this->payload($data['fetched_image'], $data['image_source'] ?? null, $data['image_author'] ?? null, $data['image_source_link'] ?? null);
        }

        $dataWithId = $data + ['id' => $brand->id];
        foreach ($this->candidates($dataWithId) as $candidate) {
            $stored = $this->store($candidate['url'] ?? '', $brand->id);
            if (! $stored) {
                continue;
            }
            $data['fetched_image'] = $stored;
            $data['image_source'] = $candidate['source'] ?? '';
            $data['image_author'] = $candidate['author'] ?? '';
            $data['image_source_link'] = $candidate['link'] ?? '';
            $data['image_fetched_at'] = now()->toISOString();
            $brand->data = $data;
            $brand->version++;
            $brand->save();
            Cache::forget('commerce.records.brands');

            return $this->payload($stored, $data['image_source'], $data['image_author'], $data['image_source_link']);
        }

        return $this->payload(null, null, null, null);
    }

    private function payload(?string $image, ?string $source, ?string $author, ?string $link): array
    {
        return ['image' => $image, 'source' => $source, 'author' => $author, 'link' => $link];
    }

    private function candidates(array $brand): array
    {
        $items = [];
        if (! empty($brand['website']) && ($og = $this->websiteImage($brand['website']))) {
            $items[] = ['url' => $og, 'source' => 'Brand website', 'author' => $brand['name'] ?? '', 'link' => $brand['website']];
        }
        foreach ($this->queries($brand) as $query) {
            if ($pexels = $this->pexels($query)) {
                $items[] = $pexels;
            }
            if ($unsplash = $this->unsplash($query)) {
                $items[] = $unsplash;
            }
            if ($pixabay = $this->pixabay($query)) {
                $items[] = $pixabay;
            }
        }

        return $items;
    }

    private function queries(array $brand): array
    {
        $queries = [$brand['name'] ?? '', trim(($brand['name'] ?? '').' appliances')];
        $products = Record::where('resource', 'products')->get()->filter(fn ($product) => ($product->data['status'] ?? '') === 'Active' && ($product->data['brand_id'] ?? '') === ($brand['id'] ?? ''));
        $categoryIds = $products->flatMap(fn ($product) => $product->data['category_ids'] ?? [])->unique()->take(3);
        $categories = Record::where('resource', 'categories')->whereIn('id', $categoryIds)->get()->pluck('data')->pluck('name')->filter()->all();
        foreach ($categories as $category) {
            $queries[] = trim(($brand['name'] ?? '').' '.$category);
            $queries[] = $category;
        }

        return array_values(array_unique(array_filter($queries)));
    }

    private function websiteImage(string $website): ?string
    {
        try {
            $response = Http::timeout(5)->withHeaders(['User-Agent' => 'LeekavBrandImageBot/1.0'])->get($website);
            if (! $response->ok()) {
                return null;
            }
            if (! preg_match('~<meta[^>]+property=["\']og:image["\'][^>]+content=["\']([^"\']+)["\']~i', $response->body(), $match)
                && ! preg_match('~<meta[^>]+content=["\']([^"\']+)["\'][^>]+property=["\']og:image["\']~i', $response->body(), $match)) {
                return null;
            }

            return $this->absoluteUrl($match[1], $website);
        } catch (\Throwable) {
            return null;
        }
    }

    private function pexels(string $query): ?array
    {
        $key = env('PEXELS_API_KEY');
        if (! $key) {
            return null;
        }
        try {
            $photo = Http::timeout(6)->withToken($key)->get('https://api.pexels.com/v1/search', ['query' => $query, 'orientation' => 'landscape', 'size' => 'large', 'per_page' => 1])->json('photos.0');
            if (! $photo) {
                return null;
            }

            return ['url' => $photo['src']['large2x'] ?? $photo['src']['original'] ?? '', 'source' => 'Pexels', 'author' => $photo['photographer'] ?? '', 'link' => $photo['url'] ?? ''];
        } catch (\Throwable) {
            return null;
        }
    }

    private function unsplash(string $query): ?array
    {
        $key = env('UNSPLASH_ACCESS_KEY');
        if (! $key) {
            return null;
        }
        try {
            $photo = Http::timeout(6)->get('https://api.unsplash.com/search/photos', ['client_id' => $key, 'query' => $query, 'orientation' => 'landscape', 'per_page' => 1, 'content_filter' => 'high'])->json('results.0');
            if (! $photo) {
                return null;
            }
            $url = ($photo['urls']['raw'] ?? $photo['urls']['full'] ?? '').(str_contains($photo['urls']['raw'] ?? '', '?') ? '&' : '?').'w=1600&fit=crop';

            return ['url' => $url, 'source' => 'Unsplash', 'author' => $photo['user']['name'] ?? '', 'link' => $photo['links']['html'] ?? ''];
        } catch (\Throwable) {
            return null;
        }
    }

    private function pixabay(string $query): ?array
    {
        $key = env('PIXABAY_API_KEY');
        if (! $key) {
            return null;
        }
        try {
            $photo = Http::timeout(6)->get('https://pixabay.com/api/', ['key' => $key, 'q' => $query, 'image_type' => 'photo', 'orientation' => 'horizontal', 'min_width' => 1600, 'safesearch' => 'true', 'per_page' => 3])->json('hits.0');
            if (! $photo) {
                return null;
            }

            return ['url' => $photo['largeImageURL'] ?? $photo['webformatURL'] ?? '', 'source' => 'Pixabay', 'author' => $photo['user'] ?? '', 'link' => $photo['pageURL'] ?? ''];
        } catch (\Throwable) {
            return null;
        }
    }

    private function store(string $url, string $brandId): ?string
    {
        if (! preg_match('~^https?://~i', $url)) {
            return null;
        }
        try {
            $response = Http::timeout(8)->withHeaders(['Accept' => 'image/avif,image/webp,image/png,image/jpeg,*/*'])->get($url);
            if (! $response->ok() || strlen($response->body()) > 12000000) {
                return null;
            }
            $size = @getimagesizefromstring($response->body());
            if (! $size || ! str_starts_with($size['mime'] ?? '', 'image/')) {
                return null;
            }
            Storage::disk('local')->makeDirectory('uploads');
            if (! function_exists('imagecreatefromstring') || ! function_exists('imagewebp')) {
                $extension = match ($size['mime'] ?? '') {
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/webp' => 'webp',
                    default => null,
                };
                if (! $extension) {
                    return null;
                }
                $name = (string) Str::uuid().'.'.$extension;
                Storage::disk('local')->put('uploads/'.$name, $response->body());

                return '/api/v1/media/'.$name;
            }
            $source = @imagecreatefromstring($response->body());
            if (! $source) {
                return null;
            }
            $width = 1600;
            $height = max(1, (int) round($size[1] * ($width / $size[0])));
            $image = imagecreatetruecolor($width, $height);
            imagefill($image, 0, 0, imagecolorallocate($image, 43, 47, 54));
            imagecopyresampled($image, $source, 0, 0, 0, 0, $width, $height, $size[0], $size[1]);
            $name = (string) Str::uuid().'.webp';
            imagewebp($image, Storage::disk('local')->path('uploads/'.$name), 82);
            imagedestroy($image);
            imagedestroy($source);

            return '/api/v1/media/'.$name;
        } catch (\Throwable) {
            return null;
        }
    }

    private function absoluteUrl(string $url, string $base): string
    {
        if (preg_match('~^https?://~i', $url)) {
            return $url;
        }
        $parts = parse_url($base);
        $origin = ($parts['scheme'] ?? 'https').'://'.($parts['host'] ?? '');
        if (str_starts_with($url, '//')) {
            return ($parts['scheme'] ?? 'https').':'.$url;
        }

        return $origin.'/'.ltrim($url, '/');
    }
}
