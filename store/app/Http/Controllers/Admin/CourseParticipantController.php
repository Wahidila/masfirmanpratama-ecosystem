<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CourseParticipantRequest;
use App\Models\Course;
use App\Models\CourseParticipant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CRUD peserta kursus (roster kelas).
 *
 * Peserta dari order kelas terisi otomatis lewat listener SyncCourseParticipant
 * (lunas maupun cicilan berjalan). Controller ini untuk mengelola: filter/cari,
 * tambah manual (peserta offline), edit status/catatan, hapus.
 */
class CourseParticipantController extends Controller
{
    public function index(Request $request): View
    {
        $query = CourseParticipant::query()->with(['course', 'order']);

        if ($search = trim((string) $request->query('q', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($courseId = $request->query('course')) {
            $query->where('course_id', $courseId);
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($payment = $request->query('payment')) {
            $query->where('payment_status', $payment);
        }

        return view('admin.participants.index', [
            'participants' => $query->orderByDesc('joined_at')->orderByDesc('id')
                ->paginate(20)->withQueryString(),
            'courses' => Course::orderBy('title')->get(['id', 'title']),
            'statuses' => CourseParticipant::STATUSES,
            'paymentStatuses' => CourseParticipant::PAYMENT_STATUSES,
            'filters' => $request->only(['q', 'course', 'status', 'payment']),
            'totalAll' => CourseParticipant::count(),
        ]);
    }

    public function create(): View
    {
        return view('admin.participants.create', [
            'participant' => new CourseParticipant(['status' => 'registered', 'payment_status' => 'lunas']),
            'courses' => Course::orderBy('title')->get(['id', 'title']),
            'statuses' => CourseParticipant::STATUSES,
            'paymentStatuses' => CourseParticipant::PAYMENT_STATUSES,
        ]);
    }

    public function store(CourseParticipantRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['joined_at'] = $data['joined_at'] ?? now();

        CourseParticipant::create($data);

        return redirect()
            ->route('admin.participants.index')
            ->with('status', 'Peserta berhasil ditambahkan.');
    }

    public function edit(CourseParticipant $participant): View
    {
        return view('admin.participants.edit', [
            'participant' => $participant->load(['course', 'order']),
            'courses' => Course::orderBy('title')->get(['id', 'title']),
            'statuses' => CourseParticipant::STATUSES,
            'paymentStatuses' => CourseParticipant::PAYMENT_STATUSES,
        ]);
    }

    public function update(CourseParticipantRequest $request, CourseParticipant $participant): RedirectResponse
    {
        $participant->update($request->validated());

        return redirect()
            ->route('admin.participants.index')
            ->with('status', 'Data peserta berhasil diperbarui.');
    }

    public function destroy(CourseParticipant $participant): RedirectResponse
    {
        $participant->delete();

        return redirect()
            ->route('admin.participants.index')
            ->with('status', 'Peserta berhasil dihapus.');
    }
}
