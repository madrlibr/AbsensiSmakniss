<x-layouts.app :title="$tahunAjaran ? 'Ubah Tahun Ajaran' : 'Tambah Tahun Ajaran'">
    <x-ui.page-header
        :title="$tahunAjaran ? 'Ubah Tahun Ajaran' : 'Tambah Tahun Ajaran'"
        :breadcrumb="['Master Data', 'Tahun Ajaran', $tahunAjaran ? 'Ubah' : 'Tambah']" />

    <x-ui.panel class="max-w-2xl">
        <form method="POST"
            action="{{ $tahunAjaran ? route('master.tahun-ajaran.update', $tahunAjaran) : route('master.tahun-ajaran.store') }}"
            class="space-y-space-md">
            @csrf
            @if ($tahunAjaran)
                @method('PUT')
            @endif

            <x-ui.input name="nama" label="Nama Tahun Ajaran" placeholder="cth. 2026/2027" required
                :value="$tahunAjaran?->nama" hint="Format: YYYY/YYYY (satu tahun ajaran berjalan Juli–Juni)." />

            <div class="grid grid-cols-1 gap-space-md sm:grid-cols-2">
                <x-ui.input type="date" name="tanggal_mulai" label="Tanggal Mulai" required
                    :value="$tahunAjaran?->tanggal_mulai?->format('Y-m-d')" />
                <x-ui.input type="date" name="tanggal_selesai" label="Tanggal Selesai" required
                    :value="$tahunAjaran?->tanggal_selesai?->format('Y-m-d')" />
            </div>

            <div class="flex items-center gap-space-sm pt-space-sm">
                <x-ui.btn type="submit" icon="save">Simpan</x-ui.btn>
                <x-ui.btn variant="outline" :href="route('master.tahun-ajaran')" icon="arrow_back">Batal</x-ui.btn>
            </div>
        </form>
    </x-ui.panel>
</x-layouts.app>
