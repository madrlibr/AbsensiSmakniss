<x-layouts.app :title="$jurusan ? 'Ubah Jurusan' : 'Tambah Jurusan'">
    <x-ui.page-header
        :title="$jurusan ? 'Ubah Jurusan' : 'Tambah Jurusan'"
        :breadcrumb="['Master Data', 'Kelas & Jurusan', 'Jurusan', $jurusan ? 'Ubah' : 'Tambah']" />

    <x-ui.panel class="max-w-2xl">
        <form method="POST"
            action="{{ $jurusan ? route('master.jurusan.update', $jurusan) : route('master.jurusan.store') }}"
            class="space-y-space-md">
            @csrf
            @if ($jurusan)
                @method('PUT')
            @endif

            <x-ui.input name="nama" label="Nama Jurusan" placeholder="cth. Teknik Komputer Jaringan" required
                :value="$jurusan?->nama" />

            <x-ui.input name="singkatan" label="Singkatan" placeholder="cth. TKJ" required
                :value="$jurusan?->singkatan" hint="Maksimal 10 karakter, unik di seluruh sekolah." />

            <div class="flex items-center gap-space-sm pt-space-sm">
                <x-ui.btn type="submit" icon="save">Simpan</x-ui.btn>
                <x-ui.btn variant="outline" :href="route('master.jurusan')" icon="arrow_back">Batal</x-ui.btn>
            </div>
        </form>
    </x-ui.panel>
</x-layouts.app>
