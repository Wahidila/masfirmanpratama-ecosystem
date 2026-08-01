@props([
    'columns' => [],
    'empty' => 'Tidak ada data.',
    'rows' => null,
])

<div {{ $attributes->class(['overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]']) }}>
    <div class="overflow-x-auto custom-scrollbar">
        <table class="min-w-full">
            @isset($head)
                {{-- Custom header row(s) — mis. checkbox select-all + header sortable. --}}
                <thead>
                    {{ $head }}
                </thead>
            @elseif (! empty($columns))
                <thead>
                    <tr class="border-b border-gray-100 dark:border-gray-800">
                        @foreach ($columns as $col)
                            <th class="px-5 py-3 text-left sm:px-6 {{ $col['align'] ?? '' }}">
                                <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">
                                    {{ is_array($col) ? ($col['label'] ?? '') : $col }}
                                </p>
                            </th>
                        @endforeach
                    </tr>
                </thead>
            @endif

            <tbody>
                @if ($rows && (is_iterable($rows) ? iterator_count((function () use ($rows) { yield from $rows; })()) === 0 : false))
                    {{-- defensive empty case (rare with Eloquent collections that re-iterate) --}}
                @endif

                @if ($rows !== null && (is_countable($rows) ? count($rows) === 0 : false))
                    <tr>
                        <td colspan="{{ max(count($columns), 1) }}" class="px-5 py-8 text-center text-theme-sm text-gray-400 dark:text-gray-500">
                            {{ $empty }}
                        </td>
                    </tr>
                @else
                    {{ $slot }}
                @endif
            </tbody>
        </table>
    </div>
</div>
