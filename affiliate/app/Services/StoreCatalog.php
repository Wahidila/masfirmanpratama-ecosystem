<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Ambil katalog produk (buku + kelas) dari app Store lewat endpoint JSON,
 * di-cache supaya tidak fetch tiap request. Store & Affiliate app/DB terpisah.
 */
class StoreCatalog
{
    private const CACHE_KEY = 'store.catalog.products';

    /**
     * @return array<int, array{type:string,slug:string,title:string,price:int,image_url:?string,url:string}>
     */
    public function products(): array
    {
        $cached = Cache::get(self::CACHE_KEY);
        if (is_array($cached)) {
            return $cached;
        }

        try {
            $base = rtrim(config('app.store_url', 'https://masfirmanpratama.com'), '/');
            $response = Http::timeout(8)->acceptJson()->get($base.'/api/affiliate/products');

            if ($response->successful()) {
                $products = $response->json('products') ?? [];
                // Cache hanya hasil sukses (jangan cache kegagalan/kosong lama-lama).
                Cache::put(self::CACHE_KEY, $products, now()->addMinutes(30));

                return $products;
            }
        } catch (\Throwable $e) {
            Log::warning('Gagal ambil katalog store: '.$e->getMessage());
        }

        return [];
    }

    public function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
