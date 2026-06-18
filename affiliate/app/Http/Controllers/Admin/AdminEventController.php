<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AffiliateEvent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminEventController extends Controller
{
    public function index(): View
    {
        $events = AffiliateEvent::withCount('participants')
            ->latest()
            ->paginate(15);

        return view('admin.events.index', compact('events'));
    }

    public function create(): View
    {
        return view('admin.events.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:challenge,contest,bonus',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'status' => 'required|in:draft,active,ended',
            'rewards_json' => 'nullable|string',
        ]);

        $rewards = $this->parseRewards($request->rewards_json);
        if ($rewards === false) {
            return back()->withInput()->withErrors(['rewards_json' => 'Format JSON rewards tidak valid. Gunakan format array of object dengan key: rank, reward_type, reward_value, description.']);
        }

        AffiliateEvent::create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'type' => $validated['type'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'status' => $validated['status'],
            'rewards' => $rewards,
        ]);

        return redirect()->route('admin.events.index')
            ->with('success', 'Event berhasil dibuat.');
    }

    public function edit(AffiliateEvent $event): View
    {
        return view('admin.events.edit', compact('event'));
    }

    public function update(Request $request, AffiliateEvent $event): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:challenge,contest,bonus',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'status' => 'required|in:draft,active,ended',
            'rewards_json' => 'nullable|string',
        ]);

        $rewards = $this->parseRewards($request->rewards_json);
        if ($rewards === false) {
            return back()->withInput()->withErrors(['rewards_json' => 'Format JSON rewards tidak valid. Gunakan format array of object dengan key: rank, reward_type, reward_value, description.']);
        }

        $event->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'type' => $validated['type'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'status' => $validated['status'],
            'rewards' => $rewards,
        ]);

        return redirect()->route('admin.events.index')
            ->with('success', 'Event berhasil diperbarui.');
    }

    public function destroy(AffiliateEvent $event): RedirectResponse
    {
        $event->delete();

        return redirect()->route('admin.events.index')
            ->with('success', 'Event berhasil dihapus.');
    }

    public function activate(AffiliateEvent $event): RedirectResponse
    {
        if ($event->status !== 'draft') {
            return back()->with('error', 'Hanya event berstatus draft yang dapat diaktifkan.');
        }

        $event->update(['status' => 'active']);

        return redirect()->route('admin.events.index')
            ->with('success', 'Event berhasil diaktifkan.');
    }

    /**
     * Parse rewards JSON string. Returns array on success, false on invalid JSON.
     *
     * @return array<int, array<string, mixed>>|false
     */
    private function parseRewards(?string $json): array|false
    {
        if (empty($json)) {
            return [];
        }

        $decoded = json_decode($json, true);

        if (! is_array($decoded)) {
            return false;
        }

        $validTypes = ['cash', 'voucher', 'badge', 'bonus_commission'];

        foreach ($decoded as $item) {
            if (! is_array($item)) {
                return false;
            }
            if (! isset($item['rank'], $item['reward_type'], $item['reward_value'])) {
                return false;
            }
            if (! is_numeric($item['rank']) || (int) $item['rank'] < 1) {
                return false;
            }
            if (! in_array($item['reward_type'], $validTypes, true)) {
                return false;
            }
            if (! is_numeric($item['reward_value']) || $item['reward_value'] < 0) {
                return false;
            }
        }

        return $decoded;
    }
}
