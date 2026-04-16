@props(['name', 'item', 'level', 'readonly' => false, 'animateCollapse' => false])

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

<div class="@if ($level !== 0) ml-2 @endif relative font-light transition-all duration-200" :class="{ 'opacity-20 scale-[0.98] pointer-events-none': deletingPath === '{{ $escapedPath }}' }" @if ($item['type'] === 'directory') data-dir-path="{{ $item['path'] }}"
        @if ($isLazy) data-lazy="true" @endif @endif>
    @if ($item['type'] === 'directory')
        <div x-on:click="selectDirectory('{{ $escapedPath }}'); toggle('{{ $escapedPath }}', {{ $isLazy ? 'true' : 'false' }}, {{ $isSymlink ? 'true' : 'false' }}, {{ $level + 1 }}, $el.parentElement.querySelector('[data-children-for=&quot;{{ $escapedPath }}&quot;]'))" class="text-foreground/80 hover:text-accent-foreground hover:bg-accent/50 flex cursor-pointer items-center truncate rounded px-2 py-1" :class="selectedDirectory === '{{ $escapedPath }}' ? 'bg-accent/50 !text-accent-foreground' : ''">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 shrink-0 scale-110 stroke-current transition-all duration-150 ease-out" :class="{ 'rotate-90': expanded['{{ $escapedPath }}'] }" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m9 18 6-6-6-6" />
            </svg>
            <span class="ml-0.5 mr-1.5">
                <svg x-show="!expanded['{{ $escapedPath }}']" xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 stroke-current" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 20a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.69-.9L9.6 3.9A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2Z" />
                </svg>
                <svg x-show="expanded['{{ $escapedPath }}']" xmlns="http://www.w3.org/2000/svg" x-cloak class="h-3 w-3 stroke-current" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m6 14 1.5-2.9A2 2 0 0 1 9.24 10H20a2 2 0 0 1 1.94 2.5l-1.54 6a2 2 0 0 1-1.95 1.5H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h3.9a2 2 0 0 1 1.69.9l.81 1.2a2 2 0 0 0 1.67.9H18a2 2 0 0 1 2 2v2" />
                </svg>
            </span>

            <span>{{ $name }}</span>
            @if ($isSymlink)
                <span class="text-muted-foreground/50 ml-1 text-xs">(symlink)</span>
            @endif
        </div>
        {{-- Vertical Left Line in Tree --}}
        <span x-show="expanded['{{ $escapedPath }}']" x-cloak class="absolute top-0 ml-3 h-full w-px translate-x-0.5 overflow-hidden pt-7">
            <span class="bg-border relative block h-full w-px"></span>
        </span>
        <div x-show="expanded['{{ $escapedPath }}']" x-cloak class="ml-4" data-children-for="{{ $escapedPath }}" @if ($hasChildren) data-loaded="true" @endif @if ($animateCollapse) x-collapse @endif>
            @if ($hasChildren)
                @foreach ($item['children'] as $childName => $child)
                    <x-katana.directory-tree-item
                        :name="$childName" :item="$child" :level="$level + 1" :readonly="$readonly" :animateCollapse="$animateCollapse" />
                @endforeach
            @else
                @if (!$isSymlink)
                    <div x-show="!prefetchCache?.['{{ $escapedPath }}']?.loaded && !prefetchCache?.['{{ $escapedPath }}']?.preloaded" class="text-muted-foreground/50 ml-2 flex items-center px-2 py-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="mr-2 h-3 w-3 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="text-xs">Loading...</span>
                    </div>
                @endif
            @endif

            @if (!$readonly)
                {{-- Inline creation input for this directory --}}
                <template x-if="creatingType && creatingInPath === '{{ $escapedPath }}'">
                    <div class="ml-2 flex items-center px-2 py-1">
                        <span class="w-3 shrink-0"></span>
                        <span class="ml-0.5 mr-1.5">
                            <template x-if="creatingType === 'folder'">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 stroke-current" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M20 20a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.69-.9L9.6 3.9A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2Z" />
                                </svg>
                            </template>
                            <template x-if="creatingType === 'file'">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 stroke-current" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z" />
                                    <path d="M14 2v4a2 2 0 0 0 2 2h4" />
                                </svg>
                            </template>
                        </span>
                        <input
                            x-model="creatingName" class="bg-muted border-border text-foreground focus:border-ring flex-1 rounded-md border px-1 py-0 text-sm outline-none" type="text" data-creation-input="{{ $escapedPath }}" @keydown.enter.prevent="confirmCreation()" @keydown.escape.prevent="cancelCreation()" @blur="creatingName.trim() ? confirmCreation() : cancelCreation()" placeholder="Enter name..." />
                    </div>
                </template>
            @endif
        </div>
    @else
        <div data-file-path="{{ $escapedPath }}" class="text-foreground/80 hover:text-foreground flex cursor-pointer items-center truncate rounded px-2 py-1" :class="selectedFile === '{{ $escapedPath }}' ? 'bg-blue-50 dark:bg-blue-500/15 hover:bg-blue-100 dark:hover:bg-blue-500/25 !text-blue-600 dark:!text-blue-400' : 'hover:bg-accent/50 hover:text-accent-foreground'" @mouseover="
                const fullPath = '{{ $escapedPath }}';
                fetchFileContent(fullPath);
            " @click="
                const fullPath = '{{ $escapedPath }}';
                selectFile(fullPath);
                const cached = files[fullPath];
                if (cached !== undefined) {
                    $dispatch('file-selected', [{ file: fullPath, content: cached }]);
                } else {
                    fetchFileContent(fullPath).then(content => {
                        $dispatch('file-selected', [{ file: fullPath, content }]);
                    });
                }
            ">
            <span class="w-3 shrink-0"></span>
            <span class="ml-0.5 mr-1.5">
                @if ($fileType && isset($fileType['icon']) && isset($fileType['color']))
                    <span class="{{ $fileType['color'] }}">{!! $fileType['icon'] !!}</span>
                @else
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 stroke-current" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z" />
                        <path d="M14 2v4a2 2 0 0 0 2 2h4" />
                        <path d="M10 9H8" />
                        <path d="M16 13H8" />
                        <path d="M16 17H8" />
                    </svg>
                @endif
            </span>
            <span>{{ $name }}</span>
        </div>
    @endif
</div>
