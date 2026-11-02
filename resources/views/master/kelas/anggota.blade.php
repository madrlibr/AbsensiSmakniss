<x-layouts.app :title="'Anggota ' . $kelas->nama_kelas">
    <x-ui.page-header :title="'Anggota ' . $kelas->nama_kelas"
        :breadcrumb="['Master Data', 'Kelas', 'Anggota']"
        :description="$kelas->jurusan?->nama . ' · TA ' . $kelas->tahunAjaran?->nama . ($kelas->waliKelas ? ' · Wali: ' . $kelas->waliKelas->name : '')">
        <x-slot:actions>
            <x-ui.btn variant="ghost" icon="print" :href="route('master.kelas.kartu', $kelas)">Cetak Kartu
                QR</x-ui.btn>
            <x-ui.btn variant="outline" :href="route('master.kelas')" icon="arrow_back">Kembali</x-ui.btn>
        </x-slot:actions>
    </x-ui.page-header>

    @unless ($belumPunyaKelas->isEmpty())
        <x-ui.panel title="Tambah Anggota" class="mb-space-lg max-w-3xl">
            <form method="POST" action="{{ route('master.kelas.anggota.tambah', $kelas) }}"
                class="flex flex-wrap items-end gap-space-md">
                @csrf
                <div class="min-w-72 flex-1">
                    <x-ui.select name="siswa_id" label="Siswa (belum punya kelas di TA ini)" required
                        :options="$belumPunyaKelas->mapWithKeys(fn ($s) => [$s->id => $s->nama . ' · ' . $s->nisn])->all()"
                        placeholder="— Pilih siswa —" />
                </div>
                <x-ui.btn type="submit" icon="person_add">Tambahkan</x-ui.btn>
            </form>
        </x-ui.panel>
    @endunless

    <x-ui.panel :flush="true" title="Daftar Anggota">
        <x-ui.data-table>
            <x-slot:head>
                <x-ui.th>Siswa</x-ui.th>
                <x-ui.th>NISN / NIS</x-ui.th>
                <x-ui.th class="w-56">Pindah Kelas</x-ui.th>
                <x-ui.th class="text-right">Aksi</x-ui.th>
            </x-slot:head>

            @forelse ($anggota as $keanggotaan)
                @php($siswa = $keanggotaan->siswa)
                <tr class="hover:bg-surface-container-low">
                    <x-ui.td>
                        <div class="flex items-center gap-space-sm">
                            <x-ui.avatar :name="$siswa->nama" size="sm" />
                            <span class="font-label-lg text-label-lg">{{ $siswa->nama }}</span>
                        </div>
                    </x-ui.td>
                    <x-ui.td>
                        <span class="tabular">{{ $siswa->nisn }}</span>
                        <span class="text-on-surface-variant"> / </span>
                        <span class="tabular">{{ $siswa->nis }}</span>
                    </x-ui.td>
                    <x-ui.td>
                        @if ($kelasSejenis->isNotEmpty())
                            <form method="POST"
                                action="{{ route('master.kelas.anggota.pindah', $keanggotaan) }}"
                                class="flex items-center gap-space-xs">
                                @csrf
                                @method('PUT')
                                <select name="kelas_id"
                                    class="rounded-lg border border-outline-variant bg-surface-container-lowest px-space-sm py-space-xs text-body-sm">
                                    @foreach ($kelasSejenis as $id => $nama)
                                        <option value="{{ $id }}">{{ $nama }}</option>
                                    @endforeach
                                </select>
                                <x-ui.btn type="submit" variant="secondary" size="sm" icon="swap_horiz">Pindah</x-ui.btn>
                            </form>
                        @else
                            <span class="text-body-sm text-on-surface-variant">—</span>
                        @endif
                    </x-ui.td>
                    <x-ui.td>
                        <div class="flex items-center justify-end gap-space-xs">
                            <x-ui.btn variant="ghost" size="sm" icon="badge"
                                :href="route('master.siswa.kartu', $siswa)">Kartu</x-ui.btn>
                            <form method="POST"
                                action="{{ route('master.kelas.anggota.keluarkan', $keanggotaan) }}"
                                data-confirm="Keluarkan {{ $siswa->nama }} dari kelas {{ $kelas->nama_kelas }}?">
                                @csrf
                                @method('DELETE')
                                <x-ui.btn variant="danger" size="sm" icon="person_remove">Keluarkan</x-ui.btn>
                            </form>
                        </div>
                    </x-ui.td>
                </tr>
            @empty
                <tr>
                    <x-ui.td colspan="4">
                        <x-ui.empty-state icon="group" title="Belum ada anggota"
                            description="Tambahkan siswa lewat formulir di atas — atau daftarkan siswa baru di menu Data Siswa." />
                    </x-ui.td>
                </tr>
            @endforelse
        </x-ui.data-table>
    </x-ui.panel>
</x-layouts.app>
