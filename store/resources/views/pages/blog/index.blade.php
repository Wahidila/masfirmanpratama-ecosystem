@php
    $pageTitle = $activeCategory
        ? $activeCategory->name.' — Blog Firman Pratama'
        : 'Blog — Wawasan Mind Power & Life Mastery';
    $pageDescription = $activeCategory
        ? 'Kumpulan artikel kategori '.$activeCategory->name.' dari Firman Pratama.'
        : 'Artikel & wawasan seputar Mind Power, kekuatan pikiran, kekayaan, dan pengembangan diri dari Firman Pratama.';
@endphp

<x-layouts.store :title="$pageTitle" :description="$pageDescription" ogType="website">
    <section class="bg-gradient-to-b from-primary-50/60 to-transparent">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-16">
            <nav class="text-sm text-slate-500 mb-3" aria-label="Breadcrumb">
                <a href="{{ url('/') }}" class="hover:text-primary-600">Beranda</a>
                <span class="mx-1.5">/</span>
                <a href="{{ route('blog.index') }}" class="hover:text-primary-600">Blog</a>
                @if ($activeCategory)
                    <span class="mx-1.5">/</span>
                    <span class="text-slate-700">{{ $activeCategory->name }}</span>
                @endif
            </nav>
            <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-slate-900">
                {{ $activeCategory ? $activeCategory->name : 'Blog & Artikel' }}
            </h1>
            <p class="mt-3 max-w-2xl text-slate-600">
                {{ $activeCategory?->description ?: 'Wawasan seputar kekuatan pikiran, kekayaan, keluarga, dan pengembangan diri.' }}
            </p>
        </div>
    </section>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-16 lg:grid lg:grid-cols-12 lg:gap-10">
        {{-- Main column --}}
        <div class="lg:col-span-8">
            @if ($search)
                <p class="mb-6 text-sm text-slate-500">
                    Hasil pencarian untuk "<span class="font-medium text-slate-700">{{ $search }}</span>" — {{ $posts->total() }} artikel.
                </p>
            @endif

            @forelse ($posts as $post)
                <article class="group mb-8 overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-sm transition hover:shadow-md">
                    <a href="{{ route('blog.show', $post->slug) }}" class="block">
                        <div class="aspect-[16/9] w-full overflow-hidden bg-slate-100">
                            @if ($post->image_path)
                                <img src="{{ $post->imageUrl() }}" alt="{{ $post->title }}" loading="lazy"
                                    class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.02]">
                            @else
                                <div class="flex h-full w-full items-center justify-center text-slate-300">
                                    <i data-lucide="image" class="w-10 h-10"></i>
                                </div>
                            @endif
                        </div>
                    </a>
                    <div class="p-5 sm:p-6">
                        <div class="mb-2 flex items-center gap-2 text-xs text-slate-500">
                            @php $chip = $post->primaryCategory ?? $post->categories->first(); @endphp
                            @if ($chip)
                                <a href="{{ route('blog.index', ['category' => $chip->slug]) }}"
                                    class="inline-flex rounded-full bg-primary-50 px-2.5 py-0.5 font-medium text-primary-700 hover:bg-primary-100">{{ $chip->name }}</a>
                                <span aria-hidden="true">·</span>
                            @endif
                            <time datetime="{{ optional($post->published_at)->toDateString() }}">{{ optional($post->published_at)->translatedFormat('d M Y') }}</time>
                            @if ($post->reading_minutes)
                                <span aria-hidden="true">·</span>
                                <span>{{ $post->reading_minutes }} mnt baca</span>
                            @endif
                        </div>
                        <h2 class="text-xl font-bold leading-snug text-slate-900">
                            <a href="{{ route('blog.show', $post->slug) }}" class="hover:text-primary-600">{{ $post->title }}</a>
                        </h2>
                        @if ($post->excerpt)
                            <p class="mt-2 line-clamp-3 text-sm text-slate-600">{{ $post->excerpt }}</p>
                        @endif
                        <a href="{{ route('blog.show', $post->slug) }}"
                            class="mt-4 inline-flex items-center gap-1 text-sm font-semibold text-primary-600 hover:text-primary-700">
                            Baca selengkapnya
                            <i data-lucide="arrow-right" class="w-4 h-4"></i>
                        </a>
                    </div>
                </article>
            @empty
                <div class="rounded-2xl border border-dashed border-slate-200 bg-white p-12 text-center">
                    <p class="text-slate-500">Belum ada artikel{{ $activeCategory ? ' di kategori ini' : '' }}.</p>
                    @if ($search || $activeCategory)
                        <a href="{{ route('blog.index') }}" class="mt-3 inline-block text-sm font-semibold text-primary-600 hover:text-primary-700">← Lihat semua artikel</a>
                    @endif
                </div>
            @endforelse

            @if ($posts->hasPages())
                <div class="mt-8">{{ $posts->withQueryString()->links() }}</div>
            @endif
        </div>

        {{-- Sidebar --}}
        <aside class="mt-12 lg:col-span-4 lg:mt-0">
            <div class="lg:sticky lg:top-28 space-y-8">
                {{-- Search --}}
                <div class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm">
                    <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Cari Artikel</h3>
                    <form method="GET" action="{{ route('blog.index') }}" class="flex gap-2">
                        <input type="text" name="q" value="{{ $search }}" placeholder="Ketik kata kunci…"
                            class="h-10 w-full rounded-lg border border-slate-200 px-3 text-sm text-slate-800 focus:border-primary-400 focus:ring-2 focus:ring-primary-100 focus:outline-none">
                        <button type="submit" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary-600 text-white hover:bg-primary-700" aria-label="Cari">
                            <i data-lucide="search" class="w-4 h-4"></i>
                        </button>
                    </form>
                </div>

                {{-- Categories --}}
                <div class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm">
                    <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Kategori</h3>
                    <ul class="space-y-1">
                        <li>
                            <a href="{{ route('blog.index') }}"
                                class="flex items-center justify-between rounded-lg px-3 py-2 text-sm transition {{ ! $activeCategory ? 'bg-primary-50 font-semibold text-primary-700' : 'text-slate-600 hover:bg-slate-50' }}">
                                Semua Artikel
                            </a>
                        </li>
                        @foreach ($categories as $category)
                            <li>
                                <a href="{{ route('blog.index', ['category' => $category->slug]) }}"
                                    class="flex items-center justify-between rounded-lg px-3 py-2 text-sm transition {{ $activeCategory && $activeCategory->id === $category->id ? 'bg-primary-50 font-semibold text-primary-700' : 'text-slate-600 hover:bg-slate-50' }}">
                                    {{ $category->name }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>

                {{-- Recent --}}
                @if ($recentPosts->isNotEmpty())
                    <div class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm">
                        <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Artikel Terbaru</h3>
                        <ul class="space-y-3">
                            @foreach ($recentPosts as $recent)
                                <li>
                                    <a href="{{ route('blog.show', $recent->slug) }}" class="group block">
                                        <p class="text-sm font-medium text-slate-700 group-hover:text-primary-600 line-clamp-2">{{ $recent->title }}</p>
                                        <p class="mt-0.5 text-xs text-slate-400">{{ optional($recent->published_at)->translatedFormat('d M Y') }}</p>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </aside>
    </div>
</x-layouts.store>
