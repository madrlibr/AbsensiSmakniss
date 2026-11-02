<x-layouts.app>
    <x-ui.page-header :title="$judul" :breadcrumb="['Beranda', $judul]" />

    <x-ui.panel>
        <x-ui.empty-state :icon="$ikon" title="Halaman ini sedang disiapkan"
            :description="$pesan">
            <x-ui.badge tone="primary">Task berikutnya</x-ui.badge>
        </x-ui.empty-state>
    </x-ui.panel>
</x-layouts.app>
