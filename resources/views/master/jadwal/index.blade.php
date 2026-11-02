<x-layouts.app title="Jadwal Pelajaran">
    <x-ui.page-header title="Jadwal Pelajaran" :breadcrumb="['Manajemen Sistem', 'Jadwal & Kalender']"
        description="Jadwal tetap per kelas & hari. Sistem otomatis menolak bentrok kelas, guru, dan ruangan pada jam yang sama.">
        <x-slot:actions>
            @if (auth()->user()?->role?->value === 'admin')
                <x-ui.btn :href="route('jadwal.create')" icon="add">Tambah Jadwal</x-ui.btn>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.filter-bar>
        <div class="w-full max-w-64">
            <x-ui.select name="kelas_id" label="Kelas"
                :options="$kelasPilihan->mapWithKeys(fn ($k) => [$k->id => $k->nama_kelas . ' — ' . $k->jurusan?->singkatan])->all()"
                :selected="$filterKelas" placeholder="Semua kelas" />
        </div>
        <div class="w-full max-w-44">
            <x-ui.select name="semester" label="Semester" :options="$semesterPilihan"
                :selected="$filterSemester" placeholder="Semua semester" />
        </div>
        <x-ui.btn type="submit" variant="secondary" icon="filter_list">Terapkan</x-ui.btn>
        <x-ui.btn variant="ghost" icon="restart_alt" :href="route('jadwal.index')">Reset</x-ui.btn>
    </x-ui.filter-bar>

    <x-ui.panel :flush="true">
        <x-ui.data-table>
            <x-slot:head>
                <x-ui.th>Hari</x-ui.th>
                <x-ui.th>Jam Ke</x-ui.th>
                <x-ui.th>Waktu</x-ui.th>
                <x-ui.th>Kelas</x-ui.th>
                <x-ui.th>Mata Pelajaran</x-ui.th>
                <x-ui.th>Guru</x-ui.th>
                <x-ui.th>Ruangan</x-ui.th>
                @if (auth()->user()?->role?->value === 'admin')
                    <x-ui.th class="text-right">Aksi</x-ui.th>
                @endif
            </x-slot:head>

            @forelse ($daftar as $jadwal)
                <tr class="hover:bg-surface-container-low">
                    <x-ui.td>
                        <x-ui.badge tone="primary">{{ $jadwal->hari->label() }}</x-ui.badge>
                    </x-ui.td>
                    <x-ui.td class="tabular">{{ $jadwal->jam_ke }}</x-ui.td>
                    <x-ui.td class="tabular">{{ $jadwal->jam_mulai->format('H:i') }}–{{ $jadwal->jam_selesai->format('H:i') }}</x-ui.td>
                    <x-ui.td>
                        <span class="font-label-lg text-label-lg">{{ $jadwal->kelas?->nama_kelas }}</span>
                        <span class="ml-space-xs text-body-sm text-on-surface-variant">{{ $jadwal->kelas?->jurusan?->singkatan }}</span>
                    </x-ui.td>
                    <x-ui.td>{{ $jadwal->mapel?->nama_mapel }}</x-ui.td>
                    <x-ui.td>{{ $jadwal->guru?->nama }}</x-ui.td>
                    <x-ui.td>{{ $jadwal->ruangan ?? '—' }}</x-ui.td>
                    @if (auth()->user()?->role?->value === 'admin')
                        <x-ui.td>
                            <div class="flex items-center justify-end gap-space-xs">
                                <x-ui.btn variant="ghost" size="sm" icon="edit"
                                    :href="route('jadwal.edit', $jadwal)">Ubah</x-ui.btn>
                                <form method="POST" action="{{ route('jadwal.destroy', $jadwal) }}"
                                    data-confirm="Hapus jadwal {{ $jadwal->mapel?->nama_mapel }} ({{ $jadwal->hari->label() }} jam ke-{{ $jadwal->jam_ke }})?">
                                    @csrf
                                    @method('DELETE')
                                    <x-ui.btn variant="danger" size="sm" icon="delete">Hapus</x-ui.btn>
                                </form>
                            </div>
                        </x-ui.td>
                    @endif
                </tr>
            @empty
                <tr>
                    <x-ui.td colspan="8">
                        <x-ui.empty-state icon="event" title="Belum ada jadwal"
                            description="Tambahkan jadwal per kelas — sistem menjaga agar tidak ada bentrok kelas/guru/ruangan." />
                    </x-ui.td>
                </tr>
            @endforelse
        </x-ui.data-table>
    </x-ui.panel>
</x-layouts.app>
