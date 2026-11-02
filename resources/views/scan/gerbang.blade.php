<x-layouts.app title="Scanner Gerbang">
    {{-- Task 11 — port mockup `scanner_gerbang_presensi_siswa_smk` ke shell aplikasi. --}}
    <div id="scan-gerbang"
        data-mode="gerbang"
        data-url="{{ route('scan.gerbang.proses') }}"
        data-csrf="{{ csrf_token() }}"
        data-boleh="{{ $hari->bolehAbsensi() ? '1' : '0' }}"
        data-batas="{{ $pengaturan?->batas_tepat_waktu?->format('H:i') }}"
        data-tutup="{{ $pengaturan?->jam_tutup_gerbang?->format('H:i') }}"
        data-server="{{ now()->format('H:i:s') }}">

        <x-ui.page-header title="Scanner Gerbang" description="Presensi masuk dengan memindai QR kartu pelajar">
            <x-slot:actions>
                <span @class([
                    'inline-flex items-center gap-space-xs rounded-full px-space-md py-space-xs font-label-md font-bold',
                    'bg-tertiary-container text-on-tertiary-container' => $hari->bolehAbsensi(),
                    'bg-error-container text-on-error-container' => ! $hari->bolehAbsensi(),
                ])>
                    <span class="material-symbols-outlined text-[16px]">{{ $hari->bolehAbsensi() ? 'verified' : 'block' }}</span>
                    {{ $hari->status->label() }}
                </span>
            </x-slot:actions>
        </x-ui.page-header>

        @unless ($hari->bolehAbsensi())
            <div class="mb-space-lg flex items-start gap-space-sm rounded-xl bg-error-container px-space-lg py-space-md text-on-error-container">
                <span class="material-symbols-outlined text-[20px]">error</span>
                <div>
                    <p class="font-label-md font-bold">Scan diblokir</p>
                    <p class="text-body-sm">{{ $hari->pesanBlokir }}</p>
                </div>
            </div>
        @endunless

        <div class="grid gap-space-lg lg:grid-cols-2">
            {{-- ── Kolom kiri: mode, ringkasan, kamera ─────────────── --}}
            <div class="flex flex-col gap-space-lg">
                {{-- Mode presensi + info jam (mockup: toleransi & sisa waktu) --}}
                <x-ui.panel>
                    <div class="flex flex-col gap-space-sm">
                        <div class="grid grid-cols-2 gap-space-xs rounded-xl bg-surface-container p-space-xs">
                            <span class="rounded-lg bg-primary-container px-space-md py-space-sm text-center font-label-md font-bold text-on-primary">Masuk</span>
                            <a href="{{ route('scan.pulang') }}"
                                class="rounded-lg px-space-md py-space-sm text-center font-label-md font-bold text-on-surface-variant transition-colors hover:bg-surface-container-high">Pulang</a>
                        </div>

                        <div class="flex flex-wrap items-center justify-between gap-space-sm">
                            <span class="flex min-w-0 items-center gap-1.5">
                                <span class="material-symbols-outlined shrink-0 text-[18px] text-primary">alarm_on</span>
                                <span class="truncate font-label-md text-label-md text-on-surface">
                                    Batas tepat waktu:
                                    <strong class="font-headline-sm text-title-md text-primary" data-tampil-batas>{{ $pengaturan?->batas_tepat_waktu?->format('H:i') ?? '—' }}</strong>
                                    &middot; Tutup:
                                    <strong class="font-headline-sm text-title-md text-on-surface" data-tampil-tutup>{{ $pengaturan?->jam_tutup_gerbang?->format('H:i') ?? '—' }}</strong>
                                </span>
                            </span>
                            <span data-countdown @class([
                                'flex shrink-0 items-center gap-1.5 rounded-full px-2.5 py-1',
                                'bg-tertiary-container text-on-tertiary-container' => $hari->bolehAbsensi(),
                                'bg-surface-container text-on-surface-variant' => ! $hari->bolehAbsensi(),
                            ])>
                                <span class="h-1.5 w-1.5 rounded-full bg-current animate-ping"></span>
                                <span class="font-label-sm font-bold tracking-tight" data-countdown-teks>{{ $hari->bolehAbsensi() ? 'Memuat…' : 'Tidak aktif' }}</span>
                            </span>
                        </div>
                    </div>
                </x-ui.panel>

                {{-- Counter chips (mockup: Tepat Waktu / Terlambat / Dispensasi) --}}
                <div class="grid grid-cols-3 gap-2">
                    <div class="flex flex-col items-center justify-center rounded-xl bg-tertiary-container p-2 text-on-tertiary-container">
                        <span class="font-label-sm uppercase opacity-90">Tepat Waktu</span>
                        <span class="font-display-lg text-headline-sm font-bold leading-tight" data-kpi-tepat>{{ $tepatWaktu }}</span>
                    </div>
                    <div class="flex flex-col items-center justify-center rounded-xl bg-secondary-container p-2 text-on-secondary-container">
                        <span class="font-label-sm uppercase opacity-90">Terlambat</span>
                        <span class="font-display-lg text-headline-sm font-bold leading-tight text-error" data-kpi-terlambat>{{ $terlambat }}</span>
                    </div>
                    <div class="flex flex-col items-center justify-center rounded-xl bg-surface-container-highest p-2 text-on-primary-fixed">
                        <span class="font-label-sm uppercase opacity-90">Total Hadir</span>
                        <span class="font-display-lg text-headline-sm font-bold leading-tight" data-kpi-total>{{ $totalHadir }}</span>
                    </div>
                </div>

                {{-- Viewfinder kamera (mockup: HUD gelap + sudut bidik) --}}
                <section class="relative w-full overflow-hidden rounded-2xl bg-inverse-surface shadow-md">
                    <div class="relative flex aspect-[4/3] w-full flex-col items-center justify-between p-4">
                        {{-- Overlay atas: status kamera + toggle suara --}}
                        <div class="z-20 flex w-full items-center justify-between">
                            <span @class([
                                'flex items-center gap-1.5 rounded-full px-2.5 py-1.5 shadow-sm backdrop-blur-md',
                                'bg-inverse-surface/80' => $hari->bolehAbsensi(),
                                'bg-error/80' => ! $hari->bolehAbsensi(),
                            ])>
                                <span class="material-symbols-outlined text-[18px] text-tertiary-fixed animate-pulse">contactless</span>
                                <span class="font-label-sm text-inverse-on-surface" data-status-kamera>{{ $hari->bolehAbsensi() ? 'Kamera siap' : 'Kamera nonaktif' }}</span>
                            </span>
                            <button type="button" id="btn-suara" aria-label="Toggle suara beep"
                                class="flex h-10 w-10 items-center justify-center rounded-full bg-inverse-surface/80 text-inverse-on-surface transition-all hover:bg-surface-tint active:scale-95">
                                <span class="material-symbols-outlined text-[20px]" data-ikon-suara>volume_up</span>
                            </button>
                        </div>

                        {{-- Bidik tengah dengan beam --}}
                        <div class="relative my-auto flex h-56 w-56 items-center justify-center">
                            <span class="absolute left-0 top-0 h-8 w-8 rounded-tl-xl bg-primary-container"></span>
                            <span class="absolute right-0 top-0 h-8 w-8 rounded-tr-xl bg-primary-container"></span>
                            <span class="absolute bottom-0 left-0 h-8 w-8 rounded-bl-xl bg-primary-container"></span>
                            <span class="absolute bottom-0 right-0 h-8 w-8 rounded-br-xl bg-primary-container"></span>
                            {{-- Mount kamera html5-qrcode --}}
                            <div id="scanner-gerbang" class="relative flex h-48 w-48 items-center justify-center overflow-hidden rounded-lg bg-surface/5 backdrop-blur-[1px]">
                                <span class="material-symbols-outlined text-[72px] text-inverse-on-surface/30" data-ikon-mounter>qr_code_2</span>
                            </div>
                        </div>

                        {{-- Hint bawah --}}
                        <div class="z-20 w-full text-center">
                            <p class="inline-flex items-center gap-1.5 rounded-full bg-inverse-surface/90 px-3 py-1.5 font-label-md text-label-md text-inverse-on-surface backdrop-blur-md">
                                <span class="material-symbols-outlined text-[16px] text-tertiary-fixed">qr_code_scanner</span>
                                Arahkan kamera ke QR kartu pelajar
                            </p>
                        </div>
                    </div>
                </section>

                {{-- Input manual petugas --}}
                @if ($bolehManual)
                    <form id="form-manual" hidden class="flex flex-col gap-space-sm rounded-xl border border-outline-variant bg-surface-container-lowest p-space-lg">
                        <p class="font-title-md text-title-md">Input Manual NISN</p>
                        <p class="text-body-sm text-on-surface-variant">Untuk siswa yang kartunya hilang atau scan setelah gerbang ditutup (wajib mengisi keterangan bila diminta).</p>
                        <x-ui.input name="nisn" label="NISN" placeholder="0081234567" autocomplete="off" required />
                        <x-ui.textarea name="keterangan" label="Keterangan" placeholder="Mis. kartu rusak / izin petugas" rows="2" />
                        <div class="flex gap-space-sm">
                            <x-ui.btn type="submit" icon="person_search">Catat Sekarang</x-ui.btn>
                            <x-ui.btn type="button" id="btn-tutup-manual" variant="ghost">Tutup</x-ui.btn>
                        </div>
                    </form>
                @endif
            </div>

            {{-- ── Kolom kanan: kartu hasil, aksi piket, log ────────── --}}
            <div class="flex flex-col gap-space-lg">
                {{-- Kartu hasil scan terakhir --}}
                <section id="kartu-hasil" class="relative overflow-hidden rounded-2xl bg-surface-container-lowest p-space-md shadow-sm"
                    data-placeholder="{{ $hari->bolehAbsensi() ? 'Menunggu pemindaian…' : 'Scan tidak aktif hari ini.' }}">
                    <div data-isi-kartu class="flex flex-col items-center justify-center gap-space-sm rounded-xl border-2 border-dashed border-outline-variant py-space-xl text-on-surface-variant">
                        <span class="material-symbols-outlined text-[40px]">qr_code_scanner</span>
                        <p class="font-label-md">{{ $hari->bolehAbsensi() ? 'Menunggu pemindaian…' : 'Scan tidak aktif hari ini.' }}</p>
                    </div>
                </section>

                {{-- Aksi petugas piket --}}
                <x-ui.panel>
                    <p class="font-headline-sm text-title-md font-bold text-on-surface">Aksi Petugas Piket</p>
                    <p class="mb-space-sm font-label-sm text-label-sm text-on-surface-variant">Bertugas: {{ auth()->user()->name }}</p>
                    <div class="grid grid-cols-3 gap-2">
                        <button type="button" disabled title="Task 14 — pengajuan izin orang tua"
                            class="flex min-h-[52px] flex-col items-center justify-center rounded-xl bg-secondary-container p-2 text-center text-on-secondary-container opacity-60 shadow-sm">
                            <span class="material-symbols-outlined text-[22px]">schedule_send</span>
                            <span class="mt-1 font-label-sm font-bold leading-tight">Toleransi Izin</span>
                        </button>
                        @if ($bolehManual)
                            <button type="button" id="btn-manual" data-expanded="0"
                                class="flex min-h-[52px] flex-col items-center justify-center rounded-xl bg-primary-container p-2 text-center text-on-primary shadow-sm transition-transform active:scale-95">
                                <span class="material-symbols-outlined text-[22px]">keyboard</span>
                                <span class="mt-1 font-label-sm font-bold leading-tight">Input Manual</span>
                            </button>
                        @else
                            <span title="Hanya piket/admin"
                                class="flex min-h-[52px] flex-col items-center justify-center rounded-xl bg-surface-container-high p-2 text-center text-on-surface-variant opacity-50 shadow-sm">
                                <span class="material-symbols-outlined text-[22px]">keyboard</span>
                                <span class="mt-1 font-label-sm font-bold leading-tight">Input Manual</span>
                            </span>
                        @endif
                        <button type="button" disabled title="Task 13 — karcis pulang"
                            class="flex min-h-[52px] flex-col items-center justify-center rounded-xl bg-surface-container-high p-2 text-center text-primary opacity-60 shadow-sm">
                            <span class="material-symbols-outlined text-[22px]">confirmation_number</span>
                            <span class="mt-1 font-label-sm font-bold leading-tight">Karcis Pulang</span>
                        </button>
                    </div>
                </x-ui.panel>

                {{-- Log scan terkini --}}
                <x-ui.panel title="Log Scan Terkini" subtitle="10 pencatatan terakhir hari ini">
                    <ul id="log-scan" class="flex flex-col divide-y divide-outline-variant">
                        @forelse ($log as $baris)
                            <li class="flex items-center gap-space-sm py-space-sm" data-log-id="{{ $baris->id }}">
                                <x-ui.avatar :name="$baris->siswa->nama" size="sm" />
                                <div class="min-w-0 flex-1">
                                    <p class="truncate font-label-md font-bold text-on-surface">{{ $baris->siswa->nama }}</p>
                                    <p class="truncate font-body-sm text-body-sm text-on-surface-variant">
                                        {{ $baris->siswa->kelasAktif?->nama_kelas ?? '—' }} &middot; {{ $baris->metode === 'manual' ? 'Manual' : 'QR' }}
                                    </p>
                                </div>
                                <span class="shrink-0 font-label-sm text-label-sm text-on-surface-variant">{{ $baris->waktu_scan?->format('H:i') ?? '—' }}</span>
                                <x-ui.status-badge :status="$baris->status" class="shrink-0" />
                            </li>
                        @empty
                            <li class="py-space-md text-center text-body-sm text-on-surface-variant" data-log-kosong>Belum ada scan hari ini.</li>
                        @endforelse
                    </ul>
                </x-ui.panel>
            </div>
        </div>
    </div>

    @push('scripts')
        @vite('resources/js/scan.js')
    @endpush
</x-layouts.app>
