<x-layouts.app :title="$kelas ? 'Ubah Kelas' : 'Tambah Kelas'">
    <x-ui.page-header
        :title="$kelas ? 'Ubah Kelas' : 'Tambah Kelas'"
        :breadcrumb="['Master Data', 'Kelas', $kelas ? 'Ubah' : 'Tambah']"
        description="Kelas melekat pada tahun ajaran. Wali kelas dipilih dari akun guru / wali kelas yang aktif." />

    <x-ui.panel class="max-w-2xl">
        <form method="POST"
            action="{{ $kelas ? route('master.kelas.update', $kelas) : route('master.kelas.store') }}"
            class="space-y-space-md">
            @csrf
            @if ($kelas)
                @method('PUT')
            @endif

            <div class="grid grid-cols-1 gap-space-md sm:grid-cols-2">
                <x-ui.select name="tahun_ajaran_id" label="Tahun Ajaran" required
                    :options="$daftarTahunAjaran->pluck('nama', 'id')->all()"
                    :selected="$kelas?->tahun_ajaran_id ?? request('ta') ?? $daftarTahunAjaran->firstWhere('is_active', true)?->id" />
                <x-ui.select name="jurusan_id" label="Jurusan" required
                    :options="$daftarJurusan" :selected="$kelas?->jurusan_id"
                    placeholder="— Pilih jurusan —" />
            </div>

            <x-ui.input name="nama_kelas" label="Nama Kelas" placeholder="cth. X TKJ 1" required
                :value="$kelas?->nama_kelas"
                hint="Harus unik dalam satu tahun ajaran." />

            <x-ui.select name="wali_kelas_id" label="Wali Kelas" :options="$daftarWali"
                :selected="$kelas?->wali_kelas_id" placeholder="— Belum ditentukan —" />

            <div class="flex items-center gap-space-sm pt-space-sm">
                <x-ui.btn type="submit" icon="save">Simpan</x-ui.btn>
                <x-ui.btn variant="outline" :href="route('master.kelas')" icon="arrow_back">Batal</x-ui.btn>
            </div>
        </form>
    </x-ui.panel>
</x-layouts.app>
