<x-layouts.app>
    @php
        $statuses = \App\Enums\StatusAbsensi::cases();
    @endphp

    <x-ui.page-header title="Galeri Komponen UI" :breadcrumb="['Developer', 'Galeri Komponen']"
        description="Semua varian komponen x-ui/* — acuan konsistensi seluruh halaman.">
        <x-slot:actions>
            <x-ui.btn variant="outline" icon="palette">Design System v1</x-ui.btn>
        </x-slot:actions>
    </x-ui.page-header>

    {{-- KPI card --}}
    <p class="mb-space-sm font-label-sm uppercase tracking-widest text-outline">KPI Card</p>
    <div class="mb-space-lg grid grid-cols-1 gap-space-md sm:grid-cols-2 lg:grid-cols-4">
        <x-ui.kpi-card label="Total Siswa Hari Ini" value="1.248" icon="groups" tone="primary">
            Dari 48 kelas aktif
        </x-ui.kpi-card>
        <x-ui.kpi-card label="Hadir Tepat Waktu" value="98,4%" icon="verified" tone="tertiary">
            1.228 siswa
        </x-ui.kpi-card>
        <x-ui.kpi-card label="Terlambat" value="12" icon="schedule">
            Toleransi 07:15 WIB
        </x-ui.kpi-card>
        <x-ui.kpi-card label="Belum Absen" value="8" icon="warning" tone="error">
            Perlu tindak lanjut
        </x-ui.kpi-card>
    </div>

    {{-- Status badge + badge --}}
    <p class="mb-space-sm font-label-sm uppercase tracking-widest text-outline">Status Badge & Badge</p>
    <x-ui.panel class="mb-space-lg">
        <div class="flex flex-wrap items-center gap-space-sm">
            @foreach ($statuses as $status)
                <x-ui.status-badge :status="$status" />
            @endforeach
            <x-ui.badge tone="primary">Info</x-ui.badge>
            <x-ui.badge tone="warning">Perhatian</x-ui.badge>
            <x-ui.badge tone="danger">Kritis</x-ui.badge>
            <x-ui.badge tone="neutral">Draft</x-ui.badge>
        </div>
    </x-ui.panel>

    {{-- Tombol --}}
    <p class="mb-space-sm font-label-sm uppercase tracking-widest text-outline">Tombol</p>
    <x-ui.panel class="mb-space-lg">
        <div class="flex flex-wrap items-center gap-space-sm">
            <x-ui.btn icon="check">Tandai Semua Hadir</x-ui.btn>
            <x-ui.btn variant="secondary" icon="download">Unduh</x-ui.btn>
            <x-ui.btn variant="outline" icon="filter_list">Filter</x-ui.btn>
            <x-ui.btn variant="ghost" icon="open_in_new">Detail</x-ui.btn>
            <x-ui.btn variant="danger" icon="delete">Hapus</x-ui.btn>
            <x-ui.btn icon="check" disabled>Disabled</x-ui.btn>
        </div>
    </x-ui.panel>

    {{-- Timeline --}}
    <div class="mb-space-lg grid grid-cols-1 gap-space-md lg:grid-cols-2">
        <x-ui.panel title="Timeline (Portal Orang Tua)" subtitle="Perjalanan hari ini">
            <ul class="mt-space-sm">
                <x-ui.timeline-item title="Berangkat ke Sekolah" time="06:45 WIB" icon="directions_run"
                    tone="success">
                    Gate 2 · QR Kodim · Hadir tepat waktu
                </x-ui.timeline-item>
                <x-ui.timeline-item title="Presensi Mapel" time="07:15 WIB" icon="menu_book" tone="primary">
                    Matematika · Budi Suryanto, S.Pd · Hadir
                </x-ui.timeline-item>
                <x-ui.timeline-item title="Pulang" time="15:35 WIB" icon="logout" tone="success" :last="true">
                    Gate 1 · Pulang bersama
                </x-ui.timeline-item>
            </ul>
        </x-ui.panel>

        <x-ui.panel title="Avatar & Empty State">
            <div class="mb-space-md flex items-center gap-space-sm">
                <x-ui.avatar name="Muhammad Rizky Pratama" size="lg" />
                <x-ui.avatar name="Siti Nurhaliza" size="md" />
                <x-ui.avatar name="Budi" size="sm" />
            </div>
            <div class="rounded-lg border border-dashed border-outline-variant">
                <x-ui.empty-state icon="hourglass_empty" title="Tidak ada siswa terlambat hari ini"
                    description="Semua siswa yang terjadwal sudah absen sebelum batas toleransi." />
            </div>
        </x-ui.panel>
    </div>

    {{-- Tabel --}}
    <p class="mb-space-sm font-label-sm uppercase tracking-widest text-outline">Tabel Data</p>
    <x-ui.panel :flush="true" class="mb-space-lg">
        <x-ui.filter-bar class="m-space-lg">
            <div class="min-w-40">
                <x-ui.select name="demo_kelas" label="Kelas" :options="['X TKJ 1' => 'X TKJ 1', 'X TKJ 2' => 'X TKJ 2']"
                    placeholder="Semua kelas" />
            </div>
            <div class="min-w-40">
                <x-ui.input name="demo_tanggal" label="Tanggal" type="date" value="2026-10-30" />
            </div>
            <div class="min-w-40">
                <x-ui.select name="demo_status" label="Status"
                    :options="collect(\App\Enums\StatusAbsensi::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all()"
                    placeholder="Semua status" />
            </div>
            <x-ui.btn type="submit" icon="search" size="sm">Terapkan</x-ui.btn>
        </x-ui.filter-bar>

        <x-ui.data-table>
            <x-slot:head>
                <x-ui.th>No</x-ui.th>
                <x-ui.th>Nama Siswa</x-ui.th>
                <x-ui.th>Kelas</x-ui.th>
                <x-ui.th>Status Gerbang</x-ui.th>
                <x-ui.th class="text-right">Aksi</x-ui.th>
            </x-slot:head>

            @php($contoh = [
                ['Muhammad Rizky Pratama', 'X TKJ 1', 'hadir_tepat_waktu'],
                ['Siti Nurhaliza', 'X TKJ 1', 'terlambat'],
                ['Andi Pratama', 'X TKJ 2', 'izin'],
                ['Dewi Lestari', 'X TKJ 2', 'alpa'],
            ])
            @foreach ($contoh as [$nama, $kelas, $status])
                <tr class="hover:bg-surface-container-low">
                    <x-ui.td class="tabular text-on-surface-variant">{{ $loop->iteration }}</x-ui.td>
                    <x-ui.td>
                        <div class="flex items-center gap-space-sm">
                            <x-ui.avatar :name="$nama" size="sm" />
                            <span class="font-label-lg text-label-lg">{{ $nama }}</span>
                        </div>
                    </x-ui.td>
                    <x-ui.td>{{ $kelas }}</x-ui.td>
                    <x-ui.td><x-ui.status-badge :status="$status" /></x-ui.td>
                    <x-ui.td class="text-right">
                        <x-ui.btn variant="ghost" size="sm" icon="open_in_new">Detail</x-ui.btn>
                    </x-ui.td>
                </tr>
            @endforeach
        </x-ui.data-table>
    </x-ui.panel>

    {{-- Form --}}
    <p class="mb-space-sm font-label-sm uppercase tracking-widest text-outline">Formulir</p>
    <div class="grid grid-cols-1 gap-space-md lg:grid-cols-2">
        <x-ui.panel title="Input Dasar" subtitle="Label, required, hint, dan error">
            <div class="space-y-space-md pt-space-sm">
                <x-ui.input name="demo_nama" label="Nama Lengkap" placeholder="cth. Budi Santoso" required
                    hint="Sesuai akta kelahiran." />
                <x-ui.input name="demo_gagal" label="NISN" value="0081" error="NISN harus 10 digit angka." />
                <x-ui.select name="demo_jk" label="Jenis Kelamin"
                    :options="['laki-laki' => 'Laki-laki', 'perempuan' => 'Perempuan']" placeholder="Pilih…" />
                <x-ui.textarea name="demo_alasan" label="Keterangan" placeholder="Keterangan tambahan…" />
            </div>
        </x-ui.panel>

        <x-ui.panel title="Toast & Pagination">
            <div class="flex flex-wrap gap-space-sm pt-space-sm">
                <a href="{{ route('ui.demo') }}?toast=sukses" class="inline-flex items-center gap-space-xs rounded-xl bg-primary-container px-space-lg py-space-sm font-label-lg text-on-primary-container hover:bg-primary hover:text-on-primary">
                    <span class="material-symbols-outlined text-[18px]">check_circle</span> Demo toast sukses
                </a>
                <a href="{{ route('ui.demo') }}?toast=gagal" class="inline-flex items-center gap-space-xs rounded-xl bg-error px-space-lg py-space-sm font-label-lg text-on-error hover:opacity-90">
                    <span class="material-symbols-outlined text-[18px]">error</span> Demo toast gagal
                </a>
            </div>

            <div class="mt-space-md rounded-lg border border-outline-variant">
                <x-ui.pagination :paginator="new \Illuminate\Pagination\LengthAwarePaginator(range(1, 4), 42, 3, ['path' => url()->current()])" />
            </div>
        </x-ui.panel>
    </div>
</x-layouts.app>
