<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CourseParticipantRequest;
use App\Models\Course;
use App\Models\CourseParticipant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * CRUD peserta kursus (roster kelas).
 *
 * Peserta dari order kelas terisi otomatis lewat listener SyncCourseParticipant
 * (lunas maupun cicilan berjalan). Controller ini untuk mengelola: filter/cari,
 * tambah manual (peserta offline), edit status/catatan, hapus, dan export XLSX.
 */
class CourseParticipantController extends Controller
{
    /** Kolom export (header => resolver). */
    private const EXPORT_COLUMNS = [
        'Nama', 'Email', 'No. WhatsApp', 'Kelas', 'Status Peserta',
        'Status Pembayaran', 'Pekerjaan', 'Motivasi', 'Asal Order',
        'Tanggal Bergabung', 'Catatan',
    ];

    /**
     * Query terfilter — dipakai bersama oleh index & export supaya hasil
     * export selalu sama persis dengan yang tampil di layar.
     */
    private function filteredQuery(Request $request): Builder
    {
        return CourseParticipant::query()
            ->when(trim((string) $request->query('q', '')), function (Builder $query, string $search) {
                $query->where(function (Builder $q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($request->query('course'), fn (Builder $q, $id) => $q->where('course_id', $id))
            ->when($request->query('status'), fn (Builder $q, $s) => $q->where('status', $s))
            ->when($request->query('payment'), fn (Builder $q, $p) => $q->where('payment_status', $p));
    }

    public function index(Request $request): View
    {
        $participants = $this->filteredQuery($request)
            ->with(['course:id,title', 'order:id,order_number'])
            ->orderByDesc('joined_at')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('admin.participants.index', [
            'participants' => $participants,
            'courses' => Course::orderBy('title')->get(['id', 'title']),
            'statuses' => CourseParticipant::STATUSES,
            'paymentStatuses' => CourseParticipant::PAYMENT_STATUSES,
            'filters' => array_filter($request->only(['q', 'course', 'status', 'payment']), fn ($v) => $v !== null && $v !== ''),
        ]);
    }

    /**
     * Export daftar peserta (mengikuti filter aktif) ke XLSX.
     *
     * Dibaca per-chunk lewat lazyById supaya ribuan baris tidak dimuat
     * sekaligus ke memori.
     */
    public function export(Request $request): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Peserta Kursus');

        $sheet->fromArray(self::EXPORT_COLUMNS, null, 'A1');
        $lastColumn = chr(ord('A') + count(self::EXPORT_COLUMNS) - 1);
        $sheet->getStyle("A1:{$lastColumn}1")->getFont()->setBold(true);
        $sheet->freezePane('A2');

        $row = 2;
        $this->filteredQuery($request)
            ->with(['course:id,title', 'order:id,order_number'])
            ->orderBy('id')
            ->lazyById(500)
            ->each(function (CourseParticipant $participant) use ($sheet, &$row) {
                $sheet->fromArray([
                    $participant->name,
                    $participant->email,
                    $participant->phone,
                    $participant->course?->title,
                    $participant->statusLabel(),
                    $participant->paymentStatusLabel(),
                    $participant->occupation,
                    $participant->motivation,
                    $participant->order?->order_number ?? 'Manual',
                    $participant->joined_at?->format('d/m/Y'),
                    $participant->notes,
                ], null, 'A'.$row);
                $row++;
            });

        foreach (range('A', $lastColumn) as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $filename = 'peserta-kursus-'.now()->format('Y-m-d_His').'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
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
