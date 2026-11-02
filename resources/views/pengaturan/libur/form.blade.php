<x-layouts.app :title="$libur ? 'Ubah Hari Libur' : 'Tambah Hari Libur'">
    <x-ui.page-header
        :title="$libur ? 'Ubah Hari Libur / Agenda' : 'Tambah Hari Libur / Agenda'"
        :breadcrumb="['Manajemen Sistem', 'Hari Libur Rentang', $libur ? 'Ubah' : 'Tambah']"
        description="Isi tanggal tunggal (kosongkan tanggal selesai) atau rentang tanggal. Status 'Kegiatan' memaksa hari tetap aktif." />

    <x-ui.panel class="max-w-2xl">
        <form method="POST"
            action="{{ $libur ? route('pengaturan.libur.update', $libur) : route('pengaturan.libur.store') }}"
            class="space-y-space-md">
            @csrf
            @if ($libur)
                @method('PUT')
            @endif

            <x-ui.input name="keterangan" label="Nama Libur / Agenda" required
                placeholder="cth. Libur Nasional Kemerdekaan / Uji Kompetensi Keahlian"
                :value="$libur?->keterangan" />

            <div class="grid grid-cols-1 gap-space-md sm:grid-cols-2">
                <x-ui.input name="tanggal_mulai" label="Tanggal Mulai" type="date" required
                    :value="$libur?->tanggal_mulai?->format('Y-m-d')" />
                <x-ui.input name="tanggal_selesai" label="Tanggal Selesai" type="date"
                    :value="$libur?->tanggal_selesai?->format('Y-m-d')"
                    hint="Kosongkan bila hanya satu hari." />
            </div>

            <x-ui.select name="status_masuk" label="Status Hari" required
                :options="['tidak_masuk' => 'Libur — sekolah tutup, scan diblokir', 'masuk' => 'Kegiatan — hari dipaksa aktif (mis. UKK)']"
                :selected="$libur?->status_masuk" />

            <x-ui.select name="tahun_ajaran_id" label="Tahun Ajaran" required
                :options="$daftarTahunAjaran->pluck('nama', 'id')->all()"
                :selected="$libur?->tahun_ajaran_id ?? $ta?->id" />

            <div class="flex items-center gap-space-sm pt-space-sm">
                <x-ui.btn type="submit" icon="save">Simpan</x-ui.btn>
                <x-ui.btn variant="outline" :href="route('pengaturan.libur')" icon="arrow_back">Batal</x-ui.btn>
            </div>
        </form>
    </x-ui.panel>
</x-layouts.app>
