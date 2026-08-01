<?php

namespace App\Http\Controllers;

use App\Models\ReferralClick;
use App\Models\ReferralCode;
use App\Services\StoreCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ReferralController extends Controller
{
    public function track(string $code): RedirectResponse
    {
        $referralCode = ReferralCode::where('code', $code)
            ->where('is_active', true)
            ->first();

        if (! $referralCode) {
            return redirect()->route('landing');
        }

        // Log click
        ReferralClick::create([
            'referral_code_id' => $referralCode->id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'referer_url' => request()->header('referer'),
            'clicked_at' => now(),
        ]);

        // Set referral cookie (30 days)
        $targetUrl = $referralCode->target_url ?: config('app.store_url', 'https://masfirmanpratama.com');

        return redirect()->away($targetUrl)
            ->withCookie(cookie('referral_code', $code, 60 * 24 * 30));
    }

    public function index(Request $request, StoreCatalog $catalog): View
    {
        $affiliator = Auth::guard('affiliator')->user();

        $referrals = $affiliator->referralCodes()
            ->withCount(['clicks', 'orders'])
            ->latest()
            ->paginate(10);

        // Peta URL produk → nama, untuk menampilkan nama produk per link referral.
        $productNames = collect($catalog->products())
            ->mapWithKeys(fn (array $p) => [$p['url'] => $p['title']])
            ->all();

        return view('referrals.index', compact('referrals', 'productNames'));
    }

    public function create(Request $request, StoreCatalog $catalog): View
    {
        return view('referrals.create', [
            'products' => $this->productOptions($catalog),
            'prefillUrl' => (string) $request->query('target_url', ''),
            'prefillLabel' => (string) $request->query('label', ''),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'label' => ['nullable', 'string', 'max:100'],
            'target_url' => ['nullable', 'url', 'max:500'],
        ]);

        $affiliator = Auth::guard('affiliator')->user();

        $code = strtoupper(Str::random(8));
        while (ReferralCode::where('code', $code)->exists()) {
            $code = strtoupper(Str::random(8));
        }

        $affiliator->referralCodes()->create([
            'code' => $code,
            'label' => $request->label,
            'target_url' => $request->target_url,
        ]);

        return redirect()->route('referrals.index')
            ->with('success', 'Link referral berhasil dibuat!');
    }

    public function edit(ReferralCode $referral, StoreCatalog $catalog): View
    {
        $this->authorizeReferral($referral);

        return view('referrals.edit', [
            'referral' => $referral,
            'products' => $this->productOptions($catalog),
        ]);
    }

    /**
     * Ringkas katalog store jadi opsi dropdown produk (url + label + harga).
     *
     * @return array<int, array{url:string,label:string,type:string,price:int}>
     */
    private function productOptions(StoreCatalog $catalog): array
    {
        return collect($catalog->products())
            ->map(fn (array $p) => [
                'url' => $p['url'],
                'label' => $p['title'],
                'type' => $p['type'],
                'price' => $p['price'],
            ])
            ->values()
            ->all();
    }

    public function update(Request $request, ReferralCode $referral): RedirectResponse
    {
        $this->authorizeReferral($referral);

        $request->validate([
            'label' => ['nullable', 'string', 'max:100'],
            'target_url' => ['nullable', 'url', 'max:500'],
        ]);

        $referral->update([
            'label' => $request->label,
            'target_url' => $request->target_url,
        ]);

        return redirect()->route('referrals.index')
            ->with('success', 'Link referral berhasil diperbarui!');
    }

    public function destroy(ReferralCode $referral): RedirectResponse
    {
        $this->authorizeReferral($referral);

        $referral->delete();

        return redirect()->route('referrals.index')
            ->with('success', 'Link referral berhasil dihapus.');
    }

    public function toggle(ReferralCode $referral): RedirectResponse
    {
        $this->authorizeReferral($referral);

        $referral->update(['is_active' => ! $referral->is_active]);

        $status = $referral->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()->route('referrals.index')
            ->with('success', "Link referral berhasil {$status}.");
    }

    private function authorizeReferral(ReferralCode $referral): void
    {
        $affiliator = Auth::guard('affiliator')->user();

        if ($referral->affiliator_id !== $affiliator->id) {
            abort(403);
        }
    }
}
