@props([
    'disk' => 'local',
    'baseDir' => '',
    'exclude' => [],
    'lazyDirs' => ['node_modules', 'vendor'],
    'showToolbar' => true,
    'readonly' => false,
    'title' => null,
    'animateCollapse' => false,
])

@php
    $computedTitle = $title ?? ($baseDir ? basename(rtrim($baseDir, '/')) : '');
@endphp

<section
    x-data="{ dtCreating: false, dtHasSelection: false, dtIsDeleting: false }" class="flex h-full w-full select-none flex-col text-sm" @dt-creating-state.window="dtCreating = $event.detail.creating" @dt-deleting-state.window="dtIsDeleting = $event.detail.deleting" @directory-tree-selection-changed.window="dtHasSelection = $event.detail.file !== null || ($event.detail.directory !== null && $event.detail.directory !== '')">
    @if ($showToolbar && !$readonly)
        <div class="flex shrink-0 items-center px-5 pt-3">
            {{-- Left: header slot or title --}}
            <div class="min-w-0 flex-1">
                @if (isset($header) && !$header->isEmpty())
                    {{ $header }}
                @elseif($computedTitle)
                    <h3 class="truncate pl-0.5 text-[11px] font-semibold uppercase tracking-widest text-zinc-500/80">{{ $computedTitle }}</h3>
                @endif
            </div>
            {{-- Right: toolbar actions --}}
            <div class="flex shrink-0 items-center gap-0.5">
                <button
                    class="rounded-md p-1.5 transition-all duration-200" type="button" title="Delete" :disabled="!dtHasSelection || dtCreating || dtIsDeleting" :class="!dtHasSelection || dtCreating || dtIsDeleting ?
                        'text-zinc-300 dark:text-zinc-700 pointer-events-none' :
                        'text-zinc-500 hover:bg-red-500/10 hover:text-red-600 dark:hover:text-red-400'" @click="$dispatch('dt-delete-selected')">
                    <svg x-show="!dtIsDeleting" xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 6h18" />
                        <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6" />
                        <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2" />
                    </svg>
                    <svg x-show="dtIsDeleting" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 animate-spin text-zinc-400" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </button>
                <button
                    class="text-zinc-500 rounded-md p-1.5 transition-colors" type="button" title="New File" :disabled="dtCreating" :class="dtCreating ? 'opacity-30 cursor-not-allowed' : 'hover:bg-zinc-100 hover:text-zinc-900 dark:hover:bg-zinc-800 dark:hover:text-zinc-100'" @click="$dispatch('dt-start-creating', { type: 'file' })">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z" />
                        <path d="M14 2v4a2 2 0 0 0 2 2h4" />
                        <path d="M12 18v-6" />
                        <path d="M9 15h6" />
                    </svg>
                </button>
                <button
                    class="text-zinc-500 rounded-md p-1.5 transition-colors" type="button" title="New Folder" :disabled="dtCreating" :class="dtCreating ? 'opacity-30 cursor-not-allowed' : 'hover:bg-zinc-100 hover:text-zinc-900 dark:hover:bg-zinc-800 dark:hover:text-zinc-100'" @click="$dispatch('dt-start-creating', { type: 'folder' })">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 10v6" />
                        <path d="M9 13h6" />
                        <path d="M20 20a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.69-.9L9.6 3.9A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2Z" />
                    </svg>
                </button>
            </div>
        </div>
    @endif
    <div class="min-h-0 flex-1">
        <livewire:directory-tree :disk="$disk" :base-dir="$baseDir" :exclude="$exclude" :lazy-dirs="$lazyDirs" :show-toolbar="false" :readonly="$readonly" :animate-collapse="$animateCollapse" />
    </div>
</section>
