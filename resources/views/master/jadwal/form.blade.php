<x-layouts.app :title="$jadwal ? 'Ubah Jadwal' : 'Tambah Jadwal'">
    <x-ui.page-header
        :title="$jadwal ? 'Ubah Jadwal Pelajaran' : 'Tambah Jadwal Pelajaran'"
        :breadcrumb="['Manajemen Sistem', 'Jadwal', $jadwal ? 'Ubah' : 'Tambah']"
        description="Sistem menolak jadwal yang bentrok kelas, guru, atau ruangan pada hari/jam/semester yang sama." />

    <x-ui.panel class="max-w-2xl">
        <form method="POST"
            action="{{ $jadwal ? route('jadwal.update', $jadwal) : route('jadwal.store') }}"
            class="space-y-space-md">
            @csrf
            @if ($jadwal)
                @method('PUT')
            @endif

            <div class="grid grid-cols-1 gap-space-md sm:grid-cols-2">
                <x-ui.select name="kelas_id" label="Kelas (TA {{ $ta?->nama ?? '—' }})" required
                    :options="$kelasPilihan->mapWithKeys(fn ($k) => [$k->id => $k->nama_kelas . ' — ' . $k->jurusan?->singkatan])->all()"
                    :selected="$jadwal?->kelas_id" placeholder="— Pilih kelas —" />
                <x-ui.select name="mapel_id" label="Mata Pelajaran" required :options="$mapelPilihan"
                    :selected="$jadwal?->mapel_id" placeholder="— Pilih mapel —" />
            </div>

            <x-ui.select name="guru_id" label="Guru Pengampu" required :options="$guruPilihan"
                :selected="$jadwal?->guru_id" placeholder="— Pilih guru —"
                hint="Guru yang sama tidak bisa mengajar dua kelas di jam yang bersamaan." />

            <div class="grid grid-cols-1 gap-space-md sm:grid-cols-3">
                <x-ui.select name="hari" label="Hari" required :options="$hariPilihan"
                    :selected="$jadwal?->hari?->value" placeholder="— Pilih hari —" />
                <x-ui.input name="jam_ke" label="Jam Ke-" type="number" min="1" max="8" required
                    :value="$jadwal?->jam_ke" placeholder="cth. 1" />
                <x-ui.select name="semester" label="Semester" required :options="$semesterPilihan"
                    :selected="$jadwal?->semester?->value" placeholder="— Pilih semester —" />
            </div>

            <div class="grid grid-cols-1 gap-space-md sm:grid-cols-2">
                <x-ui.input name="jam_mulai" label="Jam Mulai" type="time" required
                    :value="$jadwal?->jam_mulai?->format('H:i')" />
                <x-ui.input name="jam_selesai" label="Jam Selesai" type="time" required
                    :value="$jadwal?->jam_selesai?->format('H:i')" />
            </div>

            <x-ui.input name="ruangan" label="Ruangan" placeholder="cth. TKJ Lab 1 / R. Teori 3"
                :value="$jadwal?->ruangan"
                hint="Opsional — kosongkan untuk kelas reguler. Ruangan yang sama tidak bisa dipakai dua kelas bersamaan." />

            <div class="flex items-center gap-space-sm pt-space-sm">
                <x-ui.btn type="submit" icon="save">Simpan</x-ui.btn>
                <x-ui.btn variant="outline" :href="route('jadwal.index')" icon="arrow_back">Batal</x-ui.btn>
            </div>
        </form>
    </x-ui.panel>
</x-layouts.app>
