@php
    use Illuminate\Support\Str;

    $meta = is_array($post->meta_seo) ? $post->meta_seo : [];
    $metaTitle = $meta['title'] ?? ($post->title.' — Firman Pratama');
    $metaDescription = $meta['description'] ?? ($post->excerpt ?: Str::limit(trim(strip_tags($post->content)), 160));
    $ogImage = $post->image_path ?: null;
    $chip = $post->primaryCategory ?? $post->categories->first();
    $shareUrl = url()->current();
@endphp

<x-layouts.store :title="$metaTitle" :description="$metaDescription" :ogImage="$ogImage" ogType="article">
    <x-slot:head>
        {{-- Self-referential canonical to the new /blog/{slug} URL --}}
        <link rel="canonical" href="{{ $shareUrl }}">
        <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'BlogPosting',
            'headline' => $post->title,
            'description' => $metaDescription,
            'image' => $ogImage ? asset($ogImage) : null,
            'datePublished' => optional($post->published_at)->toIso8601String(),
            'dateModified' => optional($post->updated_at)->toIso8601String(),
            'author' => ['@type' => 'Person', 'name' => 'Firman Pratama'],
            'mainEntityOfPage' => $shareUrl,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
        </script>
        <style>
            .article-body { color: #334155; line-height: 1.75; }
            .article-body > * + * { margin-top: 1.15rem; }
            .article-body h2 { font-size: 1.5rem; font-weight: 700; color: #0f172a; margin-top: 2rem; }
            .article-body h3 { font-size: 1.25rem; font-weight: 700; color: #0f172a; margin-top: 1.75rem; }
            .article-body p { margin-bottom: 0; }
            .article-body a { color: #4f46e5; text-decoration: underline; }
            .article-body ul, .article-body ol { padding-left: 1.4rem; }
            .article-body ul { list-style: disc; }
            .article-body ol { list-style: decimal; }
            .article-body li + li { margin-top: 0.4rem; }
            .article-body img { border-radius: 0.75rem; margin: 1.5rem 0; max-width: 100%; height: auto; }
            .article-body blockquote { border-left: 4px solid #6366f1; padding-left: 1rem; color: #475569; font-style: italic; }
            .article-body h2 + p, .article-body h3 + p { margin-top: 0.5rem; }
        </style>
    </x-slot:head>

    <article class="max-w-3xl mx-auto px-4 sm:px-6 py-10 sm:py-14">
        {{-- Breadcrumb --}}
        <nav class="text-sm text-slate-500 mb-5" aria-label="Breadcrumb">
            <a href="{{ url('/') }}" class="hover:text-primary-600">Beranda</a>
            <span class="mx-1.5">/</span>
            <a href="{{ route('blog.index') }}" class="hover:text-primary-600">Blog</a>
            @if ($chip)
                <span class="mx-1.5">/</span>
                <a href="{{ route('blog.index', ['category' => $chip->slug]) }}" class="hover:text-primary-600">{{ $chip->name }}</a>
            @endif
        </nav>

        {{-- Title + meta (title above image, per brand) --}}
        <header>
            @if ($chip)
                <a href="{{ route('blog.index', ['category' => $chip->slug]) }}"
                    class="inline-flex rounded-full bg-primary-50 px-3 py-1 text-xs font-semibold text-primary-700 hover:bg-primary-100">{{ $chip->name }}</a>
            @endif
            <h1 class="mt-4 text-3xl sm:text-4xl font-extrabold leading-tight tracking-tight text-slate-900">{{ $post->title }}</h1>
            <div class="mt-4 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-slate-500">
                <span class="font-medium text-slate-700">Firman Pratama</span>
                <span aria-hidden="true">·</span>
                <time datetime="{{ optional($post->published_at)->toDateString() }}">{{ optional($post->published_at)->translatedFormat('d F Y') }}</time>
                @if ($post->reading_minutes)
                    <span aria-hidden="true">·</span>
                    <span>{{ $post->reading_minutes }} menit baca</span>
                @endif
            </div>
        </header>

        {{-- Featured image (contained, beneath title) --}}
        @if ($post->image_path)
            <figure class="mt-6 overflow-hidden rounded-2xl bg-slate-100">
                <img src="{{ $post->imageUrl() }}" alt="{{ $post->title }}" class="w-full object-cover">
            </figure>
        @endif

        {{-- Body --}}
        <div class="article-body mt-8 text-[1.05rem]">
            {!! $post->content !!}
        </div>

        {{-- Tags --}}
        @if ($post->tags->isNotEmpty())
            <div class="mt-8 flex flex-wrap gap-2">
                @foreach ($post->tags as $tag)
                    <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs text-slate-600">#{{ $tag->name }}</span>
                @endforeach
            </div>
        @endif

        {{-- Share --}}
        <div class="mt-8 flex items-center gap-3 border-t border-slate-100 pt-6">
            <span class="text-sm font-medium text-slate-500">Bagikan:</span>
            <a href="https://wa.me/?text={{ urlencode($post->title.' '.$shareUrl) }}" target="_blank" rel="noopener"
                class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-slate-100 text-slate-600 hover:bg-primary-50 hover:text-primary-600" aria-label="Bagikan ke WhatsApp">
                <i data-lucide="message-circle" class="w-4 h-4"></i>
            </a>
            <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($shareUrl) }}" target="_blank" rel="noopener"
                class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-slate-100 text-slate-600 hover:bg-primary-50 hover:text-primary-600" aria-label="Bagikan ke Facebook">
                <i data-lucide="facebook" class="w-4 h-4"></i>
            </a>
            <a href="https://twitter.com/intent/tweet?url={{ urlencode($shareUrl) }}&text={{ urlencode($post->title) }}" target="_blank" rel="noopener"
                class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-slate-100 text-slate-600 hover:bg-primary-50 hover:text-primary-600" aria-label="Bagikan ke X">
                <i data-lucide="twitter" class="w-4 h-4"></i>
            </a>
        </div>

        {{-- Related products CTA (funnel) --}}
        @if ($post->products->isNotEmpty())
            <section class="mt-12 rounded-2xl border border-primary-100 bg-primary-50/50 p-6">
                <h2 class="text-lg font-bold text-slate-900">Rekomendasi untuk Anda</h2>
                <p class="mt-1 text-sm text-slate-600">Produk & kelas yang relevan dengan artikel ini.</p>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    @foreach ($post->products as $product)
                        @php
                            $isCourse = $product->type === 'course';
                            $href = $isCourse ? route('courses.show', $product->slug) : route('products.show', $product->slug);
                        @endphp
                        <a href="{{ $href }}" class="group flex items-center gap-4 rounded-xl border border-slate-100 bg-white p-4 transition hover:shadow-md">
                            <div class="h-16 w-16 shrink-0 overflow-hidden rounded-lg bg-slate-100">
                                @if ($product->image_path)
                                    <img src="{{ asset($product->image_path) }}" alt="{{ $product->title }}" class="h-full w-full object-cover">
                                @endif
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs font-medium uppercase text-primary-600">{{ $isCourse ? 'Kelas' : 'Buku' }}</p>
                                <p class="truncate font-semibold text-slate-800 group-hover:text-primary-600">{{ $product->title }}</p>
                                <p class="text-sm text-slate-500">Rp {{ number_format((float) $product->price, 0, ',', '.') }}</p>
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Related posts --}}
        @if ($relatedPosts->isNotEmpty())
            <section class="mt-12">
                <h2 class="text-lg font-bold text-slate-900">Artikel Terkait</h2>
                <div class="mt-4 grid gap-6 sm:grid-cols-3">
                    @foreach ($relatedPosts as $related)
                        <a href="{{ route('blog.show', $related->slug) }}" class="group block">
                            <div class="aspect-[16/9] w-full overflow-hidden rounded-xl bg-slate-100">
                                @if ($related->image_path)
                                    <img src="{{ $related->imageUrl() }}" alt="{{ $related->title }}" loading="lazy" class="h-full w-full object-cover transition group-hover:scale-105">
                                @endif
                            </div>
                            <p class="mt-2 text-sm font-semibold text-slate-800 group-hover:text-primary-600 line-clamp-2">{{ $related->title }}</p>
                            <p class="mt-0.5 text-xs text-slate-400">{{ optional($related->published_at)->translatedFormat('d M Y') }}</p>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Back --}}
        <div class="mt-12">
            <a href="{{ route('blog.index') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-primary-600 hover:text-primary-700">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> Kembali ke Blog
            </a>
        </div>
    </article>
</x-layouts.store>
