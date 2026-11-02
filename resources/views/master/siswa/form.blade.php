<x-layouts.app :title="$siswa ? 'Ubah Siswa' : 'Tambah Siswa'">
    <x-ui.page-header
        :title="$siswa ? 'Ubah Siswa' : 'Tambah Siswa'"
        :breadcrumb="['Master Data', 'Siswa', $siswa ? 'Ubah' : 'Tambah']"
        description="QR kartu pelajar digenerate otomatis saat siswa disimpan. Keanggotaan kelas dicatat per tahun ajaran (riwayat terjaga).">
        <x-slot:actions>
            @if ($siswa)
                <x-ui.btn variant="ghost" icon="print" :href="route('master.siswa.kartu', $siswa)">Cetak
                    Kartu</x-ui.btn>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.panel class="max-w-2xl">
        <form method="POST"
            action="{{ $siswa ? route('master.siswa.update', $siswa) : route('master.siswa.store') }}"
            class="space-y-space-md">
            @csrf
            @if ($siswa)
                @method('PUT')
            @endif

            <x-ui.input name="nama" label="Nama Lengkap" placeholder="cth. Muhammad Rizky Pratama" required
                :value="$siswa?->nama" />

            <div class="grid grid-cols-1 gap-space-md sm:grid-cols-2">
                <x-ui.input name="nisn" label="NISN" placeholder="0081234567" required :value="$siswa?->nisn"
                    hint="10 digit, unik — dipakai di laporan Dapodik." />
                <x-ui.input name="nis" label="NIS" placeholder="2026.001" required :value="$siswa?->nis"
                    hint="Nomor induk sekolah, unik." />
            </div>

            <div class="grid grid-cols-1 gap-space-md sm:grid-cols-2">
                <x-ui.select name="jenis_kelamin" label="Jenis Kelamin" required
                    :options="['laki-laki' => 'Laki-laki', 'perempuan' => 'Perempuan']"
                    :selected="$siswa?->jenis_kelamin" />
                <x-ui.select name="kelas_id" label="Kelas (TA Berjalan)" :options="$kelasPilihan->mapWithKeys(fn ($k) => [$k->id => $k->nama_kelas . ' — ' . $k->jurusan?->singkatan])->all()"
                    :selected="$siswa?->kelas_aktif?->id"
                    placeholder="— Belum masuk kelas —"
                    hint="Pemindahan kelas otomatis tercatat sebagai riwayat per tahun ajaran." />
            </div>

            @if ($siswa)
                <x-ui.select name="active" label="Status Siswa"
                    :options="[1 => 'Aktif', 0 => 'Nonaktif']" :selected="$siswa->active ? 1 : 0" />
            @endif

            @if ($siswa)
                <div class="rounded-lg bg-surface-container-low p-space-md text-body-sm text-on-surface-variant">
                    <span class="font-label-lg text-label-lg">Payload QR:</span>
                    <code class="tabular">{{ $siswa->qr_code }}</code> —
                    dicetak pada kartu pelajar, dipindai petugas gerbang.
                </div>
            @endif

            <div class="flex items-center gap-space-sm pt-space-sm">
                <x-ui.btn type="submit" icon="save">Simpan</x-ui.btn>
                <x-ui.btn variant="outline" :href="route('master.siswa')" icon="arrow_back">Batal</x-ui.btn>
            </div>
        </form>
    </x-ui.panel>
</x-layouts.app>
