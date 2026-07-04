<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WaNotification;
use App\Services\WhatsappNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * WaNotificationController — read-only admin list buat WA notifikasi queued.
 *
 * M2 stub (task t_e5d877f3): cuma list view, no actions. Gateway sender M3+
 * yang akan flip status ke 'sent' atau 'failed' setelah API call ke Fonnte/Wablas.
 *
 * Filter: status (queued/sent/failed), template, search by recipient/order_number.
 * Pagination: 20 per halaman, sort terbaru di atas.
 */
class WaNotificationController extends Controller
{
    public function index(Request $request): View
    {
        $statusFilter = $request->query('status');
        if (! in_array($statusFilter, ['queued', 'sent', 'failed'], true)) {
            $statusFilter = null;
        }

        $templateFilter = (string) $request->query('template', '');
        $search = trim((string) $request->query('q', ''));

        $query = WaNotification::query()
            ->with('order:id,order_number,customer_name')
            ->latest('id');

        if ($statusFilter !== null) {
            $query->where('status', $statusFilter);
        }

        if ($templateFilter !== '') {
            $query->where('template', $templateFilter);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('recipient', 'like', '%'.$search.'%')
                    ->orWhereHas('order', function ($oq) use ($search) {
                        $oq->where('order_number', 'like', '%'.$search.'%')
                            ->orWhere('customer_name', 'like', '%'.$search.'%');
                    });
            });
        }

        $notifications = $query->paginate(20)->withQueryString();

        $stats = [
            'queued' => WaNotification::where('status', 'queued')->count(),
            'sent' => WaNotification::where('status', 'sent')->count(),
            'failed' => WaNotification::where('status', 'failed')->count(),
            'total' => WaNotification::count(),
        ];

        // Distinct template list buat dropdown filter (max 20).
        $templates = WaNotification::query()
            ->select('template')
            ->distinct()
            ->orderBy('template')
            ->limit(20)
            ->pluck('template')
            ->all();

        return view('admin.wa-notifications.index', [
            'notifications' => $notifications,
            'stats' => $stats,
            'templates' => $templates,
            'statusFilter' => $statusFilter,
            'templateFilter' => $templateFilter,
            'search' => $search,
        ]);
    }

    /**
     * Kirim ulang satu notifikasi WhatsApp secara manual (mitigasi gagal kirim).
     * Dipicu dari section "Notifikasi WhatsApp" di detail order. Pesan dibangun
     * ulang dari template + payload tersimpan; status row di-update in-place.
     */
    public function resend(WaNotification $notification, WhatsappNotifier $notifier): RedirectResponse
    {
        if (trim((string) $notification->recipient) === '') {
            return back()->with('error', 'Notifikasi tidak punya nomor tujuan — tidak bisa dikirim ulang.');
        }

        $result = $notifier->resend($notification);

        $message = match ($result->status) {
            'sent' => 'Notifikasi WhatsApp berhasil dikirim ulang.',
            'failed' => 'Gagal mengirim ulang: '.($result->error ?: 'error tidak diketahui').'.',
            default => 'Notifikasi masuk antrean kirim (gateway WhatsApp belum dikonfigurasi).',
        };

        return back()->with($result->status === 'failed' ? 'error' : 'status', $message);
    }
}
