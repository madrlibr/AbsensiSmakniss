<x-layouts.app title="Data Siswa">
    <x-ui.page-header title="Data Siswa" :breadcrumb="['Master Data', 'Siswa']"
        description="Direktori siswa aktif: identitas NISN/NIS, pemetaan kelas per tahun ajaran, dan QR kartu pelajar otomatis.">
        <x-slot:actions>
            <x-ui.btn :href="route('master.siswa.create')" icon="add">Tambah Siswa</x-ui.btn>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="mb-space-lg grid grid-cols-1 gap-space-md sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.kpi-card label="Total Siswa Aktif" :value="$kpi['total']" icon="how_to_reg" />
        <x-ui.kpi-card label="Laki-laki" :value="$kpi['laki']" icon="boy" tone="secondary" />
        <x-ui.kpi-card label="Perempuan" :value="$kpi['perempuan']" icon="girl" tone="tertiary" />
        <x-ui.kpi-card label="Belum Punya Kelas" :value="$kpi['tanpa_kelas']" icon="warning"
            tone="{{ $kpi['tanpa_kelas'] > 0 ? 'error' : 'neutral' }}" />
    </div>

    <x-ui.filter-bar>
        <div class="min-w-56 flex-1">
            <x-ui.input name="q" label="Cari Siswa" placeholder="Cari nama, NISN, atau NIS…"
                :value="request('q')" />
        </div>
        <div class="w-full max-w-56">
            <x-ui.select name="kelas_id" label="Kelas"
                :options="$kelasPilihan->mapWithKeys(fn ($k) => [$k->id => $k->nama_kelas . ' — ' . $k->jurusan?->singkatan])->all()"
                :selected="request('kelas_id')" placeholder="Semua kelas" />
        </div>
        <div class="w-full max-w-44">
            <x-ui.select name="status" label="Status" :options="['aktif' => 'Aktif', 'nonaktif' => 'Nonaktif', 'semua' => 'Semua']"
                :selected="$status" />
        </div>
        <x-ui.btn type="submit" variant="secondary" icon="filter_list">Terapkan</x-ui.btn>
        <x-ui.btn variant="ghost" icon="restart_alt" :href="route('master.siswa')">Reset</x-ui.btn>
    </x-ui.filter-bar>

    <x-ui.panel :flush="true">
        <x-ui.data-table>
            <x-slot:head>
                <x-ui.th>Identitas Siswa (NISN / NIS)</x-ui.th>
                <x-ui.th>Kelas</x-ui.th>
                <x-ui.th>JK</x-ui.th>
                <x-ui.th>Status</x-ui.th>
                <x-ui.th class="text-right">Aksi</x-ui.th>
            </x-slot:head>

            @forelse ($daftar as $siswa)
                <tr class="hover:bg-surface-container-low">
                    <x-ui.td>
                        <div class="flex items-center gap-space-sm">
                            <x-ui.avatar :name="$siswa->nama" size="sm" />
                            <div class="leading-tight">
                                <p class="font-label-lg text-label-lg">{{ $siswa->nama }}</p>
                                <p class="tabular text-body-sm text-on-surface-variant">
                                    NISN: {{ $siswa->nisn }} · NIS: {{ $siswa->nis }}
                                </p>
                            </div>
                        </div>
                    </x-ui.td>
                    <x-ui.td>
                        @if ($siswa->kelasAktif)
                            <x-ui.badge tone="primary">{{ $siswa->kelasAktif->nama_kelas }}</x-ui.badge>
                        @else
                            <x-ui.badge tone="warning">Belum ada kelas</x-ui.badge>
                        @endif
                    </x-ui.td>
                    <x-ui.td class="capitalize">{{ $siswa->jenis_kelamin }}</x-ui.td>
                    <x-ui.td>
                        @if ($siswa->active)
                            <x-ui.badge tone="success">Aktif</x-ui.badge>
                        @else
                            <x-ui.badge tone="danger">Nonaktif</x-ui.badge>
                        @endif
                    </x-ui.td>
                    <x-ui.td>
                        <div class="flex items-center justify-end gap-space-xs">
                            <x-ui.btn variant="ghost" size="sm" icon="print"
                                :href="route('master.siswa.kartu', $siswa)">Kartu</x-ui.btn>
                            <x-ui.btn variant="ghost" size="sm" icon="edit"
                                :href="route('master.siswa.edit', $siswa)">Ubah</x-ui.btn>
                            <form method="POST" action="{{ route('master.siswa.destroy', $siswa) }}"
                                data-confirm="Hapus siswa {{ $siswa->nama }}? Data absensi tidak ikut terhapus.">
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
                        <x-ui.empty-state icon="person_search" title="Tidak ada siswa ditemukan"
                            description="Ubah kata kunci pencarian, atau tambahkan siswa baru." />
                    </x-ui.td>
                </tr>
            @endforelse
        </x-ui.data-table>

        <x-ui.pagination :paginator="$daftar" />
    </x-ui.panel>
</x-layouts.app>
