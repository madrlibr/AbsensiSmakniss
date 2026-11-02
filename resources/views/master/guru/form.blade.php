<x-layouts.app :title="$guru ? 'Ubah Guru' : 'Tambah Guru'">
    <x-ui.page-header
        :title="$guru ? 'Ubah Guru' : 'Tambah Guru'"
        :breadcrumb="['Master Data', 'Guru', $guru ? 'Ubah' : 'Tambah']"
        description="Data profil terhubung dengan akun login (role Guru). Wali kelas ditetapkan di modul Kelas." />

    <x-ui.panel class="max-w-2xl">
        <form method="POST"
            action="{{ $guru ? route('master.guru.update', $guru) : route('master.guru.store') }}"
            class="space-y-space-md">
            @csrf
            @if ($guru)
                @method('PUT')
            @endif

            <x-ui.input name="name" label="Nama Lengkap & Gelar" placeholder="cth. Budi Suryanto, S.Pd" required
                :value="$guru?->user?->name" />

            <div class="grid grid-cols-1 gap-space-md sm:grid-cols-2">
                <x-ui.input name="nip" label="NIP" placeholder="198504122001011001" required
                    :value="$guru?->nip" hint="18 digit, unik." />
                <x-ui.input name="telepon" label="Telepon / WA" placeholder="081234567890"
                    :value="$guru?->telepon" />
            </div>

            <div class="grid grid-cols-1 gap-space-md sm:grid-cols-2">
                <x-ui.input name="email" label="Email (Akun Login)" type="email"
                    placeholder="guru@sekolah.test" required :value="$guru?->user?->email" />
                <x-ui.input name="password" label="Kata Sandi" type="password"
                    placeholder="{{ $guru ? 'Kosongkan jika tidak diganti' : 'Minimal 8 karakter' }}"
                    :required="! $guru"
                    :hint="$guru ? 'Isi hanya jika ingin mengganti kata sandi.' : 'Diberikan ke guru untuk masuk.'" />
            </div>

            <x-ui.select name="mapel_utama_id" label="Mapel Utama" :options="$mapel->pluck('nama_mapel', 'id')->all()"
                :selected="$guru?->mapel_utama_id" placeholder="— Tidak ada —" />

            <div class="flex items-center gap-space-sm pt-space-sm">
                <x-ui.btn type="submit" icon="save">Simpan</x-ui.btn>
                <x-ui.btn variant="outline" :href="route('master.guru')" icon="arrow_back">Batal</x-ui.btn>
            </div>
        </form>
    </x-ui.panel>
</x-layouts.app>
