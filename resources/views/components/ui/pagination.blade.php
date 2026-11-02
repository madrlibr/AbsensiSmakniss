@props(['paginator'])

@if ($paginator->hasPages())
    <nav class="flex flex-wrap items-center justify-between gap-space-sm border-t border-outline-variant px-space-lg py-space-sm"
        aria-label="Navigasi halaman">
        <p class="text-body-sm text-on-surface-variant">
            Menampilkan {{ number_format($paginator->firstItem() ?? 0) }}–{{ number_format($paginator->lastItem() ?? 0) }}
            dari {{ number_format($paginator->total()) }}
        </p>

        <div class="flex items-center gap-space-xs">
            @if ($paginator->onFirstPage())
                <span
                    class="inline-flex cursor-not-allowed items-center gap-space-xs rounded-lg px-space-md py-space-xs font-label-md text-on-surface-variant opacity-50">
                    <span class="material-symbols-outlined text-[16px]">chevron_left</span> Sebelumnya
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}"
                    class="inline-flex items-center gap-space-xs rounded-lg bg-surface-container px-space-md py-space-xs font-label-md text-on-surface hover:bg-surface-container-high">
                    <span class="material-symbols-outlined text-[16px]">chevron_left</span> Sebelumnya
                </a>
            @endif

            <span class="tabular px-space-sm text-body-sm text-on-surface-variant">
                Hal {{ number_format($paginator->currentPage()) }} / {{ number_format($paginator->lastPage()) }}
            </span>

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}"
                    class="inline-flex items-center gap-space-xs rounded-lg bg-surface-container px-space-md py-space-xs font-label-md text-on-surface hover:bg-surface-container-high">
                    Berikutnya <span class="material-symbols-outlined text-[16px]">chevron_right</span>
                </a>
            @else
                <span
                    class="inline-flex cursor-not-allowed items-center gap-space-xs rounded-lg px-space-md py-space-xs font-label-md text-on-surface-variant opacity-50">
                    Berikutnya <span class="material-symbols-outlined text-[16px]">chevron_right</span>
                </span>
            @endif
        </div>
    </nav>
@endif
