@props([
    'title' => '',
    'value' => '',
    'icon' => 'box',
    'badge' => null,
    'badgeTone' => null,
    'hint' => null,
])

@php
    $badgeClasses = match($badgeTone) {
        'success' => 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500',
        'error' => 'bg-error-50 text-error-600 dark:bg-error-500/15 dark:text-error-500',
        'warning' => 'bg-warning-50 text-warning-600 dark:bg-warning-500/15 dark:text-warning-500',
        'brand' => 'bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-500',
        default => 'bg-gray-50 text-gray-600 dark:bg-gray-500/15 dark:text-gray-400',
    };
@endphp

<div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
    {{-- Baris atas: ikon (kiri) + badge (kanan). Badge dipindah ke sini supaya
         tidak bertabrakan dengan teks hint saat kartu menyempit (grid 4 kolom
         di layar 1024–1279px). --}}
    <div class="flex items-start justify-between gap-2">
        <div class="flex items-center justify-center w-12 h-12 bg-gray-100 rounded-xl dark:bg-gray-800 shrink-0">
        @if($icon === 'orders')
            <svg class="fill-gray-800 dark:fill-white/90" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd" clip-rule="evenodd" d="M5 3H19C19.5304 3 20.0391 3.21071 20.4142 3.58579C20.7893 3.96086 21 4.46957 21 5V21C21 21.2652 20.8946 21.5196 20.7071 21.7071C20.5196 21.8946 20.2652 22 20 22H4C3.73478 22 3.48043 21.8946 3.29289 21.7071C3.10536 21.5196 3 21.2652 3 21V5C3 4.46957 3.21071 3.96086 3.58579 3.58579C3.96086 3.21071 4.46957 3 5 3ZM5 5V19H19V5H5ZM8 8C8 7.44772 8.44772 7 9 7H15C15.5523 7 16 7.44772 16 8C16 8.55228 15.5523 9 15 9H9C8.44772 9 8 8.55228 8 8ZM9 12H15C15.5523 12 16 12.4477 16 13C16 13.5523 15.5523 14 15 14H9C8.44772 14 8 13.5523 8 13C8 12.4477 8.44772 12 9 12Z" fill=""/>
            </svg>
        @elseif($icon === 'pending')
            <svg class="fill-gray-800 dark:fill-white/90" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd" clip-rule="evenodd" d="M12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2ZM12 8C12.5523 8 13 8.44772 13 9V12C13 12.5523 12.5523 13 12 13C11.4477 13 11 12.5523 11 12V9C11 8.44772 11.4477 8 12 8ZM12 15C12.5523 15 13 15.4477 13 16C13 16.5523 12.5523 17 12 17C11.4477 17 11 16.5523 11 16C11 15.4477 11.4477 15 12 15Z" fill=""/>
            </svg>
        @elseif($icon === 'verify')
            <svg class="fill-gray-800 dark:fill-white/90" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd" clip-rule="evenodd" d="M12 1L3 5V11C3 17.5 6.8 23.7 12 25C17.2 23.7 21 17.5 21 11V5L12 1ZM10 15.1L7.4 12.5L8.8 11.1L10 12.3L15.2 7.1L16.6 8.5L10 15.1Z" fill=""/>
            </svg>
        @elseif($icon === 'installment')
            <svg class="fill-gray-800 dark:fill-white/90" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd" clip-rule="evenodd" d="M12 2C6.48 2 2 6.48 2 12C2 17.52 6.48 22 12 22C17.52 22 22 17.52 22 12C22 6.48 17.52 2 12 2ZM13 17H11V11H13V17ZM13 9H11V7H13V9Z" fill=""/>
            </svg>
        @elseif($icon === 'paid')
            <svg class="fill-gray-800 dark:fill-white/90" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd" clip-rule="evenodd" d="M2 12C2 6.48 6.48 2 12 2C17.52 2 22 6.48 22 12C22 17.52 17.52 22 12 22C6.48 22 2 17.52 2 12ZM10.5 15.1L17.1 8.5L15.7 7.1L10.5 12.3L8.3 10.1L6.9 11.5L10.5 15.1Z" fill=""/>
            </svg>
        @elseif($icon === 'product')
            <svg class="fill-gray-800 dark:fill-white/90" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd" clip-rule="evenodd" d="M11.665 3.75621C11.8762 3.65064 12.1247 3.65064 12.3358 3.75621L18.7807 6.97856L12.3358 10.2009C12.1247 10.3065 11.8762 10.3065 11.665 10.2009L5.22014 6.97856L11.665 3.75621ZM4.29297 8.19203V16.0946C4.29297 16.3787 4.45347 16.6384 4.70757 16.7654L11.25 20.0366V11.6513L4.29297 8.19203ZM12.75 20.037L19.2933 16.7654C19.5474 16.6384 19.7079 16.3787 19.7079 16.0946V8.19202L13.0066 11.5426L12.75 11.6516V20.037Z" fill=""/>
            </svg>
        @elseif($icon === 'revenue')
            <svg class="fill-gray-800 dark:fill-white/90" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd" clip-rule="evenodd" d="M12 2C6.48 2 2 6.48 2 12C2 17.52 6.48 22 12 22C17.52 22 22 17.52 22 12C22 6.48 17.52 2 12 2ZM13 17.5H11V16H10C9.45 16 9 15.55 9 15V13.5C9 12.95 9.45 12.5 10 12.5H14V11H9V9.5H11V8H13V9.5H14C14.55 9.5 15 9.95 15 10.5V12C15 12.55 14.55 13 14 13H10V14.5H15V16H13V17.5Z" fill=""/>
            </svg>
        @elseif($icon === 'calendar')
            <svg class="fill-gray-800 dark:fill-white/90" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd" clip-rule="evenodd" d="M7 2C7 1.44772 7.44772 1 8 1C8.55228 1 9 1.44772 9 2V3H15V2C15 1.44772 15.4477 1 16 1C16.5523 1 17 1.44772 17 2V3H19C20.1046 3 21 3.89543 21 5V20C21 21.1046 20.1046 22 19 22H5C3.89543 22 3 21.1046 3 20V5C3 3.89543 3.89543 3 5 3H7V2ZM5 8V20H19V8H5Z" fill=""/>
            </svg>
        @else
            <svg class="fill-gray-800 dark:fill-white/90" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd" clip-rule="evenodd" d="M5 3H19C19.5304 3 20.0391 3.21071 20.4142 3.58579C20.7893 3.96086 21 4.46957 21 5V21C21 21.2652 20.8946 21.5196 20.7071 21.7071C20.5196 21.8946 20.2652 22 20 22H4C3.73478 22 3.48043 21.8946 3.29289 21.7071C3.10536 21.5196 3 21.2652 3 21V5C3 4.46957 3.21071 3.96086 3.58579 3.58579C3.96086 3.21071 4.46957 3 5 3ZM5 5V19H19V5H5Z" fill=""/>
            </svg>
        @endif
        </div>

        @if($badge)
            <span class="shrink-0 inline-flex items-center gap-1 rounded-full py-0.5 px-2.5 text-xs font-medium whitespace-nowrap {{ $badgeClasses }}">
                {{ $badge }}
            </span>
        @endif
    </div>

    {{-- Konten: judul, angka, hint — full-width, tanpa rebutan ruang dgn badge. --}}
    <div class="mt-5">
        <span class="text-sm text-gray-500 dark:text-gray-400">{{ $title }}</span>
        <h4 class="mt-2 font-bold text-gray-800 text-title-sm dark:text-white/90">{{ $value }}</h4>
        @if($hint)
            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">{{ $hint }}</p>
        @endif
    </div>
</div>
