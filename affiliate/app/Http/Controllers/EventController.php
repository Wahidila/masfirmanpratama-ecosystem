<?php

namespace App\Http\Controllers;

use App\Models\AffiliateEvent;
use App\Models\AffiliateEventParticipant;
use App\Models\AffiliateEventReward;
use App\Models\Affiliator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class EventController extends Controller
{
    public function index(): View
    {
        $events = AffiliateEvent::where('status', 'active')
            ->orderBy('end_date')
            ->paginate(10);

        return view('events.index', compact('events'));
    }

    public function show(AffiliateEvent $event): View
    {
        $affiliator = Auth::guard('affiliator')->user();

        $participation = $event->participants()
            ->where('affiliator_id', $affiliator->id)
            ->first();

        $leaderboard = $event->participants()
            ->with('affiliator:id,name,avatar')
            ->orderByDesc('score')
            ->take(20)
            ->get();

        return view('events.show', compact('event', 'participation', 'leaderboard'));
    }

    public function join(AffiliateEvent $event): RedirectResponse
    {
        $affiliator = Auth::guard('affiliator')->user();

        if (! $event->isActive()) {
            return back()->withErrors(['event' => 'Event sudah berakhir atau belum dimulai.']);
        }

        $existing = $event->participants()
            ->where('affiliator_id', $affiliator->id)
            ->exists();

        if ($existing) {
            return back()->withErrors(['event' => 'Anda sudah terdaftar di event ini.']);
        }

        AffiliateEventParticipant::create([
            'affiliate_event_id' => $event->id,
            'affiliator_id' => $affiliator->id,
            'score' => 0,
        ]);

        return back()->with('success', 'Berhasil bergabung dengan event!');
    }

    public function leaderboard(): View
    {
        $topAffiliators = Affiliator::where('status', 'active')
            ->withCount('referralOrders')
            ->withSum('commissions as total_earned', 'amount')
            ->orderByDesc('total_earned')
            ->take(50)
            ->get();

        return view('events.leaderboard', compact('topAffiliators'));
    }

    public function rewards(): View
    {
        $affiliator = Auth::guard('affiliator')->user();

        $rewards = AffiliateEventReward::where('affiliator_id', $affiliator->id)
            ->with('event')
            ->orderByDesc('created_at')
            ->get();

        $unclaimed = $rewards->where('is_claimed', false);
        $claimed = $rewards->where('is_claimed', true);

        return view('events.rewards', compact('unclaimed', 'claimed'));
    }

    public function claimReward(AffiliateEventReward $reward): RedirectResponse
    {
        $affiliator = Auth::guard('affiliator')->user();

        if ($reward->affiliator_id !== $affiliator->id) {
            abort(403);
        }

        if ($reward->is_claimed) {
            return back()->withErrors(['reward' => 'Reward sudah diklaim.']);
        }

        $reward->update([
            'is_claimed' => true,
            'claimed_at' => now(),
        ]);

        return back()->with('success', 'Reward berhasil diklaim!');
    }
}
