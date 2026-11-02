<x-layouts.app title="Tahun Ajaran">
    <x-ui.page-header title="Tahun Ajaran" :breadcrumb="['Master Data', 'Tahun Ajaran']"
        description="Kelola tahun ajaran sekolah. Tandai satu tahun ajaran sebagai aktif — seluruh absensi & jadwal mengikutinya.">
        <x-slot:actions>
            <x-ui.btn :href="route('master.tahun-ajaran.create')" icon="add">Tambah Tahun Ajaran</x-ui.btn>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.panel :flush="true">
        <x-ui.data-table>
            <x-slot:head>
                <x-ui.th>Nama</x-ui.th>
                <x-ui.th>Periode</x-ui.th>
                <x-ui.th>Status</x-ui.th>
                <x-ui.th class="text-right">Aksi</x-ui.th>
            </x-slot:head>

            @forelse ($daftar as $ta)
                <tr class="hover:bg-surface-container-low">
                    <x-ui.td>
                        <span class="font-label-lg text-label-lg">{{ $ta->nama }}</span>
                    </x-ui.td>
                    <x-ui.td>
                        <span class="tabular">{{ $ta->tanggal_mulai->translatedFormat('d M Y') }}</span> –
                        <span class="tabular">{{ $ta->tanggal_selesai->translatedFormat('d M Y') }}</span>
                    </x-ui.td>
                    <x-ui.td>
                        @if ($ta->is_active)
                            <x-ui.badge tone="success">Aktif</x-ui.badge>
                        @else
                            <x-ui.badge tone="neutral">Nonaktif</x-ui.badge>
                        @endif
                    </x-ui.td>
                    <x-ui.td>
                        <div class="flex items-center justify-end gap-space-xs">
                            @unless ($ta->is_active)
                                <form method="POST" action="{{ route('master.tahun-ajaran.aktifkan', $ta) }}"
                                    data-confirm="Jadikan {{ $ta->nama }} sebagai tahun ajaran aktif?">
                                    @csrf
                                    <x-ui.btn variant="secondary" size="sm" icon="check_circle">Jadikan
                                        Aktif</x-ui.btn>
                                </form>
                            @endunless

                            <x-ui.btn variant="ghost" size="sm" icon="edit"
                                :href="route('master.tahun-ajaran.edit', $ta)">Ubah</x-ui.btn>

                            @unless ($ta->is_active)
                                <form method="POST"
                                    action="{{ route('master.tahun-ajaran.destroy', $ta) }}"
                                    data-confirm="Hapus tahun ajaran {{ $ta->nama }}?">
                                    @csrf
                                    @method('DELETE')
                                    <x-ui.btn variant="danger" size="sm" icon="delete">Hapus</x-ui.btn>
                                </form>
                            @endunless
                        </div>
                    </x-ui.td>
                </tr>
            @empty
                <tr>
                    <x-ui.td colspan="4">
                        <x-ui.empty-state icon="event_busy" title="Belum ada tahun ajaran"
                            description="Tambahkan tahun ajaran pertama untuk mulai mengelola data sekolah." />
                    </x-ui.td>
                </tr>
            @endforelse
        </x-ui.data-table>

        <x-ui.pagination :paginator="$daftar" />
    </x-ui.panel>
</x-layouts.app>
