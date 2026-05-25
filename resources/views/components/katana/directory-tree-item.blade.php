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

    // Image extensions get a distinct picture icon AND a lightbox preview
    // path on click (vs Monaco). The check has to be cheap — runs once per
    // row on render — so it's a plain regex against $name. Kept in sync
    // with the JS regex in the click handler below.
    $isImage = (bool) preg_match('/\.(png|jpe?g|gif|webp|avif|svg|bmp|ico)$/i', (string) $name);
@endphp

<div wire:key="dt-{{ $item['type'] === 'directory' ? 'd' : 'f' }}-{{ $item['path'] }}" class="@if ($level !== 0) ml-2 @endif relative font-light transition-all duration-200" :class="{ 'opacity-20 scale-[0.98] pointer-events-none': deletingPath === '{{ $escapedPath }}' }" @if ($item['type'] === 'directory') data-dir-path="{{ $item['path'] }}"
        @if ($isLazy) data-lazy="true" @endif @else data-file-path="{{ $item['path'] }}" @endif>
    @if ($item['type'] === 'directory')
        <div x-on:click="selectDirectory('{{ $escapedPath }}'); toggle('{{ $escapedPath }}', {{ $isLazy ? 'true' : 'false' }}, {{ $isSymlink ? 'true' : 'false' }}, {{ $level + 1 }}, $el.parentElement.querySelector('[data-children-for=&quot;{{ $escapedPath }}&quot;]'))" @if (!$readonly) draggable="true" @dragstart.stop="onFolderDragStart($event, '{{ $escapedPath }}')" @dragend="onFolderDragEnd()" @contextmenu.prevent.stop="openContextMenu($event, '{{ $escapedPath }}', 'directory', '{{ e($name) }}')" @endif class="flex cursor-pointer items-center truncate px-2 py-1 rounded-lg text-zinc-700 transition-[opacity,background-color,color] duration-150 hover:bg-black/[0.08] hover:text-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800 dark:hover:text-zinc-100 clean:rounded-md clean:hover:bg-zinc-100/70" :class="(selectedDirectory === '{{ $escapedPath }}' ? '!bg-black/[0.08] !text-zinc-900 dark:!bg-zinc-700 dark:!text-zinc-100 clean:!bg-zinc-100' : '') + (dragOverTarget === '{{ $escapedPath }}' && dragSource ? ' ring-2 ring-blue-500/50 ring-inset !bg-blue-500/[0.08]' : '') + (dragSource === '{{ $escapedPath }}' ? ' opacity-40' : '')">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 shrink-0 scale-110 stroke-current transition-all duration-150 ease-out" :class="{ 'rotate-90': expanded['{{ $escapedPath }}'] }" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m9 18 6-6-6-6" />
            </svg>
            {{-- Single SVG with reactive `:d` (matches the chevron above).
                 The previous two-SVG x-show pattern was fragile through
                 Livewire morphs: when refreshTree() re-rendered a row whose
                 children had changed (e.g. dragging a child folder out of
                 its parent), the morph could leave both SVGs visible at
                 once because the morph reconciles inline-style + x-cloak
                 state in a way that x-show can't always recover from.
                 Swapping a single path's `d` attribute sidesteps the entire
                 class of "both visible" bugs — there is only one element. --}}
            <span class="ml-0.5 mr-1.5">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 stroke-current" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path :d="expanded['{{ $escapedPath }}']
                        ? 'm6 14 1.5-2.9A2 2 0 0 1 9.24 10H20a2 2 0 0 1 1.94 2.5l-1.54 6a2 2 0 0 1-1.95 1.5H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h3.9a2 2 0 0 1 1.69.9l.81 1.2a2 2 0 0 0 1.67.9H18a2 2 0 0 1 2 2v2'
                        : 'M20 20a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.69-.9L9.6 3.9A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2Z'" />
                </svg>
            </span>

            <span>{{ $name }}</span>
            @if ($isSymlink)
                <span class="ml-1 text-xs text-zinc-400">(symlink)</span>
            @endif
        </div>
        {{-- Vertical Left Line in Tree --}}
        <span x-show="expanded['{{ $escapedPath }}']" x-cloak class="absolute top-0 ml-3 h-full w-px translate-x-0.5 overflow-hidden pt-7">
            <span class="relative block h-full w-px bg-zinc-200 dark:bg-zinc-800"></span>
        </span>
        {{-- x-show/x-collapse on outer wrapper. The children-container div
             is a sibling of the inline creation template so that empty
             fetchChildren responses (innerHTML = '') can't wipe the template
             — same pattern the root uses. --}}
        <div x-show="expanded['{{ $escapedPath }}']" x-cloak class="ml-4" @if ($animateCollapse) x-collapse @endif>
            <div data-children-for="{{ $escapedPath }}" @if ($hasChildren || !empty($item['loaded'])) data-loaded="true" @endif>
                @if ($hasChildren)
                    @foreach ($item['children'] as $childName => $child)
                        <x-katana.directory-tree-item
                            :name="$childName" :item="$child" :level="$level + 1" :readonly="$readonly" :animateCollapse="$animateCollapse" />
                    @endforeach
                @else
                    @if (!$isSymlink)
                        <div x-show="!prefetchCache?.['{{ $escapedPath }}']?.loaded && !prefetchCache?.['{{ $escapedPath }}']?.preloaded" class="ml-2 flex items-center px-2 py-1 text-zinc-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="mr-2 h-3 w-3 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span class="text-xs">Loading...</span>
                        </div>
                    @endif
                @endif
            </div>

            @if (!$readonly)
                {{-- Inline creation input for this directory (sibling, NOT
                     inside data-children-for). Sibling placement lets it
                     survive injectChildren's innerHTML reset. --}}
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
                            x-model="creatingName" class="flex-1 rounded-md border border-zinc-300 bg-white px-2 py-0.5 text-sm text-zinc-900 outline-none focus:border-zinc-500 focus:ring-2 focus:ring-zinc-900/5 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-zinc-400 dark:focus:ring-white/5" type="text" data-creation-input="{{ $escapedPath }}" @keydown.enter.prevent="confirmCreation()" @keydown.escape.prevent="cancelCreation()" @blur="creatingName.trim() ? confirmCreation() : cancelCreation()" placeholder="Enter name..." />
                    </div>
                </template>
            @endif
        </div>
    @else
        <div class="flex cursor-pointer rounded-lg items-center truncate px-2 py-1 text-zinc-700 transition-[opacity,background-color,color] duration-150 dark:text-zinc-300" :class="(selectedFile === '{{ $escapedPath }}' ? 'bg-[#171717] !text-white hover:bg-zinc-800 dark:bg-white dark:!text-[#171717] dark:hover:bg-zinc-100 clean:rounded-md clean:!bg-zinc-100 clean:!text-zinc-900' : 'hover:bg-black/[0.08] hover:text-zinc-900 dark:hover:bg-zinc-800 dark:hover:text-zinc-100 clean:rounded-md clean:hover:bg-zinc-100/70') + (dragSource === '{{ $escapedPath }}' ? ' opacity-40' : '')" @if (!$readonly) draggable="true" @dragstart="onFileDragStart($event, '{{ $escapedPath }}')" @dragend="onFileDragEnd()" @contextmenu.prevent.stop="openContextMenu($event, '{{ $escapedPath }}', 'file', '{{ e($name) }}')" @endif @mouseover="
                const fullPath = '{{ $escapedPath }}';
                // Image files never get prefetched — /katana/file-content
                // returns {binary: true} for them anyway, so the round-trip
                // is wasted bandwidth and the cache marker doesn't help us.
                if (!/\.(png|jpe?g|gif|webp|avif|svg|bmp|ico)$/i.test(fullPath)) {
                    fetchFileContent(fullPath);
                }
            " @click="
                const fullPath = '{{ $escapedPath }}';
                selectFile(fullPath);
                // Image files open in a lightbox modal on the editor side
                // (not Monaco). Binary contents can't be JSON-encoded back
                // through Livewire's update endpoint — sending image bytes
                // as `content` would 500 the whole page with 'Malformed
                // UTF-8'. The lightbox loads the image directly from the
                // {slug}.div.so preview host, bypassing Livewire entirely.
                if (/\.(png|jpe?g|gif|webp|avif|svg|bmp|ico)$/i.test(fullPath)) {
                    $dispatch('image-preview', { path: fullPath });
                    return;
                }
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
                @if ($isImage)
                    {{-- Picture icon — a small "frame with mountain + sun" silhouette
                         so images are recognizable at a glance in the tree. --}}
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 stroke-current text-violet-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                        <circle cx="9" cy="9" r="2"/>
                        <path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/>
                    </svg>
                @elseif ($fileType && isset($fileType['icon']) && isset($fileType['color']))
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
            {{-- Filename — swapped for an inline input when the user
                 picks Rename from the context menu. Using x-show (not
                 x-if + template) so both elements stay in the DOM and
                 we just toggle visibility; nested <template> tags in
                 the optimistic-create templates at the tree root made
                 the x-if version inconsistent across statically-rendered
                 rows and cloned-from-template rows, which surfaced as
                 missing filename text across the whole tree. The
                 @click.stop on the input keeps clicks inside the input
                 from re-firing the row's @click (which would fire
                 file-selected and close the rename). --}}
            <span x-show="renamingPath !== '{{ $escapedPath }}'">{{ $name }}</span>
            <input
                x-show="renamingPath === '{{ $escapedPath }}'"
                x-cloak
                type="text"
                data-katana-rename-input="{{ $escapedPath }}"
                x-model="renamingName"
                @click.stop
                @keydown.enter.prevent.stop="confirmRename()"
                @keydown.escape.prevent.stop="cancelRename()"
                @blur="renamingName.trim() && renamingName.trim() !== renamingOriginal ? confirmRename() : cancelRename()"
                class="flex-1 min-w-0 rounded-md border border-blue-500/60 bg-white px-1.5 py-0 text-[13px] leading-snug text-zinc-900 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-blue-400/50 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-blue-400 dark:focus:ring-blue-400/20"
            />
        </div>
    @endif
</div>
