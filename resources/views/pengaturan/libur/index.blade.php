<x-layouts.app title="Hari Libur & Agenda">
    <x-ui.page-header title="Hari Libur & Agenda" :breadcrumb="['Manajemen Sistem', 'Hari Libur Rentang']"
        description="Libur rentang tanggal (libur nasional, UKK, acara sekolah). Status 'Kegiatan' memaksa hari tetap aktif walau jatuh di hari libur rutin.">
        <x-slot:actions>
            <x-ui.btn :href="route('pengaturan.libur.create')" icon="add">Tambah Hari Libur</x-ui.btn>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.panel :flush="true">
        <x-ui.data-table>
            <x-slot:head>
                <x-ui.th>Tanggal</x-ui.th>
                <x-ui.th>Nama Libur / Agenda</x-ui.th>
                <x-ui.th>Status Hari</x-ui.th>
                <x-ui.th class="text-right">Aksi</x-ui.th>
            </x-slot:head>

            @forelse ($daftar as $libur)
                <tr class="hover:bg-surface-container-low">
                    <x-ui.td class="tabular whitespace-nowrap">
                        @if ($libur->tanggal_selesai && ! $libur->tanggal_selesai->isSameDay($libur->tanggal_mulai))
                            {{ $libur->tanggal_mulai->isoFormat('D MMM YYYY') }} – {{ $libur->tanggal_selesai->isoFormat('D MMM YYYY') }}
                        @else
                            {{ $libur->tanggal_mulai->isoFormat('D MMM YYYY') }}
                        @endif
                    </x-ui.td>
                    <x-ui.td>{{ $libur->keterangan }}</x-ui.td>
                    <x-ui.td>
                        @if ($libur->status_masuk === 'masuk')
                            <x-ui.badge tone="success">Kegiatan — Masuk</x-ui.badge>
                        @else
                            <x-ui.badge tone="danger">Libur — Gerbang Tutup</x-ui.badge>
                        @endif
                    </x-ui.td>
                    <x-ui.td>
                        <div class="flex items-center justify-end gap-space-xs">
                            <x-ui.btn variant="ghost" size="sm" icon="edit"
                                :href="route('pengaturan.libur.edit', $libur)">Ubah</x-ui.btn>
                            <form method="POST" action="{{ route('pengaturan.libur.destroy', $libur) }}"
                                data-confirm="Hapus &quot;{{ $libur->keterangan }}&quot; dari kalender libur?">
                                @csrf
                                @method('DELETE')
                                <x-ui.btn variant="danger" size="sm" icon="delete">Hapus</x-ui.btn>
                            </form>
                        </div>
                    </x-ui.td>
                </tr>
            @empty
                <tr>
                    <x-ui.td colspan="4">
                        <x-ui.empty-state icon="beach_access" title="Belum ada hari libur"
                            description="Tambahkan libur nasional, hari ujian, atau acara sekolah pada tahun ajaran aktif." />
                    </x-ui.td>
                </tr>
            @endforelse
        </x-ui.data-table>
    </x-ui.panel>
</x-layouts.app>
