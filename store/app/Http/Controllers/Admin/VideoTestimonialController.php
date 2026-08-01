<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\VideoTestimonialRequest;
use App\Models\VideoTestimonial;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VideoTestimonialController extends Controller
{
    public function index(Request $request): View
    {
        $filterStatus = $request->query('status');
        $search = trim((string) $request->query('q', ''));

        $query = VideoTestimonial::query()
            ->orderBy('sort_order')
            ->orderByDesc('id');

        if (in_array($filterStatus, ['draft', 'active', 'archived'], true)) {
            $query->where('status', $filterStatus);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('participant_name', 'like', "%{$search}%");
            });
        }

        $videoTestimonials = $query->paginate(20)->withQueryString();

        $stats = [
            'total' => VideoTestimonial::count(),
            'active' => VideoTestimonial::where('status', 'active')->count(),
            'draft' => VideoTestimonial::where('status', 'draft')->count(),
            'archived' => VideoTestimonial::where('status', 'archived')->count(),
            'homepage' => VideoTestimonial::visibleOnHomepage()->count(),
        ];

        return view('admin.video-testimonials.index', [
            'videoTestimonials' => $videoTestimonials,
            'stats' => $stats,
            'filterStatus' => $filterStatus,
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        return view('admin.video-testimonials.create', [
            'videoTestimonial' => new VideoTestimonial([
                'status' => 'active',
                'role' => 'Alumni AMC',
                'show_on_homepage' => true,
                'sort_order' => VideoTestimonial::max('sort_order') + 1,
            ]),
        ]);
    }

    public function store(VideoTestimonialRequest $request): RedirectResponse
    {
        $videoTestimonial = VideoTestimonial::create($request->validated());

        return redirect()
            ->route('admin.video-testimonials.index')
            ->with('status', "Video testimoni \"{$videoTestimonial->title}\" berhasil ditambahkan.");
    }

    public function edit(VideoTestimonial $videoTestimonial): View
    {
        return view('admin.video-testimonials.edit', [
            'videoTestimonial' => $videoTestimonial,
        ]);
    }

    public function update(VideoTestimonialRequest $request, VideoTestimonial $videoTestimonial): RedirectResponse
    {
        $videoTestimonial->update($request->validated());

        return redirect()
            ->route('admin.video-testimonials.index')
            ->with('status', "Video testimoni \"{$videoTestimonial->title}\" berhasil diperbarui.");
    }

    public function destroy(VideoTestimonial $videoTestimonial): RedirectResponse
    {
        $title = $videoTestimonial->title;
        $videoTestimonial->delete();

        return redirect()
            ->route('admin.video-testimonials.index')
            ->with('status', "Video testimoni \"{$title}\" berhasil dihapus dari daftar.");
    }
}
