<x-layouts.guest title="Karcis Pulang">
    {{-- Task 13 — karcis pulang cepat: ditunjukkan ke penjaga gerbang, bisa dicetak. --}}
    @php
        $siswa = $baris->siswa;
    @endphp

    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: #fff !important; padding: 0 !important; }
        }
    </style>

    <section class="rounded-2xl border-2 border-outline-variant bg-surface-container-lowest p-space-lg shadow-sm">
        <div class="mb-space-md border-b-2 border-dashed border-outline-variant pb-space-md text-center">
            <p class="font-headline-sm text-title-md font-bold text-primary">{{ config('app.name') }}</p>
            <p class="font-label-md uppercase tracking-widest text-on-surface-variant">Karcis Pulang Cepat</p>
            <p class="mt-space-xs inline-block rounded-full bg-secondary-container px-space-md py-space-xs font-headline-sm text-title-md font-bold text-on-secondary-fixed">
                {{ $baris->kode_karcis }}
            </p>
        </div>

        <dl class="flex flex-col gap-space-sm text-body-md">
            <div class="flex justify-between gap-space-sm">
                <dt class="text-on-surface-variant">Nama</dt>
                <dd class="text-right font-bold text-on-surface">{{ $siswa->nama }}</dd>
            </div>
            <div class="flex justify-between gap-space-sm">
                <dt class="text-on-surface-variant">NISN</dt>
                <dd class="text-right font-mono text-on-surface">{{ $siswa->nisn }}</dd>
            </div>
            <div class="flex justify-between gap-space-sm">
                <dt class="text-on-surface-variant">Kelas</dt>
                <dd class="text-right text-on-surface">{{ $siswa->kelasAktif?->nama_kelas ?? '—' }}</dd>
            </div>
            <div class="flex justify-between gap-space-sm">
                <dt class="text-on-surface-variant">Tanggal</dt>
                <dd class="text-right text-on-surface">{{ $baris->tanggal->isoFormat('D MMMM YYYY') }}</dd>
            </div>
            <div class="flex justify-between gap-space-sm">
                <dt class="text-on-surface-variant">Waktu Pulang</dt>
                <dd class="text-right text-on-surface">{{ $baris->waktu_scan?->format('H:i') ?? '—' }} WIB</dd>
            </div>
            <div class="flex justify-between gap-space-sm">
                <dt class="text-on-surface-variant">Keterangan</dt>
                <dd class="max-w-[60%] text-right text-on-surface">{{ $baris->keterangan ?? '—' }}</dd>
            </div>
            <div class="flex justify-between gap-space-sm">
                <dt class="text-on-surface-variant">Petugas</dt>
                <dd class="text-right text-on-surface">{{ $baris->scannedBy?->name ?? '—' }}</dd>
            </div>
        </dl>

        <p class="mt-space-md rounded-lg bg-surface-container-low p-space-sm text-center text-body-sm text-on-surface-variant">
            Tunjukkan karcis ini kepada penjaga gerbang. Karcis hanya berlaku pada tanggal {{ $baris->tanggal->isoFormat('D MMM YYYY') }}.
        </p>
    </section>

    <div class="no-print mt-space-md flex justify-center gap-space-sm">
        <x-ui.btn icon="print" onclick="window.print()">Cetak Karcis</x-ui.btn>
        <x-ui.btn variant="outline" :href="route('scan.pulang')">Kembali ke Scanner</x-ui.btn>
    </div>
</x-layouts.guest>
