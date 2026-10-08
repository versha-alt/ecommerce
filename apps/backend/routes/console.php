<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('brand:image:fetch {brand : Brand id, exact name, or slug} {--refresh : Ignore any previously fetched image}', function () {
    $needle = $this->argument('brand');
    $brand = \App\Models\CommerceRecord::where('resource', 'brands')->get()->first(function ($record) use ($needle) {
        $data = $record->data;

        return $record->id === $needle || strcasecmp($data['name'] ?? '', $needle) === 0 || Str::slug($data['name'] ?? '') === $needle;
    });
    if (! $brand) {
        $this->error('Brand not found.');

        return 1;
    }
    $hero = app(\App\Services\BrandImageResolver::class)->ensure($brand, (bool) $this->option('refresh'));
    $this->info(($brand->data['name'] ?? 'Brand').': '.($hero['image'] ?: 'no image found'));
    if ($hero['source']) {
        $this->line('Source: '.$hero['source'].($hero['author'] ? ' / '.$hero['author'] : ''));
    }

    return 0;
})->purpose('Fetch and cache a storefront brand banner image');
