<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(): View
    {
        $affiliator = Auth::guard('affiliator')->user();
        $affiliator->load('type');

        return view('profile.edit', [
            'affiliator' => $affiliator,
            'payoutAccountCount' => $affiliator->payoutAccounts()->count(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $affiliator = Auth::guard('affiliator')->user();

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'bio' => ['nullable', 'string', 'max:500'],
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
        ]);

        $affiliator->update([
            'name' => $request->name,
            'phone' => $request->phone,
            'bio' => $request->bio,
        ]);

        if ($request->filled('password')) {
            $affiliator->update([
                'password' => $request->password,
            ]);
        }

        return redirect()->route('profile.edit')
            ->with('success', 'Profil berhasil diperbarui.');
    }
}
