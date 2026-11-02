<x-layouts.app title="Data Kelas">
    <x-ui.page-header title="Data Kelas" :breadcrumb="['Master Data', 'Kelas']"
        description="Kelas per tahun ajaran beserta wali kelas & jumlah anggota. Anggota dikelola lewat pivot siswa–kelas."
        >
        <x-slot:actions>
            <x-ui.btn variant="outline" :href="route('master.jurusan')" icon="category">Kelola Jurusan</x-ui.btn>
            <x-ui.btn :href="route('master.kelas.create')" icon="add">Tambah Kelas</x-ui.btn>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.filter-bar>
        <div class="w-full max-w-xs">
            <x-ui.select name="ta" label="Tahun Ajaran" :options="$daftarTahunAjaran->pluck('nama', 'id')->all()"
                :selected="$tahunAjaran?->id" />
        </div>
        <x-ui.btn type="submit" variant="secondary" icon="filter_list">Terapkan</x-ui.btn>
    </x-ui.filter-bar>

    <x-ui.panel :flush="true">
        <x-ui.data-table>
            <x-slot:head>
                <x-ui.th>Kelas</x-ui.th>
                <x-ui.th>Jurusan</x-ui.th>
                <x-ui.th>Wali Kelas</x-ui.th>
                <x-ui.th>Anggota</x-ui.th>
                <x-ui.th class="text-right">Aksi</x-ui.th>
            </x-slot:head>

            @forelse ($daftar as $kelas)
                <tr class="hover:bg-surface-container-low">
                    <x-ui.td>
                        <span class="font-label-lg text-label-lg">{{ $kelas->nama_kelas }}</span>
                        <p class="text-body-sm text-on-surface-variant">{{ $kelas->tahunAjaran?->nama ?? '—' }}</p>
                    </x-ui.td>
                    <x-ui.td>
                        <x-ui.badge tone="primary">{{ $kelas->jurusan?->singkatan ?? '—' }}</x-ui.badge>
                        <p class="mt-space-xs text-body-sm text-on-surface-variant">{{ $kelas->jurusan?->nama }}</p>
                    </x-ui.td>
                    <x-ui.td>{{ $kelas->waliKelas?->name ?? '—' }}</x-ui.td>
                    <x-ui.td class="tabular">{{ $kelas->siswa_count }} siswa</x-ui.td>
                    <x-ui.td>
                        <div class="flex items-center justify-end gap-space-xs">
                            <x-ui.btn variant="secondary" size="sm" icon="group"
                                :href="route('master.kelas.anggota', $kelas)">Anggota</x-ui.btn>
                            <x-ui.btn variant="ghost" size="sm" icon="print"
                                :href="route('master.kelas.kartu', $kelas)">Kartu</x-ui.btn>
                            <x-ui.btn variant="ghost" size="sm" icon="edit"
                                :href="route('master.kelas.edit', $kelas)">Ubah</x-ui.btn>
                            <form method="POST" action="{{ route('master.kelas.destroy', $kelas) }}"
                                data-confirm="Hapus kelas {{ $kelas->nama_kelas }}?">
                                @csrf
                                @method('DELETE')
                                <x-ui.btn variant="danger" size="sm" icon="delete">Hapus</x-ui.btn>
                            </form>
                        </div>
                    </x-ui.td>
                </tr>
            @empty
                <tr>
                    <x-ui.td colspan="5">
                        <x-ui.empty-state icon="meeting_room"
                            title="Belum ada kelas untuk tahun ajaran ini"
                            description="Buat kelas baru lalu tambahkan anggotanya." />
                    </x-ui.td>
                </tr>
            @endforelse
        </x-ui.data-table>

        <x-ui.pagination :paginator="$daftar" />
    </x-ui.panel>
</x-layouts.app>
