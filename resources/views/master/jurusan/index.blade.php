<x-layouts.app title="Data Jurusan">
    <x-ui.page-header title="Data Jurusan" :breadcrumb="['Master Data', 'Kelas & Jurusan', 'Jurusan']"
        description="Daftar jurusan/program keahlian beserta jumlah kelasnya.">
        <x-slot:actions>
            <x-ui.btn :href="route('master.jurusan.create')" icon="add">Tambah Jurusan</x-ui.btn>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.panel :flush="true">
        <x-ui.data-table>
            <x-slot:head>
                <x-ui.th>Nama Jurusan</x-ui.th>
                <x-ui.th>Singkatan</x-ui.th>
                <x-ui.th>Jumlah Kelas</x-ui.th>
                <x-ui.th class="text-right">Aksi</x-ui.th>
            </x-slot:head>

            @forelse ($daftar as $jurusan)
                <tr class="hover:bg-surface-container-low">
                    <x-ui.td><span class="font-label-lg text-label-lg">{{ $jurusan->nama }}</span></x-ui.td>
                    <x-ui.td>
                        <x-ui.badge tone="primary">{{ $jurusan->singkatan }}</x-ui.badge>
                    </x-ui.td>
                    <x-ui.td class="tabular">{{ $jurusan->kelas_count }} kelas</x-ui.td>
                    <x-ui.td>
                        <div class="flex items-center justify-end gap-space-xs">
                            <x-ui.btn variant="ghost" size="sm" icon="edit"
                                :href="route('master.jurusan.edit', $jurusan)">Ubah</x-ui.btn>
                            <form method="POST" action="{{ route('master.jurusan.destroy', $jurusan) }}"
                                data-confirm="Hapus jurusan {{ $jurusan->nama }}?">
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
                        <x-ui.empty-state icon="school" title="Belum ada jurusan"
                            description="Tambahkan jurusan yang ada di sekolah Anda." />
                    </x-ui.td>
                </tr>
            @endforelse
        </x-ui.data-table>

        <x-ui.pagination :paginator="$daftar" />
    </x-ui.panel>
</x-layouts.app>
