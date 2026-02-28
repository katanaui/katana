@props([
    'disk' => 'local',
    'baseDir' => '',
    'exclude' => [],
    'lazyDirs' => ['node_modules', 'vendor'],
    'showToolbar' => true,
    'readonly' => false,
    'title' => null,
])

@php
    $computedTitle = $title ?? ($baseDir ? basename(rtrim($baseDir, '/')) : '');
@endphp

<section
    class="w-full h-full bg-stone-950 flex flex-col text-sm select-none"
    x-data="{ dtCreating: false }"
    @dt-creating-state.window="dtCreating = $event.detail.creating"
>
    @if($showToolbar && !$readonly)
    <div class="flex items-center px-5 pt-3 shrink-0">
        {{-- Left: header slot or title --}}
        <div class="flex-1 min-w-0">
            @if(isset($header) && !$header->isEmpty())
                {{ $header }}
            @elseif($computedTitle)
                <h3 class="text-[11px] font-semibold uppercase tracking-widest text-white/30 truncate pl-0.5">{{ $computedTitle }}</h3>
            @endif
        </div>
        {{-- Right: toolbar actions --}}
        <div class="flex items-center gap-0.5 shrink-0">
            <button
                type="button"
                title="New File"
                :disabled="dtCreating"
                :class="dtCreating ? 'opacity-30 cursor-not-allowed' : 'hover:bg-white/[0.06] hover:text-white/80'"
                class="p-1.5 rounded-md text-white/40 transition-colors"
                @click="$dispatch('dt-start-creating', { type: 'file' })"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M12 18v-6"/><path d="M9 15h6"/></svg>
            </button>
            <button
                type="button"
                title="New Folder"
                :disabled="dtCreating"
                :class="dtCreating ? 'opacity-30 cursor-not-allowed' : 'hover:bg-white/[0.06] hover:text-white/80'"
                class="p-1.5 rounded-md text-white/40 transition-colors"
                @click="$dispatch('dt-start-creating', { type: 'folder' })"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 10v6"/><path d="M9 13h6"/><path d="M20 20a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.69-.9L9.6 3.9A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2Z"/></svg>
            </button>
        </div>
    </div>
    @endif
    <div class="flex-1 min-h-0">
        <livewire:directory-tree :disk="$disk" :base-dir="$baseDir" :exclude="$exclude" :lazy-dirs="$lazyDirs" :show-toolbar="false" :readonly="$readonly" />
    </div>
</section>
