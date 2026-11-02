<x-layouts.app :title="$mapel ? 'Ubah Mata Pelajaran' : 'Tambah Mata Pelajaran'">
    <x-ui.page-header
        :title="$mapel ? 'Ubah Mata Pelajaran' : 'Tambah Mata Pelajaran'"
        :breadcrumb="['Master Data', 'Mata Pelajaran', $mapel ? 'Ubah' : 'Tambah']" />

    <x-ui.panel class="max-w-2xl">
        <form method="POST"
            action="{{ $mapel ? route('master.mapel.update', $mapel) : route('master.mapel.store') }}"
            class="space-y-space-md">
            @csrf
            @if ($mapel)
                @method('PUT')
            @endif

            <div class="grid grid-cols-1 gap-space-md sm:grid-cols-2">
                <x-ui.input name="kode_mapel" label="Kode Mapel" placeholder="cth. MTK-101" required
                    :value="$mapel?->kode_mapel" hint="Unik — memudahkan pencarian di laporan." />
                <x-ui.input type="number" name="jumlah_jam_per_minggu" label="Jam per Minggu" required
                    :value="$mapel?->jumlah_jam_per_minggu ?? 2" hint="Angka 1–20." />
            </div>

            <x-ui.input name="nama_mapel" label="Nama Mata Pelajaran" placeholder="cth. Matematika" required
                :value="$mapel?->nama_mapel" />

            <div class="flex items-center gap-space-sm pt-space-sm">
                <x-ui.btn type="submit" icon="save">Simpan</x-ui.btn>
                <x-ui.btn variant="outline" :href="route('master.mapel')" icon="arrow_back">Batal</x-ui.btn>
            </div>
        </form>
    </x-ui.panel>
</x-layouts.app>
