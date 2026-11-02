<x-layouts.guest title="Masuk">
    {{-- Kartu login — gaya konsisten dengan design system mockup --}}
    <div class="mb-space-lg flex flex-col items-center text-center">
        <span
            class="material-symbols-outlined mb-space-sm flex h-14 w-14 items-center justify-center rounded-xl bg-primary-container text-[28px] text-on-primary-container">qr_code_scanner</span>
        <h1 class="font-headline-md text-headline-md tracking-tight">SMK Attendance OS</h1>
        <p class="text-body-md text-on-surface-variant">Masuk dengan akun yang diberikan sekolah</p>
    </div>

    <div class="rounded-xl bg-surface-container-lowest p-space-lg shadow-sm">
        <form method="POST" action="{{ route('login') }}" class="space-y-space-md">
            @csrf

            <x-ui.input name="email" label="Email" type="email" placeholder="nama@sekolah.test" required
                :value="old('email')" autofocus />

            <x-ui.input name="password" label="Kata Sandi" type="password" placeholder="••••••••" required />

            @if ($errors->any())
                <div class="flex items-center gap-space-sm rounded-lg bg-error-container px-space-md py-space-sm text-body-sm text-on-error-container">
                    <span class="material-symbols-outlined text-[18px]">error</span>
                    {{ $errors->first() }}
                </div>
            @endif

            <x-ui.btn type="submit" icon="login" class="w-full">Masuk</x-ui.btn>
        </form>
    </div>

    <p class="mt-space-lg text-center text-body-sm text-on-surface-variant">
        Lupa akun? Hubungi wali kelas atau admin sekolah.
    </p>
</x-layouts.guest>
