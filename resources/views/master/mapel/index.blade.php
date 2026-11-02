<x-layouts.app title="Data Mata Pelajaran">
    <x-ui.page-header title="Data Mata Pelajaran" :breadcrumb="['Master Data', 'Mata Pelajaran']"
        description="Kurikulum mata pelajaran yang diajarkan — dipakai di jadwal & presensi mapel.">
        <x-slot:actions>
            <x-ui.btn :href="route('master.mapel.create')" icon="add">Tambah Mata Pelajaran</x-ui.btn>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.panel :flush="true">
        <x-ui.data-table>
            <x-slot:head>
                <x-ui.th>Kode</x-ui.th>
                <x-ui.th>Nama Mata Pelajaran</x-ui.th>
                <x-ui.th>Jam / Minggu</x-ui.th>
                <x-ui.th class="text-right">Aksi</x-ui.th>
            </x-slot:head>

            @forelse ($daftar as $mapel)
                <tr class="hover:bg-surface-container-low">
                    <x-ui.td>
                        <x-ui.badge tone="primary">{{ $mapel->kode_mapel }}</x-ui.badge>
                    </x-ui.td>
                    <x-ui.td><span class="font-label-lg text-label-lg">{{ $mapel->nama_mapel }}</span></x-ui.td>
                    <x-ui.td class="tabular">{{ $mapel->jumlah_jam_per_minggu }} jam</x-ui.td>
                    <x-ui.td>
                        <div class="flex items-center justify-end gap-space-xs">
                            <x-ui.btn variant="ghost" size="sm" icon="edit"
                                :href="route('master.mapel.edit', $mapel)">Ubah</x-ui.btn>
                            <form method="POST" action="{{ route('master.mapel.destroy', $mapel) }}"
                                data-confirm="Hapus mata pelajaran {{ $mapel->nama_mapel }}?">
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
                        <x-ui.empty-state icon="menu_book" title="Belum ada mata pelajaran"
                            description="Tambahkan mata pelajaran sesuai kurikulum sekolah." />
                    </x-ui.td>
                </tr>
            @endforelse
        </x-ui.data-table>

        <x-ui.pagination :paginator="$daftar" />
    </x-ui.panel>
</x-layouts.app>
