<!DOCTYPE html>
<html lang="id" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Dashboard' }} — {{ config('app.name') }}</title>

    {{-- Font & ikon identik dengan mockup PrdAndDesign (Google Fonts) --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap"
        rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200"
        rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>

<body class="h-full bg-surface font-body-md text-body-md text-on-surface antialiased">
    @php
        $user = auth()->user();
        $menu = \App\Support\Menu::untuk($user);
        $tahunAjaranAktif = \App\Models\TahunAjaran::aktif();
    @endphp

    {{-- Backdrop untuk sidebar mobile --}}
    <div id="sidebar-backdrop" class="fixed inset-0 z-40 hidden bg-inverse-surface/40 backdrop-blur-sm lg:hidden"></div>

    {{-- ── Sidebar ────────────────────────────────────────────────────────── --}}
    <aside id="sidebar"
        class="fixed inset-y-0 left-0 z-50 flex w-72 -translate-x-full flex-col border-r border-outline-variant bg-surface-container-lowest transition-transform duration-200 lg:translate-x-0">
        {{-- Brand --}}
        <div class="flex items-center gap-space-sm px-space-lg py-space-lg">
            <span
                class="material-symbols-outlined flex h-10 w-10 items-center justify-center rounded-xl bg-primary-container text-[22px] text-on-primary-container">qr_code_scanner</span>
            <div>
                <p class="font-headline-sm text-headline-sm tracking-tight">SMK Attendance OS</p>
                <p class="text-body-sm text-on-surface-variant">Sistem Presensi Sekolah</p>
            </div>
            <button id="sidebar-close" type="button" aria-label="Tutup menu"
                class="ml-auto rounded-lg p-space-xs text-on-surface-variant hover:bg-surface-container-low lg:hidden">
                <span class="material-symbols-outlined text-[20px]">close</span>
            </button>
        </div>

        {{-- Navigasi per role --}}
        <nav class="flex-1 space-y-space-lg overflow-y-auto px-space-md pb-space-lg" aria-label="Menu utama">
            @foreach ($menu as $grup => $items)
                <div>
                    <p class="px-space-md pb-space-xs font-label-sm text-label-sm uppercase tracking-widest text-outline">
                        {{ $grup }}</p>
                    <ul class="space-y-space-xs">
                        @foreach ($items as $item)
                            @php
                                $namaRute = request()->route()?->getName();
                                $aktif = $namaRute === $item['route'] || str_starts_with((string) $namaRute, $item['route'] . '.');
                                $jumlahBadge = \App\Support\Menu::hitungBadge($item['badge'] ?? '');
                            @endphp
                            <li>
                                <a href="{{ route($item['route']) }}"
                                    @class([
                                        'flex items-center gap-space-sm rounded-lg px-space-md py-space-sm font-label-lg transition-colors',
                                        'bg-secondary-container text-on-secondary-container' => $aktif,
                                        'text-on-surface-variant hover:bg-surface-container-low' => !$aktif,
                                    ])
                                    @if ($aktif) aria-current="page" @endif>
                                    <span
                                        class="material-symbols-outlined text-[20px]">{{ $item['icon'] }}</span>
                                    <span>{{ $item['label'] }}</span>
                                    @if ($jumlahBadge)
                                        <span
                                            class="ml-auto rounded-full bg-error px-space-xs font-label-sm text-on-error">{{ $jumlahBadge }}</span>
                                    @endif
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </nav>

        {{-- Footer sidebar --}}
        <div class="border-t border-outline-variant px-space-lg py-space-md">
            <p class="text-body-sm text-on-surface-variant">
                {{ $tahunAjaranAktif?->nama ?? 'Belum ada tahun ajaran aktif' }}
            </p>
            <p class="text-body-sm text-outline">v1.0 · UJIKOM</p>
        </div>
    </aside>

    {{-- ── Area konten ────────────────────────────────────────────────────── --}}
    <div class="min-h-screen lg:pl-72">
        {{-- Topbar --}}
        <header
            class="fixed left-0 right-0 top-0 z-30 flex h-16 items-center justify-between border-b border-outline-variant bg-surface-container-lowest/90 px-space-md backdrop-blur-xl lg:left-72 lg:px-space-lg">
            <div class="flex items-center gap-space-sm">
                <button id="sidebar-open" type="button" aria-label="Buka menu"
                    class="rounded-lg p-space-xs text-on-surface hover:bg-surface-container-low lg:hidden">
                    <span class="material-symbols-outlined text-[24px]">menu</span>
                </button>

                <div
                    class="flex items-center gap-space-xs rounded-full bg-secondary-fixed px-space-md py-space-xs text-on-secondary-fixed">
                    <span class="material-symbols-outlined text-[16px]">school</span>
                    <span class="font-label-sm text-label-sm uppercase tracking-wider">{{ config('app.name') }}</span>
                </div>

                <div class="flex items-center gap-space-xs rounded-full bg-surface-container-high px-space-md py-space-xs">
                    <span class="material-symbols-outlined text-[16px] text-primary">event</span>
                    <span class="font-label-sm text-label-sm">{{ $tahunAjaranAktif?->nama ?? '—' }}</span>
                </div>

                <div class="hidden items-center gap-space-xs rounded-full bg-surface-container px-space-md py-space-xs xl:flex">
                    <span class="material-symbols-outlined text-[16px] text-secondary">schedule</span>
                    <span id="live-clock" class="tabular font-label-sm text-label-sm">--:--:-- WIB</span>
                </div>
            </div>

            <div class="flex items-center gap-space-sm">
                @if ($user)
                    {{-- Notifikasi (aktif penuh pada Task 18) --}}
                    <a href="{{ route('portal.notifikasi') }}" aria-label="Notifikasi"
                        class="relative rounded-full p-space-xs text-on-surface-variant hover:bg-surface-container-low">
                        <span class="material-symbols-outlined text-[22px]">notifications</span>
                    </a>

                    <div class="flex items-center gap-space-sm rounded-full bg-surface-container-low p-space-xs pr-space-md">
                        <span
                            class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-secondary-fixed font-title-md text-body-sm text-on-secondary-fixed">
                            {{ strtoupper(mb_substr($user->name, 0, 1)) }}</span>
                        <div class="hidden leading-tight sm:block">
                            <p class="font-label-md text-label-md">{{ $user->name }}</p>
                            <p class="text-body-sm text-on-surface-variant">{{ $user->role->shortLabel() }}</p>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" aria-label="Keluar"
                            class="rounded-full p-space-xs text-on-surface-variant hover:bg-error-container hover:text-on-error-container">
                            <span class="material-symbols-outlined text-[22px]">logout</span>
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}"
                        class="rounded-xl bg-primary-container px-space-md py-space-xs font-label-lg text-on-primary-container hover:bg-primary hover:text-on-primary">Masuk</a>
                @endif
            </div>
        </header>

        {{-- Konten halaman (pt-20 = offset atas agar lepas topbar fixed h-16) --}}
        <main class="px-gutter-mobile pb-space-lg pt-20 lg:px-gutter">
            {{ $slot }}
        </main>
    </div>

    {{-- Toast flash message --}}
    <div id="toast-root" class="pointer-events-none fixed bottom-space-lg right-space-lg z-[60] flex flex-col gap-space-sm">
        @foreach ([
            'sukses' => ['ikon' => 'check_circle', 'kelas' => 'bg-tertiary-fixed text-on-tertiary-fixed'],
            'gagal' => ['ikon' => 'error', 'kelas' => 'bg-error text-on-error'],
            'info' => ['ikon' => 'info', 'kelas' => 'bg-primary-fixed text-on-primary-fixed'],
        ] as $kunci => $konfig)
            @if (session($kunci))
                <div data-toast
                    class="pointer-events-auto hidden items-center gap-space-sm rounded-xl bg-surface-container-lowest px-space-lg py-space-md shadow-lg">
                    <span
                        class="flex h-8 w-8 items-center justify-center rounded-full {{ $konfig['kelas'] }}">
                        <span class="material-symbols-outlined text-[18px]">{{ $konfig['ikon'] }}</span>
                    </span>
                    <p class="font-label-md text-label-md">{{ session($kunci) }}</p>
                    <button data-toast-close type="button" aria-label="Tutup pemberitahuan"
                        class="ml-space-md rounded-full p-space-xs text-on-surface-variant hover:bg-surface-container-low">
                        <span class="material-symbols-outlined text-[18px]">close</span>
                    </button>
                </div>
            @endif
        @endforeach
    </div>

    @stack('scripts')
</body>

</html>
