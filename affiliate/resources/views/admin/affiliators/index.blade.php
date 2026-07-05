@extends('admin.layouts.admin')

@section('content')
<x-page-header title="Kelola Affiliator" subtitle="Approve, suspend, dan kelola akun affiliator." />

<x-card class="mb-4">
    <form method="GET" class="flex flex-wrap items-end gap-3">
        <div class="flex-1 min-w-[12rem]">
            <x-form.group label="Cari">
                <x-form.input name="search" value="{{ request('search') }}" placeholder="Nama atau email..." />
            </x-form.group>
        </div>
        <div class="w-48">
            <x-form.group label="Status">
                <x-form.select name="status" onchange="this.form.submit()">
                    <option value="all" @selected(request('status') === 'all')>Semua Status</option>
                    <option value="pending" @selected(request('status') === 'pending')>Pending</option>
                    <option value="active" @selected(request('status') === 'active')>Aktif</option>
                    <option value="suspended" @selected(request('status') === 'suspended')>Suspended</option>
                </x-form.select>
            </x-form.group>
        </div>
        <x-button type="submit" icon="search">Cari</x-button>
    </form>
</x-card>

@if ($affiliators->isEmpty())
    <x-card :padded="false"><x-empty-state icon="users" title="Belum ada affiliator" /></x-card>
@else
    <x-table :heads="['Nama', 'Email', 'Tipe', 'Status', 'Aksi']">
        @foreach ($affiliators as $aff)
            <tr class="hover:bg-slate-50/70 transition-colors">
                <td class="px-5 py-3.5 font-medium text-slate-700">{{ $aff->name }}</td>
                <td class="px-5 py-3.5 text-slate-600">{{ $aff->email }}</td>
                <td class="px-5 py-3.5 text-slate-600">{{ $aff->type->name }}</td>
                <td class="px-5 py-3.5"><x-status-badge :status="$aff->status" /></td>
                <td class="px-5 py-3.5">
                    <div class="flex items-center justify-end gap-2">
                        <x-button :href="route('admin.affiliators.show', $aff)" variant="outline" size="sm">Detail</x-button>
                        @if ($aff->status === 'pending')
                            <form method="POST" action="{{ route('admin.affiliators.approve', $aff) }}" class="inline">
                                @csrf
                                <x-button type="submit" variant="secondary" size="sm">Approve</x-button>
                            </form>
                        @elseif ($aff->status === 'active')
                            <form method="POST" action="{{ route('admin.affiliators.suspend', $aff) }}" class="inline">
                                @csrf
                                <x-button type="submit" variant="danger" size="sm">Suspend</x-button>
                            </form>
                        @elseif ($aff->status === 'suspended')
                            <form method="POST" action="{{ route('admin.affiliators.reactivate', $aff) }}" class="inline">
                                @csrf
                                <x-button type="submit" size="sm">Reactivate</x-button>
                            </form>
                        @endif
                    </div>
                </td>
            </tr>
        @endforeach
    </x-table>

    @if ($affiliators->hasPages())
        <div class="mt-4">{{ $affiliators->withQueryString()->links() }}</div>
    @endif
@endif
@endsection
