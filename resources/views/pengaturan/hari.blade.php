<x-layouts.app title="Pengaturan Jam Presensi">
    <x-ui.page-header title="Pengaturan Jam Presensi" :breadcrumb="['Manajemen Sistem', 'Pengaturan Jam']"
        description="Aturan waktu per hari: buka gerbang, batas tepat waktu, tutup gerbang, auto-alpa, dan jendela pulang. Hari berstatus Libur dilewati mesin absensi (termasuk job auto-alpa).">
        <x-slot:actions>
            <form method="POST" action="{{ route('pengaturan.hari.reset') }}"
                data-confirm="Kembalikan semua pengaturan jam ke default? Perubahan hari ini akan hilang.">
                @csrf
                <x-ui.btn variant="outline" icon="restart_alt">Reset Default</x-ui.btn>
            </form>
            <x-ui.btn variant="ghost" icon="beach_access" :href="route('pengaturan.libur')">Hari Libur Rentang</x-ui.btn>
            <x-ui.btn type="submit" form="form-pengaturan-hari" icon="save">Simpan Perubahan</x-ui.btn>
        </x-slot:actions>
    </x-ui.page-header>

    @if ($ringkasan)
        <div class="mb-space-lg grid grid-cols-1 gap-space-md sm:grid-cols-2 xl:grid-cols-4">
            <x-ui.kpi-card label="Batas Tepat Waktu" :value="($ringkasan->batas_tepat_waktu?->format('H:i') ?? '—') . ' WIB'"
                icon="schedule" />
            <x-ui.kpi-card label="Tutup Gerbang Masuk" :value="($ringkasan->jam_tutup_gerbang?->format('H:i') ?? '—') . ' WIB'"
                icon="lock" tone="secondary" />
            <x-ui.kpi-card label="Trigger Auto-Alpa" :value="($ringkasan->jam_auto_alpa?->format('H:i') ?? '—') . ' WIB'"
                icon="crisis_alert" tone="error" />
            <x-ui.kpi-card label="Akhir Window Pulang" :value="($ringkasan->akhir_scan_pulang?->format('H:i') ?? '—') . ' WIB'"
                icon="logout" tone="tertiary" />
        </div>
    @endif

    <form id="form-pengaturan-hari" method="POST" action="{{ route('pengaturan.hari.simpan') }}">
        @csrf

        <div class="grid grid-cols-1 gap-space-lg xl:grid-cols-2">
            @foreach ($semua as $setting)
                @php
                    $kunci = $setting->hari->value;
                    $statusLama = old('hari.' . $kunci . '.status_hari', $setting->status_hari);
                @endphp
                <x-ui.panel data-hari-card :title="$setting->hari->label()">
                    <x-slot:actions>
                        <div data-segmen class="inline-flex rounded-lg border border-outline-variant p-space-xs">
                            @foreach (['masuk' => 'Masuk', 'libur' => 'Libur'] as $nilai => $label)
                                <label
                                    class="cursor-pointer rounded-md px-space-md py-space-xs font-label-md {{ $statusLama === $nilai ? 'bg-primary-container text-on-primary-container' : 'text-on-surface-variant' }}">
                                    <input type="radio" name="hari[{{ $kunci }}][status_hari]" value="{{ $nilai }}"
                                        class="sr-only" @checked($statusLama === $nilai)>
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                    </x-slot:actions>

                    <div class="grid grid-cols-1 gap-space-md sm:grid-cols-2">
                        @foreach ($bidang as $kolom => $meta)
                            @php
                                $nilaiAwal = $setting->{$kolom};
                                $nilaiInput = $nilaiAwal instanceof \Illuminate\Support\Carbon
                                    ? $nilaiAwal->format('H:i')
                                    : $nilaiAwal;
                                $nilaiInput = old('hari.' . $kunci . '.' . $kolom, $nilaiInput);
                            @endphp
                            <x-ui.input :name="'hari[' . $kunci . '][' . $kolom . ']'"
                                :label="$meta['label']" :type="$meta['type']" :hint="$meta['hint']"
                                :value="$nilaiInput" placeholder="—" />
                        @endforeach
                    </div>

                    <div class="mt-space-md">
                        <x-ui.input :name="'hari[' . $kunci . '][keterangan]'" label="Keterangan"
                            placeholder="cth. Jumat — jam pelajaran pendek"
                            :value="old('hari.' . $kunci . '.keterangan', $setting->keterangan)" />
                    </div>
                </x-ui.panel>
            @endforeach
        </div>
    </form>
</x-layouts.app>
