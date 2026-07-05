@props([
    'heads' => [],
])

<div {{ $attributes->merge(['class' => 'overflow-hidden rounded-2xl border border-slate-200/70 bg-white shadow-sm']) }}>
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50/80 border-b border-slate-100">
                @isset($head)
                    {{ $head }}
                @else
                    <tr>
                        @foreach ($heads as $h)
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 whitespace-nowrap">{{ $h }}</th>
                        @endforeach
                    </tr>
                @endisset
            </thead>
            <tbody class="divide-y divide-slate-100">
                {{ $slot }}
            </tbody>
        </table>
    </div>
</div>
