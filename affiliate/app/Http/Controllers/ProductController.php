<?php

namespace App\Http\Controllers;

use App\Models\CommissionSetting;
use App\Services\StoreCatalog;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Halaman "Produk" affiliator — daftar produk (buku + kelas) yang bisa
 * dipromosikan, lengkap dengan rate komisi untuk tipe affiliator ini +
 * tombol langsung buat link referral.
 */
class ProductController extends Controller
{
    public function index(StoreCatalog $catalog): View
    {
        $affiliator = Auth::guard('affiliator')->user();
        $products = $catalog->products();

        // Rate komisi per tipe produk untuk tipe affiliator yang login.
        $rates = [
            'course' => $this->resolveRate($affiliator->affiliator_type_id, 'course'),
            'book' => $this->resolveRate($affiliator->affiliator_type_id, 'book'),
        ];

        return view('products.index', [
            'products' => $products,
            'rates' => $rates,
        ]);
    }

    /**
     * Rate komisi efektif untuk (tipe affiliator, tipe produk) dengan prioritas
     * sama seperti StoreWebhookController: exact → tipe-saja → produk-saja → global.
     */
    private function resolveRate(?int $typeId, string $productType): ?float
    {
        $candidates = [
            ['affiliator_type_id' => $typeId, 'product_type' => $productType],
            ['affiliator_type_id' => $typeId, 'product_type' => null],
            ['affiliator_type_id' => null, 'product_type' => $productType],
            ['affiliator_type_id' => null, 'product_type' => null],
        ];

        foreach ($candidates as $c) {
            $setting = CommissionSetting::where('is_active', true)
                ->where('affiliator_type_id', $c['affiliator_type_id'])
                ->where('product_type', $c['product_type'])
                ->first();

            if ($setting) {
                return (float) $setting->rate;
            }
        }

        return null;
    }
}
