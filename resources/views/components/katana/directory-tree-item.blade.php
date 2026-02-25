@props(['name', 'item', 'level'])

@php
    $fileTypes = config('katana.directorytree.file_types', []);
    $fileType = null;

    // Loop through file types and check if the file matches any of their extensions
    foreach ($fileTypes as $type => $config) {
        if (!isset($config['extensions']) || !is_array($config['extensions'])) {
            continue;
        }

        foreach ($config['extensions'] as $extension) {
            if (str_ends_with($name, $extension)) {
                $fileType = $config;
                break 2;
            }
        }
    }

    $isLazy = !empty($item['lazy']);
    $isSymlink = !empty($item['symlink']);
    $hasChildren = !empty($item['children']);
    $escapedPath = e($item['path']);
@endphp

<div class="@if($level !== 0) ml-2 @endif relative font-light"
    @if($item['type'] === 'directory')
        data-dir-path="{{ $item['path'] }}"
        @if($isLazy) data-lazy="true" @endif
    @endif
>
    @if($item['type'] === 'directory')
        <div class="flex items-center px-2 py-1 truncate rounded cursor-pointer text-white/60 dark:text-white/60 hover:dark:text-white/80 hover:text-white/80 hover:bg-stone-800 dark:hover:bg-neutral-800"
            x-on:click="toggle('{{ $escapedPath }}', {{ $isLazy ? 'true' : 'false' }}, {{ $isSymlink ? 'true' : 'false' }}, {{ $level + 1 }}, $el.parentElement.querySelector('[data-children-for=&quot;{{ $escapedPath }}&quot;]'))"
        >
            <svg :class="{ 'rotate-90' : expanded['{{ $escapedPath }}'] }" xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 transition-all duration-100 ease-out scale-110 stroke-current" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
            <span class="mr-1.5 ml-0.5">
                <svg x-show="!expanded['{{ $escapedPath }}']" xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 text-white stroke-current" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 20a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.69-.9L9.6 3.9A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2Z"/></svg>
                <svg x-show="expanded['{{ $escapedPath }}']" xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 text-white stroke-current" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" x-cloak><path d="m6 14 1.5-2.9A2 2 0 0 1 9.24 10H20a2 2 0 0 1 1.94 2.5l-1.54 6a2 2 0 0 1-1.95 1.5H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h3.9a2 2 0 0 1 1.69.9l.81 1.2a2 2 0 0 0 1.67.9H18a2 2 0 0 1 2 2v2"/></svg>
            </span>

            <span>{{ $name }}</span>
            @if($isSymlink)
                <span class="ml-1 text-xs text-white/30">(symlink)</span>
            @endif
        </div>
        {{-- Vertical Left Line in Tree --}}
        <span x-show="expanded['{{ $escapedPath }}']" class="overflow-hidden absolute top-0 pt-7 ml-3 w-px h-full translate-x-0.5" x-cloak>
            <span class="block relative w-px h-full bg-stone-700 dark:bg-stone-800"></span>
        </span>
        <div class="ml-4"
            data-children-for="{{ $escapedPath }}"
            @if($hasChildren) data-loaded="true" @endif
            x-show="expanded['{{ $escapedPath }}']"
            x-cloak
        >
            @if($hasChildren)
                @foreach($item['children'] as $childName => $child)
                    <x-katana.directory-tree-item
                        :name="$childName"
                        :item="$child"
                        :level="$level + 1"
                    />
                @endforeach
            @else
                @if(!$isSymlink)
                    <div x-show="!prefetchCache?.['{{ $escapedPath }}']?.html && !prefetchCache?.['{{ $escapedPath }}']?.preloaded" class="flex items-center px-2 py-1 ml-2 text-white/30">
                        <svg class="w-3 h-3 mr-2 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        <span class="text-xs">Loading...</span>
                    </div>
                @endif
            @endif
        </div>
    @else
        <div class="flex items-center px-2 py-1 truncate rounded cursor-pointer hover:bg-stone-800 dark:hover:bg-neutral-800 text-white/60 dark:text-white/60 hover:dark:text-white/80 hover:text-white/80"
            @mouseover="
                const fullPath = '{{ $escapedPath }}';
                fetchFileContent(fullPath);
            "
            @click="
                const fullPath = '{{ $escapedPath }}';
                fetchFileContent(fullPath).then(content => {
                    $dispatch('file-selected', [{ file: fullPath, content }]);
                });
            "
        >
            <span class="@if($level === 0) ml-3.5 @endif mr-1.5">
                @if($fileType && isset($fileType['icon']) && isset($fileType['color']))
                    <span class="{{ $fileType['color'] }}">{!! $fileType['icon'] !!}</span>
                @else
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 stroke-current" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/></svg>
                @endif
            </span>
            <span>{{ $name }}</span>
        </div>
    @endif
</div>
