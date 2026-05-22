{{--
    Shared nav links partial — dipakai oleh:
    - components/admin/sidebar.blade.php (desktop, lg:flex)
    - layouts/admin.blade.php → mobile drawer (lg:hidden)

    Required vars (caller wajib pass):
    - $active: string|null (active key)

    Optional vars:
    - $linkClickHandler: extra Alpine attribute string (e.g. "@click=\"open = false\"")
                        untuk drawer auto-close on link click. Default kosong.

    Source data: config/admin-nav.php (config('admin-nav.primary' / 'coming_soon')).
--}}
@php
    $linkClickHandler ??= '';
    $primaryNav = config('admin-nav.primary', []);
    $comingSoon = config('admin-nav.coming_soon', []);
@endphp

@foreach ($primaryNav as $item)
    @php $isActive = $active === $item['key'] || request()->routeIs($item['route']); @endphp
    <a href="{{ route($item['route']) }}"
       {!! $linkClickHandler !!}
       class="flex items-center gap-2.5 rounded-xl px-3 py-2 font-medium transition {{ $isActive ? 'bg-primary-50 text-primary-700' : 'text-slate-700 hover:bg-slate-100' }}">
        <x-admin.icon :name="$item['icon']" class="h-4 w-4 shrink-0" />
        {{ $item['label'] }}
    </a>
@endforeach

@if (! empty($comingSoon))
    <p class="px-3 pt-5 pb-1 text-xs font-medium uppercase tracking-wide text-slate-400">Coming soon</p>

    @foreach ($comingSoon as $item)
        <span class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-slate-400 cursor-not-allowed select-none">
            <x-admin.icon :name="$item['icon']" class="h-4 w-4 shrink-0 opacity-60" />
            {{ $item['label'] }}
        </span>
    @endforeach
@endif
