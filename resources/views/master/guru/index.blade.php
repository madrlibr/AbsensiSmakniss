<x-layouts.app title="Data Guru">
    <x-ui.page-header title="Data Guru" :breadcrumb="['Master Data', 'Guru']"
        description="Profil guru beserta akun login untuk absensi mapel & pengampuan kelas.">
        <x-slot:actions>
            <x-ui.btn :href="route('master.guru.create')" icon="add">Tambah Guru</x-ui.btn>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.panel :flush="true">
        <x-ui.data-table>
            <x-slot:head>
                <x-ui.th>Guru</x-ui.th>
                <x-ui.th>NIP</x-ui.th>
                <x-ui.th>Mapel Utama</x-ui.th>
                <x-ui.th>Telepon</x-ui.th>
                <x-ui.th>Akun</x-ui.th>
                <x-ui.th class="text-right">Aksi</x-ui.th>
            </x-slot:head>

            @forelse ($daftar as $guru)
                <tr class="hover:bg-surface-container-low">
                    <x-ui.td>
                        <div class="flex items-center gap-space-sm">
                            <x-ui.avatar :name="$guru->nama" size="sm" />
                            <div class="leading-tight">
                                <p class="font-label-lg text-label-lg">{{ $guru->nama }}</p>
                                <p class="text-body-sm text-on-surface-variant">{{ $guru->user?->email }}</p>
                            </div>
                        </div>
                    </x-ui.td>
                    <x-ui.td class="tabular">{{ $guru->nip }}</x-ui.td>
                    <x-ui.td>{{ $guru->mapelUtama?->nama_mapel ?? '—' }}</x-ui.td>
                    <x-ui.td class="tabular">{{ $guru->telepon ?? '—' }}</x-ui.td>
                    <x-ui.td>
                        @if ($guru->user?->is_active)
                            <x-ui.badge tone="success">Aktif</x-ui.badge>
                        @else
                            <x-ui.badge tone="danger">Nonaktif</x-ui.badge>
                        @endif
                    </x-ui.td>
                    <x-ui.td>
                        <div class="flex items-center justify-end gap-space-xs">
                            <x-ui.btn variant="ghost" size="sm" icon="edit"
                                :href="route('master.guru.edit', $guru)">Ubah</x-ui.btn>
                            <form method="POST" action="{{ route('master.guru.destroy', $guru) }}"
                                data-confirm="Hapus guru {{ $guru->nama }} beserta akun loginnya?">
                                @csrf
                                @method('DELETE')
                                <x-ui.btn variant="danger" size="sm" icon="delete">Hapus</x-ui.btn>
                            </form>
                        </div>
                    </x-ui.td>
                </tr>
            @empty
                <tr>
                    <x-ui.td colspan="6">
                        <x-ui.empty-state icon="badge" title="Belum ada guru"
                            description="Tambahkan guru — sistem otomatis membuatkan akun login." />
                    </x-ui.td>
                </tr>
            @endforelse
        </x-ui.data-table>

        <x-ui.pagination :paginator="$daftar" />
    </x-ui.panel>
</x-layouts.app>
