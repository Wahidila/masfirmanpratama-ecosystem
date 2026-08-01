<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCourseRequest;
use App\Http\Requests\Admin\UpdateCourseRequest;
use App\Models\Course;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CourseController extends Controller
{
    /** Kolom yang boleh dipakai sorting (whitelist — cegah SQL injection via ?sort). */
    public const SORTABLE = ['title', 'price', 'created_at'];

    /**
     * Quick stats + listing course dengan optional filter status, search, sorting,
     * dan view (active=default, trashed=onlyTrashed for archived view).
     */
    public function index(Request $request): View
    {
        $view = $request->query('view', 'active'); // 'active' (default) | 'trashed'

        $query = Course::query();
        if ($view === 'trashed') {
            $query->onlyTrashed();
        }
        $this->applyFilters($query, $request);

        // Sorting whitelist; default = terbaru (id desc).
        [$sort, $dir] = $this->resolveSort($request);
        if ($sort !== null) {
            $query->orderBy($sort, $dir);
        } else {
            $query->latest('id');
        }

        $courses = $query->paginate(20)->withQueryString();

        $stats = [
            'total' => Course::count(),
            'active' => Course::where('status', 'active')->count(),
            'draft' => Course::where('status', 'draft')->count(),
            'archived' => Course::where('status', 'archived')->count(),
            'trashed' => Course::onlyTrashed()->count(),
        ];

        return view('admin.courses.index', [
            'courses' => $courses,
            'stats' => $stats,
            'filterStatus' => $request->query('status'),
            'search' => trim((string) $request->query('q', '')),
            'view' => $view,
            'sort' => $sort,
            'dir' => $dir,
            'filteredTotal' => $courses->total(),
        ]);
    }

    /**
     * Terapkan filter status/search ke query. Dipakai bersama index() (listing) &
     * bulk() (mode "pilih semua sesuai filter"). TIDAK menangani view/trashed —
     * caller yang atur (onlyTrashed) sesuai konteks aksi. input() supaya jalan
     * untuk GET (listing) & POST bulk (filter datang sebagai body form).
     */
    protected function applyFilters(Builder $query, Request $request): Builder
    {
        $status = $request->input('status');
        if (in_array($status, ['draft', 'active', 'archived'], true)) {
            $query->where('status', $status);
        }

        $search = trim((string) $request->input('q', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    /**
     * Resolve sort+dir dengan whitelist. Return [null, dir] kalau kolom tak valid.
     *
     * @return array{0: ?string, 1: string}
     */
    protected function resolveSort(Request $request): array
    {
        $dir = strtolower((string) $request->query('dir')) === 'asc' ? 'asc' : 'desc';
        $sort = $request->query('sort');

        return in_array($sort, self::SORTABLE, true) ? [$sort, $dir] : [null, $dir];
    }

    public function create(): View
    {
        return view('admin.courses.create', [
            'course' => new Course(['status' => 'draft', 'installment_available' => true]),
        ]);
    }

    public function store(StoreCourseRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $course = new Course;
        $course->fill([
            'title' => $data['title'],
            'slug' => $data['slug'],
            'subtitle' => $data['subtitle'] ?? null,
            'price' => $data['price'],
            'original_price' => $data['original_price'] ?? null,
            'status' => $data['status'],
            'badge' => $data['badge'] ?? null,
            'badge_icon' => $data['badge_icon'] ?? null,
            'category_label' => $data['category_label'] ?? null,
            'rating' => $data['rating'] ?? null,
            'student_count' => $data['student_count'] ?? null,
            'tagline' => $data['tagline'] ?? null,
            'installment_available' => $data['installment_available'] ?? false,
            'description' => $data['description'] ?? null,
            'syllabus' => $data['syllabus'] ?? null,
            'schedule' => $data['schedule'] ?? null,
            'benefits' => $data['benefits'] ?? null,
            'testimonials' => $data['testimonials'] ?? null,
            'related' => $data['related'] ?? null,
            'meta_seo' => $this->buildMetaSeo($data),
            'sort_order' => $data['sort_order'] ?? 0,
            'show_on_homepage' => $data['show_on_homepage'] ?? false,
            'card_features' => $data['card_features'] ?? null,
            'card_icon' => $data['card_icon'] ?? null,
            'card_icon_color' => $data['card_icon_color'] ?? null,
            'card_style' => $data['card_style'] ?? 'default',
            'cta_label' => $data['cta_label'] ?? null,
        ]);

        if ($request->hasFile('image')) {
            $course->image_path = $this->storeImage($request, $data['slug']);
        }

        $course->save();

        return redirect()
            ->route('admin.courses.index')
            ->with('status', "Kursus \"{$course->title}\" berhasil ditambahkan.");
    }

    public function edit(Course $course): View
    {
        return view('admin.courses.edit', [
            'course' => $course,
        ]);
    }

    public function update(UpdateCourseRequest $request, Course $course): RedirectResponse
    {
        $data = $request->validated();

        $course->fill([
            'title' => $data['title'],
            'slug' => $data['slug'],
            'subtitle' => $data['subtitle'] ?? null,
            'price' => $data['price'],
            'original_price' => $data['original_price'] ?? null,
            'status' => $data['status'],
            'badge' => $data['badge'] ?? null,
            'badge_icon' => $data['badge_icon'] ?? null,
            'category_label' => $data['category_label'] ?? null,
            'rating' => $data['rating'] ?? null,
            'student_count' => $data['student_count'] ?? null,
            'tagline' => $data['tagline'] ?? null,
            'installment_available' => $data['installment_available'] ?? false,
            'description' => $data['description'] ?? null,
            'syllabus' => $data['syllabus'] ?? null,
            'schedule' => $data['schedule'] ?? null,
            'benefits' => $data['benefits'] ?? null,
            'testimonials' => $data['testimonials'] ?? null,
            'related' => $data['related'] ?? null,
            'meta_seo' => $this->buildMetaSeo($data),
            'sort_order' => $data['sort_order'] ?? 0,
            'show_on_homepage' => $data['show_on_homepage'] ?? false,
            'card_features' => $data['card_features'] ?? null,
            'card_icon' => $data['card_icon'] ?? null,
            'card_icon_color' => $data['card_icon_color'] ?? null,
            'card_style' => $data['card_style'] ?? 'default',
            'cta_label' => $data['cta_label'] ?? null,
        ]);

        // Replace image
        if ($request->hasFile('image')) {
            $this->deleteImage($course->image_path);
            $course->image_path = $this->storeImage($request, $data['slug']);
        } elseif (! empty($data['remove_image'])) {
            $this->deleteImage($course->image_path);
            $course->image_path = null;
        }

        $course->save();

        return redirect()
            ->route('admin.courses.index')
            ->with('status', "Kursus \"{$course->title}\" berhasil diperbarui.");
    }

    public function destroy(Course $course): RedirectResponse
    {
        $title = $course->title;
        $course->delete(); // soft delete (deleted_at terisi)

        return redirect()
            ->route('admin.courses.index')
            ->with('status', "Kursus \"{$title}\" dipindahkan ke arsip (soft delete).");
    }

    /**
     * Restore course yang sudah soft-deleted.
     * Route: POST /admin/courses/{slug}/restore
     */
    public function restore(string $slug): RedirectResponse
    {
        $course = Course::onlyTrashed()->where('slug', $slug)->firstOrFail();
        $course->restore();

        return redirect()
            ->route('admin.courses.index')
            ->with('status', "Kursus \"{$course->title}\" berhasil dipulihkan.");
    }

    /**
     * Bulk action di index list. Format request: action + ids[].
     * Action: archive (status='archived'), activate (status='active'),
     *         soft_delete (delete()), restore (restore()), force_delete (force).
     */
    public function bulk(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', 'string', 'in:archive,activate,soft_delete,restore,force_delete'],
            'select_all' => ['nullable', 'boolean'],
            // ids wajib KECUALI mode "pilih semua sesuai filter". max:1000 cegah
            // silent-truncate PHP max_input_vars saat seleksi manual sangat besar.
            'ids' => [Rule::requiredIf(fn () => ! $request->boolean('select_all')), 'array', 'max:1000'],
            'ids.*' => ['integer', 'min:1'],
        ]);

        $action = $data['action'];
        $selectAll = $request->boolean('select_all');

        // Untuk restore/force_delete kita perlu trashed records, action lain pakai active set
        $base = in_array($action, ['restore', 'force_delete'], true)
            ? Course::onlyTrashed()
            : Course::query();

        if ($selectAll) {
            // Semua kelas yang cocok filter SAAT INI (bukan cuma 20 di halaman).
            $courses = $this->applyFilters($base, $request)->get();
        } else {
            $courses = $base->whereIn('id', $data['ids'] ?? [])->get();
        }

        $count = $courses->count();

        if ($count === 0) {
            return back()->with('status', 'Tidak ada kursus yang cocok untuk diproses.');
        }

        $message = match ($action) {
            'archive' => $this->bulkUpdateStatus($courses, 'archived'),
            'activate' => $this->bulkUpdateStatus($courses, 'active'),
            'soft_delete' => $this->bulkSoftDelete($courses),
            'restore' => $this->bulkRestore($courses),
            'force_delete' => $this->bulkForceDelete($courses),
            default => 'Aksi tidak dikenal.',
        };

        return redirect()
            ->route('admin.courses.index', $request->only(['view', 'status', 'q', 'sort', 'dir']))
            ->with('status', $message);
    }

    /**
     * Toggle status cepat dari list tanpa buka halaman Edit: active <-> archived,
     * draft -> active (publish). Tidak menyentuh soft-delete.
     */
    public function toggleStatus(Request $request, Course $course): RedirectResponse
    {
        $course->status = $course->status === 'active' ? 'archived' : 'active';
        $course->save();

        $label = $course->status === 'active' ? 'diaktifkan' : 'diarsipkan';

        return back()->with('status', "Kelas \"{$course->title}\" {$label}.");
    }

    /**
     * @param  Collection<int, Course>  $courses
     */
    protected function bulkUpdateStatus($courses, string $status): string
    {
        foreach ($courses as $course) {
            $course->status = $status;
            $course->save();
        }

        $label = $status === 'active' ? 'diaktifkan' : 'di-archive';

        return "{$courses->count()} kursus berhasil {$label}.";
    }

    /**
     * @param  Collection<int, Course>  $courses
     */
    protected function bulkSoftDelete($courses): string
    {
        foreach ($courses as $course) {
            $course->delete();
        }

        return "{$courses->count()} kursus dipindahkan ke arsip (soft delete).";
    }

    /**
     * @param  Collection<int, Course>  $courses
     */
    protected function bulkRestore($courses): string
    {
        foreach ($courses as $course) {
            $course->restore();
        }

        return "{$courses->count()} kursus berhasil dipulihkan.";
    }

    /**
     * @param  Collection<int, Course>  $courses
     */
    protected function bulkForceDelete($courses): string
    {
        $count = 0;
        foreach ($courses as $course) {
            // Cleanup image dulu sebelum hard delete
            $this->deleteImage($course->image_path);
            $course->forceDelete();
            $count++;
        }

        return "{$count} kursus dihapus permanen.";
    }

    // ---------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, string>|null
     */
    protected function buildMetaSeo(array $data): ?array
    {
        $title = $data['meta_title'] ?? null;
        $desc = $data['meta_description'] ?? null;

        if (! $title && ! $desc) {
            return null;
        }

        return array_filter([
            'title' => $title,
            'description' => $desc,
        ], fn ($v) => $v !== null && $v !== '');
    }

    /**
     * Simpan file gambar ke public disk under courses/{slug}.
     * Pakai random hex filename biar tidak trust input client.
     */
    protected function storeImage(Request $request, string $slug): string
    {
        $file = $request->file('image');
        $ext = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'jpg');
        $filename = bin2hex(random_bytes(8)).'.'.$ext;

        $path = $file->storeAs("courses/{$slug}", $filename, 'public');

        // Prepend storage/ so asset($image_path) resolves to /storage/courses/...
        return 'storage/'.$path;
    }

    protected function deleteImage(?string $path): void
    {
        if (! $path) {
            return;
        }

        // Strip storage/ prefix before addressing the disk (disk uses paths relative to storage/app/public)
        $diskPath = str_starts_with($path, 'storage/') ? substr($path, 8) : $path;

        if (Storage::disk('public')->exists($diskPath)) {
            Storage::disk('public')->delete($diskPath);
        }
    }
}
