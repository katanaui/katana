<?php

use Livewire\Volt\Component;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

new class extends Component {

    public string $disk = 'local';
    public string $baseDir = '';
    public array $exclude = ['node_modules', 'vendor', '.git', '.github', 'storage', '.claude'];
    public array $lazyDirs = ['node_modules', 'vendor'];
    public $structure = [];
    public $currentPath = '';
    public $files = [];
    public bool $showToolbar = true;
    public bool $isReadonly = false;
    public bool $animateCollapse = false;
    public ?string $writeToken = null;

    public function mount($disk = 'local', $baseDir = '', $exclude = null, $lazyDirs = null, $showToolbar = true, $isReadonly = false, $animateCollapse = false)
    {
        $this->disk = $disk;
        $this->baseDir = $baseDir;
        $this->showToolbar = $showToolbar;
        $this->isReadonly = $isReadonly;
        $this->animateCollapse = $animateCollapse;

        if (!$this->isReadonly) {
            $this->writeToken = Crypt::encryptString(json_encode([
                'writable' => true,
                'disk' => $this->disk,
                'baseDir' => $this->baseDir,
            ]));
        }

        if ($exclude !== null) {
            $this->exclude = $exclude;
        }

        if ($lazyDirs !== null) {
            $this->lazyDirs = $lazyDirs;
        }

        $this->structure = $this->getDirectoryStructure($this->baseDir, 2);
    }

    public function refreshTree()
    {
        $this->structure = $this->getDirectoryStructure($this->baseDir, 2);
    }

    protected function getDiskRootPath()
    {
        $diskConfig = config("filesystems.disks.{$this->disk}");
        return rtrim($diskConfig['root'] ?? '', '/');
    }

    protected function isLocalDisk(): bool
    {
        $driver = config("filesystems.disks.{$this->disk}.driver");
        return $driver === 'local';
    }

    protected function getDirectoryStructure($baseDir, $depth = 1)
    {
        return $this->isLocalDisk()
            ? $this->getLocalDirectoryStructure($baseDir, $depth)
            : $this->getDiskDirectoryStructure($baseDir, $depth);
    }

    protected function getLocalDirectoryStructure($baseDir, $depth = 1)
    {
        $structure = [];
        $root = $this->getDiskRootPath();
        $fullDir = $root . ($baseDir ? '/' . ltrim($baseDir, '/') : '');

        if (!is_dir($fullDir)) {
            return $structure;
        }

        $entries = scandir($fullDir);
        $baseDirPrefix = rtrim($this->baseDir, '/');

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            // Skip hidden files/dirs
            if ($entry[0] === '.') {
                continue;
            }

            $fullPath = $fullDir . '/' . $entry;
            $diskPath = $baseDir ? rtrim($baseDir, '/') . '/' . $entry : $entry;
            $relativePath = $baseDirPrefix !== ''
                ? ltrim(substr($diskPath, strlen($baseDirPrefix)), '/')
                : ltrim($diskPath, '/');

            if (is_link($fullPath)) {
                $structure[$entry] = [
                    'type' => 'directory',
                    'path' => $relativePath,
                    'symlink' => true,
                    'lazy' => false,
                    'children' => [],
                ];
            } elseif (is_dir($fullPath)) {
                if (in_array($entry, $this->exclude)) {
                    continue;
                }

                $isLazy = in_array($entry, $this->lazyDirs);
                $scanned = $depth > 1 && !$isLazy;
                $children = [];

                if ($scanned) {
                    $children = $this->getDirectoryStructure($diskPath, $depth - 1);
                }

                $structure[$entry] = [
                    'type' => 'directory',
                    'path' => $relativePath,
                    'lazy' => $isLazy,
                    'loaded' => $scanned,
                    'children' => $children,
                ];
            } else {
                $structure[$entry] = [
                    'type' => 'file',
                    'path' => $relativePath,
                ];
            }
        }

        return $this->sortStructure($structure);
    }

    /**
     * Disk-abstracted directory walk. Works for any filesystem driver
     * (s3, ftp, sftp, etc.) via Laravel's Storage facade.
     */
    protected function getDiskDirectoryStructure($baseDir, $depth = 1)
    {
        $disk = Storage::disk($this->disk);
        $dirKey = ltrim(rtrim((string) $baseDir, '/'), '/');
        $baseDirPrefix = rtrim($this->baseDir, '/');
        $structure = [];

        foreach ($disk->directories($dirKey) as $dirKeyChild) {
            $entry = basename($dirKeyChild);

            if ($entry === '' || $entry[0] === '.' || in_array($entry, $this->exclude)) {
                continue;
            }

            $relativePath = $baseDirPrefix !== ''
                ? ltrim(substr($dirKeyChild, strlen($baseDirPrefix)), '/')
                : ltrim($dirKeyChild, '/');

            $isLazy = in_array($entry, $this->lazyDirs);
            $scanned = $depth > 1 && !$isLazy;
            $children = [];

            if ($scanned) {
                $children = $this->getDirectoryStructure($dirKeyChild, $depth - 1);
            }

            $structure[$entry] = [
                'type' => 'directory',
                'path' => $relativePath,
                'lazy' => $isLazy,
                'loaded' => $scanned,
                'children' => $children,
            ];
        }

        foreach ($disk->files($dirKey) as $fileKey) {
            $entry = basename($fileKey);

            // Skip hidden files (including the .gitkeep markers used to
            // persist empty directories on blob storage).
            if ($entry === '' || $entry[0] === '.') {
                continue;
            }

            $relativePath = $baseDirPrefix !== ''
                ? ltrim(substr($fileKey, strlen($baseDirPrefix)), '/')
                : ltrim($fileKey, '/');

            $structure[$entry] = [
                'type' => 'file',
                'path' => $relativePath,
            ];
        }

        return $this->sortStructure($structure);
    }

    protected function sortStructure(array $structure): array
    {
        uksort($structure, function ($a, $b) use ($structure) {
            $aIsDir = $structure[$a]['type'] === 'directory';
            $bIsDir = $structure[$b]['type'] === 'directory';
            if ($aIsDir !== $bIsDir) {
                return $aIsDir ? -1 : 1;
            }
            return strcasecmp($a, $b);
        });
        return $structure;
    }

    public function navigateToPath($relativePath)
    {
        $this->currentPath = $relativePath;
        $disk = Storage::disk($this->disk);
        $fullPath = $this->baseDir . '/' . ltrim($relativePath, '/');

        if (!isset($this->files[$relativePath])) {
            try {
                $content = $disk->get($fullPath);
                $this->files[$relativePath] = $content;
            } catch (\Exception $e) {
                $this->files[$relativePath] = '';
            }
        }

        $this->dispatch('file-selected', [
            'file' => $relativePath,
            'content' => $this->files[$relativePath]
        ]);
    }

}; ?>

<div class="relative flex flex-col h-full text-sm select-none scrollbar-hide" x-data="directoryTree(@js($isReadonly), @js($writeToken))" x-init="init()" @refresh-directory-tree.window="$wire.refreshTree()" @katana-file-cache-set.window="if ($event.detail?.path) files[$event.detail.path] = $event.detail.content ?? null" @katana-file-cache-invalidate.window="if ($event.detail?.path) delete files[$event.detail.path]" @keydown.escape.window="closeContextMenu(); cancelRename()" @mousedown.window="if (contextMenu.open && !$event.target.closest('[data-katana-context-menu]')) closeContextMenu()" @if(!$isReadonly) @dt-start-creating.window="startCreating($event.detail.type)" @dt-delete-selected.window="deleteSelected()" @dt-insert-uploaded.window="
            // Cross-component optimistic insert for drag-and-drop uploads.
            // The dropzone (in the editor scope) POSTs to /katana/file-upload
            // first, then fires this event on success — so by the time we
            // render the row the file already exists server-side. We still
            // route through insertOptimisticItem() for the fade-in animation
            // and sorted-insertion logic, then drop the data-optimistic
            // marker immediately since the server has already confirmed.
            // Returns silently when the parent isn't expanded (no DOM
            // container to inject into — the tree will pick up the file the
            // next time that folder is opened or refreshed).
            (() => {
                const d = $event.detail || {};
                if (!d.path || !d.name) return;
                const parentPath = d.parentPath || '';
                const type = d.type === 'folder' ? 'folder' : 'file';
                const node = insertOptimisticItem(parentPath, d.name, d.path, type);
                if (node) node.removeAttribute('data-optimistic');
                const cache = prefetchCache[parentPath];
                if (cache) {
                    if (type === 'folder') {
                        cache.childDirs = [...(cache.childDirs || []), d.path];
                    } else {
                        cache.childFiles = [...(cache.childFiles || []), d.path];
                    }
                }
            })()
        " @endif>
    @if($showToolbar && !$isReadonly)
    <div class="flex items-center justify-end gap-1 px-3 pt-2 pb-1 shrink-0">
        <button
            type="button"
            title="Delete"
            :disabled="(selectedFile === null && (selectedDirectory === null || selectedDirectory === '')) || creatingType !== null || isDeleting"
            class="p-1 rounded transition-all duration-200"
            :class="(selectedFile === null && (selectedDirectory === null || selectedDirectory === '')) || creatingType !== null || isDeleting
                ? 'text-zinc-300 dark:text-zinc-700 pointer-events-none'
                : 'text-zinc-500 hover:bg-red-500/10 hover:text-red-600 dark:hover:text-red-400'"
            @click="deleteSelected()"
        >
            <svg x-show="!isDeleting" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
            <svg x-show="isDeleting" x-cloak xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 animate-spin text-zinc-400" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
        </button>
        <div class="mx-0.5 h-3 w-px bg-zinc-200 transition-opacity duration-200 dark:bg-zinc-800" :class="(selectedFile !== null || (selectedDirectory !== null && selectedDirectory !== '')) && creatingType === null ? 'opacity-100' : 'opacity-0'"></div>
        <button
            type="button"
            title="New File"
            :disabled="creatingType !== null"
            :class="creatingType !== null ? 'opacity-30 cursor-not-allowed' : 'hover:bg-zinc-100 hover:text-zinc-900 dark:hover:bg-zinc-800 dark:hover:text-zinc-100'"
            class="p-1 rounded text-zinc-500 transition-colors"
            @click="startCreating('file')"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M12 18v-6"/><path d="M9 15h6"/></svg>
        </button>
        <button
            type="button"
            title="New Folder"
            :disabled="creatingType !== null"
            :class="creatingType !== null ? 'opacity-30 cursor-not-allowed' : 'hover:bg-zinc-100 hover:text-zinc-900 dark:hover:bg-zinc-800 dark:hover:text-zinc-100'"
            class="p-1 rounded text-zinc-500 transition-colors"
            @click="startCreating('folder')"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 10v6"/><path d="M9 13h6"/><path d="M20 20a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.69-.9L9.6 3.9A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2Z"/></svg>
        </button>
    </div>
    @endif
    <div
        {{ $attributes->twMergeFor('tree-container', 'flex-1 p-1 overflow-y-auto scrollbar-hide') }}
        @if (!$isReadonly)
            @dragover="onTreeDragOver($event)"
            @dragleave="onTreeDragLeave($event)"
            @drop="onTreeDrop($event)"
            @contextmenu.prevent="openContextMenuAtRoot($event)"
            :class="dragSource && dragOverTarget === '' ? 'ring-2 ring-inset ring-blue-500/40 ring-offset-0 rounded-lg' : ''"
        @endif
    >
        <div data-children-for="" data-loaded="true">
            @foreach($structure as $name => $item)
                <x-katana.directory-tree-item
                    :name="$name"
                    :item="$item"
                    :level="0"
                    :readonly="$isReadonly"
                    :animateCollapse="$animateCollapse"
                />
            @endforeach
        </div>

        @if(!$isReadonly)
        {{-- Root-level inline creation input (outside container so it survives innerHTML refresh) --}}
        <template x-if="creatingType && creatingInPath === ''">
            <div class="flex items-center px-2 py-1 ml-0">
                <span class="w-3 shrink-0"></span>
                <span class="ml-0.5 mr-1.5">
                    <template x-if="creatingType === 'folder'">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 stroke-current" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 20a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.69-.9L9.6 3.9A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2Z"/></svg>
                    </template>
                    <template x-if="creatingType === 'file'">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 stroke-current" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/></svg>
                    </template>
                </span>
                <input
                    type="text"
                    x-model="creatingName"
                    x-ref="rootCreationInput"
                    @keydown.enter.prevent="confirmCreation()"
                    @keydown.escape.prevent="cancelCreation()"
                    @blur="creatingName.trim() ? confirmCreation() : cancelCreation()"
                    class="flex-1 rounded-md border border-zinc-300 bg-white px-2 py-0.5 text-sm text-zinc-900 outline-none focus:border-zinc-500 focus:ring-2 focus:ring-zinc-900/5 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-zinc-400 dark:focus:ring-white/5"
                    placeholder="Enter name..."
                />
            </div>
        </template>
        @endif
    </div>

    @if(!$isReadonly)
    {{-- Optimistic insert templates. Rendered server-side once with
         __KATANA_DT_PATH__ / __KATANA_DT_NAME__ placeholders, then cloned and
         token-replaced in JS so confirmCreation() can insert a new item into
         the DOM instantly — no server round-trip in the critical path. --}}
    @php
        $optimisticTemplates = [
            'folder-root' => ['type' => 'directory', 'level' => 0],
            'folder-nested' => ['type' => 'directory', 'level' => 1],
            'file-root' => ['type' => 'file', 'level' => 0],
            'file-nested' => ['type' => 'file', 'level' => 1],
        ];
    @endphp
    @foreach($optimisticTemplates as $templateKey => $cfg)
        @php
            $tplItem = $cfg['type'] === 'directory'
                ? ['type' => 'directory', 'path' => '__KATANA_DT_PATH__', 'lazy' => false, 'children' => []]
                : ['type' => 'file', 'path' => '__KATANA_DT_PATH__'];
        @endphp
        <template data-katana-dt-template="{{ $templateKey }}">{!! view('katana::katana.directory-tree-item', [
            'name' => '__KATANA_DT_NAME__',
            'item' => $tplItem,
            'level' => $cfg['level'],
            'readonly' => $isReadonly,
            'animateCollapse' => $animateCollapse,
        ])->render() !!}</template>
    @endforeach
    @endif

    {{-- ─────────────────── Context menu ───────────────────
         Dark dropdown surfaced on right-click of a tree row. Position is
         set inline from `contextMenu.x/y` (viewport pixels), recomputed
         after first paint to avoid clipping near the viewport edges.

         **Teleported to <body>** because the editor's sidebar wrapper
         applies `transform: translateX(...)` for the focus-mode slide
         animation, and any ancestor with a transform creates a new
         containing block for `position: fixed` descendants — without the
         teleport the menu's viewport-coord positioning gets interpreted
         relative to that wrapper, landing the menu well below the cursor.
         The teleport keeps the menu wired to the tree's Alpine scope
         (contextMenu state, startRename, closeContextMenu) but renders
         the DOM at <body> root where its `position: fixed` correctly
         resolves against the viewport.

         Z layer sits at 80 — above the upload notification (40) and
         dropzone overlay (60), but still below the image lightbox (70).
         A right-click while the lightbox is open would close the menu
         anyway via the outside-click handler on the tree's root. --}}
    @if (!$isReadonly)
    <template x-teleport="body">
    <div
        x-show="contextMenu.open"
        x-cloak
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        data-katana-context-menu
        role="menu"
        aria-label="Tree item actions"
        class="fixed z-[80] min-w-[176px] origin-top-left rounded-xl bg-zinc-900 p-1 text-zinc-100 ring-1 ring-white/10 shadow-[0_24px_50px_-12px_rgba(0,0,0,0.45),0_8px_18px_-8px_rgba(0,0,0,0.25)] dark:bg-zinc-950 dark:ring-white/[0.08]"
        :style="`top: ${contextMenu.y}px; left: ${contextMenu.x}px;`"
    >
        {{-- Target label — tiny truncated header so the user has a clear
             "you're acting on THIS file" cue. Mono so it tracks the
             editor's filename styling. Hidden for the empty-area menu. --}}
        <template x-if="contextMenu.target">
            <div class="mb-1 max-w-[260px] truncate border-b border-white/[0.06] px-2.5 pb-1.5 pt-1 font-mono text-[10px] tracking-[0.06em] uppercase text-zinc-500" x-text="contextMenu.target.name"></div>
        </template>

        {{-- New File — always available. Parent path is whatever the right-
             click landed on: folder → inside it; file → its parent dir;
             empty tree area → root. Selection has already been updated by
             openContextMenu / openContextMenuAtRoot, so startCreating()
             reads the correct selectedDirectory. --}}
        <button
            type="button"
            role="menuitem"
            @click="closeContextMenu(); startCreating('file')"
            class="group flex w-full cursor-pointer items-center gap-2.5 rounded-lg px-2.5 py-1.5 text-left text-[13px] text-zinc-100 hover:bg-white/[0.06]"
        >
            <svg class="size-3.5 shrink-0 text-zinc-400 group-hover:text-zinc-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/>
                <path d="M14 2v4a2 2 0 0 0 2 2h4"/>
                <path d="M12 18v-6"/>
                <path d="M9 15h6"/>
            </svg>
            <span class="flex-1">New File</span>
        </button>

        {{-- New Folder --}}
        <button
            type="button"
            role="menuitem"
            @click="closeContextMenu(); startCreating('folder')"
            class="group flex w-full cursor-pointer items-center gap-2.5 rounded-lg px-2.5 py-1.5 text-left text-[13px] text-zinc-100 hover:bg-white/[0.06]"
        >
            <svg class="size-3.5 shrink-0 text-zinc-400 group-hover:text-zinc-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M12 10v6"/>
                <path d="M9 13h6"/>
                <path d="M20 20a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.69-.9L9.6 3.9A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2Z"/>
            </svg>
            <span class="flex-1">New Folder</span>
        </button>

        {{-- Rename + Delete are row-scoped — only render when the menu was
             opened on an actual file or folder. The empty-area menu has
             nothing to rename or delete. --}}
        <template x-if="contextMenu.target">
            <div>
                <div class="my-1 h-px bg-white/[0.06]"></div>

                {{-- Rename --}}
                <button
                    type="button"
                    role="menuitem"
                    @click="startRename(contextMenu.target.path, contextMenu.target.type, contextMenu.target.name)"
                    class="group flex w-full cursor-pointer items-center gap-2.5 rounded-lg px-2.5 py-1.5 text-left text-[13px] text-zinc-100 hover:bg-white/[0.06]"
                >
                    <svg class="size-3.5 shrink-0 text-zinc-400 group-hover:text-zinc-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M12 20h9"/>
                        <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4Z"/>
                    </svg>
                    <span class="flex-1">Rename</span>
                    <span class="font-mono text-[10px] text-zinc-500">↵</span>
                </button>

                {{-- Delete --}}
                <button
                    type="button"
                    role="menuitem"
                    @click="closeContextMenu(); $dispatch('dt-delete-selected')"
                    class="group flex w-full cursor-pointer items-center gap-2.5 rounded-lg px-2.5 py-1.5 text-left text-[13px] text-rose-400 hover:bg-rose-500/[0.12] hover:text-rose-300"
                >
                    <svg class="size-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M3 6h18"/>
                        <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/>
                        <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
                    </svg>
                    <span class="flex-1">Delete</span>
                    <span class="font-mono text-[10px] text-rose-500/60">⌫</span>
                </button>
            </div>
        </template>
    </div>
    </template>
    @endif
</div>

@assets
<script>
window.directoryTree = function directoryTree(readonly, writeToken) {
    return {
        readonly: readonly || false,
        writeToken: writeToken || null,

        expanded: {},
        prefetchCache: {},
        pendingFetches: {},
        files: {},
        fetchedDirectories: {},

        // Selection state
        selectedFile: null,
        selectedDirectory: null,

        // Creation state
        creatingType: null,
        creatingInPath: null,
        creatingName: '',
        isCreating: false,

        // Deletion state
        isDeleting: false,
        deletingPath: null,

        // Rename state — mirrors the creation state above. `renamingPath`
        // is the file currently being renamed (null when no rename is
        // active), `renamingName` is the live input value, `renamingOriginal`
        // is the unedited filename used to detect "no change" submissions
        // so a stray Enter doesn't fire a pointless server round-trip.
        renamingPath: null,
        renamingName: '',
        renamingOriginal: '',
        isRenaming: false,

        // Context menu state — opened via right-click on a tree row.
        // `target` carries everything the menu items need (path, type,
        // name) so the same menu DOM can drive actions for either a file
        // or a folder. Coordinates are viewport-pixel positions; they're
        // recomputed in $nextTick after first paint to flip the menu
        // along whichever axis would overflow.
        contextMenu: { open: false, x: 0, y: 0, target: null },

        // Drag-and-drop state for moving files/folders between folders.
        // `dragSource` is the path of the node currently being dragged
        // (null when nothing is in flight). `dragSourceType` is 'file' or
        // 'directory' — used to gate cycle prevention on folder drags
        // (a folder can't be dropped into itself or a descendant).
        // `dragOverTarget` is the folder path the cursor is currently
        // over ('' for project root, null when nothing is hovered).
        // All three feed reactive x-class bindings on tree rows so the
        // dragged node dims and the hovered folder gets a soft ring.
        dragSource: null,
        dragSourceType: null,
        dragOverTarget: null,

        config: {
            disk: @js($disk),
            baseDir: @js($baseDir),
            exclude: @js($exclude),
            lazyDirs: @js($lazyDirs),
            animateCollapse: @js($animateCollapse),
        },

        init() {
            this.rebuildPrefetchCache();
            // Pre-fetch children of all visible root-level directories
            this.prefetchVisibleChildren('');
            // Broadcast creating state so the wrapper toolbar can disable buttons
            this.$watch('creatingType', (value) => {
                this.$dispatch('dt-creating-state', { creating: value !== null });
            });
        },

        rebuildPrefetchCache() {
            // Mark server-rendered directories as preloaded
            this.prefetchCache = {};
            // Root may have new files after refreshTree — reset so content
            // prefetch re-runs for the visible top level.
            delete this.fetchedDirectories[''];

            this.$el.querySelectorAll('[data-loaded]').forEach(el => {
                const path = el.getAttribute('data-children-for');
                if (path === null) return;

                const childDirs = [];
                el.querySelectorAll(':scope > [data-dir-path]').forEach(child => {
                    const dirPath = child.getAttribute('data-dir-path');
                    const isLazy = child.getAttribute('data-lazy') === 'true';
                    if (dirPath && !isLazy) {
                        childDirs.push(dirPath);
                    }
                });

                const childFiles = [];
                el.querySelectorAll(':scope > [data-file-path]').forEach(child => {
                    const filePath = child.getAttribute('data-file-path');
                    if (filePath) childFiles.push(filePath);
                });

                this.prefetchCache[path] = { html: null, childDirs, childFiles, preloaded: true };
            });

            // Eagerly prefetch only root-level file content. Sub-folder files
            // are batch-prefetched lazily when the user expands their parent
            // (via prefetchVisibleChildren), which avoids hammering the disk
            // for folders the user never opens.
            const rootCache = this.prefetchCache[''];
            if (rootCache && rootCache.childFiles && rootCache.childFiles.length > 0) {
                this.fetchFilesInDirectory('', rootCache.childFiles);
            }
        },

        selectFile(path) {
            this.selectedFile = path;
            // Derive parent directory from file path
            const lastSlash = path.lastIndexOf('/');
            this.selectedDirectory = lastSlash > -1 ? path.substring(0, lastSlash) : '';
            this.$dispatch('directory-tree-selection-changed', { file: this.selectedFile, directory: this.selectedDirectory });
        },

        selectDirectory(path) {
            this.selectedDirectory = path;
            this.selectedFile = null;
            this.$dispatch('directory-tree-selection-changed', { file: null, directory: this.selectedDirectory });
        },

        startCreating(type) {
            if (this.readonly) return;
            this.creatingType = type;
            this.creatingName = '';
            this.creatingInPath = this.selectedDirectory ?? '';

            // Auto-expand the target directory if it's not root and not
            // already expanded — the inline-create input lives inside
            // the folder's children container and would be display:none
            // (via the parent x-show) if the folder were collapsed.
            if (this.creatingInPath !== '' && !this.expanded[this.creatingInPath]) {
                this.expanded[this.creatingInPath] = true;
            }

            // Focus the matching input. Branch up-front on creatingInPath
            // instead of probing $refs first — the earlier version always
            // checked $refs.rootCreationInput before the directory-scoped
            // query, which meant a stale ref from a prior root creation
            // could steal focus from the directory-scoped input.
            // Two-pass: $nextTick handles the common case where Alpine
            // has already flushed the template x-if; the rAF fallback
            // covers the auto-expand case where the children container's
            // x-show needs an extra frame before the input is actually
            // focusable.
            const tryFocus = () => {
                let input = null;
                if (this.creatingInPath === '') {
                    input = this.$refs.rootCreationInput || null;
                } else {
                    input = this.$el.querySelector(
                        '[data-creation-input="' + CSS.escape(this.creatingInPath) + '"]'
                    );
                }
                if (input && document.contains(input)) {
                    input.focus();
                    return true;
                }
                return false;
            };
            this.$nextTick(() => {
                if (tryFocus()) return;
                requestAnimationFrame(() => tryFocus());
            });
        },

        async confirmCreation() {
            // Re-entrancy guard — prevents blur from triggering a second call
            if (this.isCreating) return;

            const name = this.creatingName.trim();
            if (!name) {
                this.cancelCreation();
                return;
            }

            // Client-side validation mirroring the server. Blocking quotes
            // keeps the hand-replaced attribute templates safe too — a path
            // containing a single quote would break the embedded Alpine
            // expressions after token replacement.
            if (name === '.' || name === '..' || /[\/\\\\'"<>]/.test(name)) {
                this.$dispatch('directory-tree-error', { message: 'Invalid name' });
                return;
            }

            this.isCreating = true;
            const type = this.creatingType;
            const parentPath = this.creatingInPath;
            const newPath = parentPath ? parentPath + '/' + name : name;
            const endpoint = type === 'file' ? '/katana/directory-create-file' : '/katana/directory-create-folder';

            // ——— OPTIMISTIC UI ———
            // Dismiss the input, insert the new item node into the DOM in
            // sorted order, and update internal caches — all synchronously,
            // before the server round-trip. The item appears instantly.
            this.cancelCreation();
            const optimisticNode = this.insertOptimisticItem(parentPath, name, newPath, type);

            const parentCache = this.prefetchCache[parentPath];
            if (parentCache) {
                if (type === 'folder') {
                    parentCache.childDirs = [...(parentCache.childDirs || []), newPath];
                } else {
                    parentCache.childFiles = [...(parentCache.childFiles || []), newPath];
                }
            }

            if (type === 'file') {
                this.files[newPath] = '';
                this.selectFile(newPath);
                this.$dispatch('file-selected', [{ file: newPath, content: '', focus: true }]);
            } else {
                this.selectDirectory(newPath);
            }

            // Rollback helper — undoes the optimistic state if the server
            // rejects the create (name collision, permission, disk error).
            const rollback = () => {
                if (optimisticNode && optimisticNode.isConnected) {
                    optimisticNode.remove();
                }
                if (parentCache) {
                    if (type === 'folder') {
                        parentCache.childDirs = (parentCache.childDirs || []).filter(p => p !== newPath);
                    } else {
                        parentCache.childFiles = (parentCache.childFiles || []).filter(p => p !== newPath);
                    }
                }
                if (type === 'file') {
                    delete this.files[newPath];
                }
                if (this.selectedFile === newPath) this.selectedFile = null;
                if (this.selectedDirectory === newPath) this.selectedDirectory = null;
            };

            const csrfToken = document.querySelector('meta[name=csrf-token]');

            try {
                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken ? csrfToken.content : '',
                    },
                    body: JSON.stringify({
                        disk: this.config.disk,
                        baseDir: this.config.baseDir,
                        parentPath: parentPath,
                        name: name,
                        _write_token: this.writeToken,
                    }),
                });

                const data = await response.json();

                if (data.error) {
                    rollback();
                    this.$dispatch('directory-tree-error', { message: data.error });
                    return;
                }

                // Success — the optimistic node stays; drop its transient marker.
                if (optimisticNode) {
                    optimisticNode.removeAttribute('data-optimistic');
                }
                this.$dispatch('directory-tree-created', { type, path: data.path, name });
            } catch (err) {
                rollback();
                console.error('Error creating ' + type + ':', err);
                this.$dispatch('directory-tree-error', { message: 'Failed to create ' + type });
            } finally {
                this.isCreating = false;
            }
        },

        insertOptimisticItem(parentPath, name, newPath, type) {
            const selector = parentPath === ''
                ? '[data-children-for=""]'
                : '[data-children-for="' + CSS.escape(parentPath) + '"]';
            const container = document.querySelector(selector);
            if (!container) return null;

            const isRoot = parentPath === '';
            const templateKey = type === 'folder'
                ? (isRoot ? 'folder-root' : 'folder-nested')
                : (isRoot ? 'file-root' : 'file-nested');
            const tmpl = document.querySelector('template[data-katana-dt-template="' + templateKey + '"]');
            if (!tmpl) return null;

            // Our name validator blocks quote characters, but parent paths
            // created before the validator existed may legitimately contain
            // '&' '<' '>'. HTML-escape both tokens on replacement so the
            // attribute values and visible text stay well-formed. (Alpine
            // decodes the attribute entities when evaluating expressions.)
            const escapeHtml = (s) => s
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;');
            const html = tmpl.innerHTML
                .replaceAll('__KATANA_DT_PATH__', escapeHtml(newPath))
                .replaceAll('__KATANA_DT_NAME__', escapeHtml(name));

            const tmp = document.createElement('div');
            tmp.innerHTML = html.trim();
            const node = tmp.firstElementChild;
            if (!node) return null;

            node.setAttribute('data-optimistic', 'true');

            // Sort insertion matches the server's sortStructure(): directories
            // first, alphabetical within each group (case-insensitive).
            const lowerName = name.toLowerCase();
            let anchor = null;
            for (const sibling of container.children) {
                if (sibling.tagName === 'TEMPLATE') continue;
                const sibIsDir = sibling.hasAttribute('data-dir-path');
                const sibPath = sibling.getAttribute('data-dir-path') || sibling.getAttribute('data-file-path') || '';
                const sibName = (sibPath.split('/').pop() || '').toLowerCase();

                if (type === 'folder') {
                    if (!sibIsDir) { anchor = sibling; break; }
                    if (sibName > lowerName) { anchor = sibling; break; }
                } else {
                    if (sibIsDir) continue;
                    if (sibName > lowerName) { anchor = sibling; break; }
                }
            }

            if (anchor) container.insertBefore(node, anchor);
            else container.appendChild(node);

            // Subtle fade+slide entrance so the new row doesn't pop in abruptly.
            node.style.opacity = '0';
            node.style.transform = 'translateY(-2px)';
            node.style.transition = 'opacity 150ms ease-out, transform 150ms ease-out';

            // The optimistic template is rendered once at PHP-render time
            // against the placeholder name (__KATANA_DT_NAME__), so the
            // server-side $isImage check there always sees "false". Swap
            // the generic file icon for the picture icon in JS now that we
            // know the real name. Kept in sync with the Blade regex above.
            if (type === 'file' && /\.(png|jpe?g|gif|webp|avif|svg|bmp|ico)$/i.test(name)) {
                const iconSvg = node.querySelector('svg');
                if (iconSvg) {
                    iconSvg.outerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 stroke-current text-violet-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>';
                }
            }

            Alpine.initTree(node);

            requestAnimationFrame(() => {
                node.style.opacity = '';
                node.style.transform = '';
                setTimeout(() => { node.style.transition = ''; }, 200);
            });

            return node;
        },

        cancelCreation() {
            this.creatingType = null;
            this.creatingInPath = null;
            this.creatingName = '';
        },

        async deleteSelected() {
            if (this.readonly || this.isDeleting) return;

            const isFile = this.selectedFile !== null;
            const path = isFile ? this.selectedFile : this.selectedDirectory;
            const type = isFile ? 'file' : 'directory';

            if (!path) return;

            this.isDeleting = true;
            this.deletingPath = path;
            this.$dispatch('dt-deleting-state', { deleting: true });

            const csrfToken = document.querySelector('meta[name=csrf-token]');
            const fadePromise = new Promise(resolve => setTimeout(resolve, 200));

            try {
                const fetchPromise = fetch('/katana/directory-delete', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken ? csrfToken.content : '',
                    },
                    body: JSON.stringify({
                        disk: this.config.disk,
                        baseDir: this.config.baseDir,
                        path: path,
                        type: type,
                        _write_token: this.writeToken,
                    }),
                }).then(r => r.json());

                const [data] = await Promise.all([fetchPromise, fadePromise]);

                if (data.error) {
                    this.$dispatch('directory-tree-error', { message: data.error });
                    return;
                }

                // Clear selection
                this.selectedFile = null;
                this.selectedDirectory = null;
                this.$dispatch('directory-tree-selection-changed', { file: null, directory: null });

                // Clean up caches
                if (isFile) {
                    delete this.files[path];
                } else {
                    Object.keys(this.expanded).forEach(key => {
                        if (key === path || key.startsWith(path + '/')) {
                            delete this.expanded[key];
                        }
                    });
                    Object.keys(this.prefetchCache).forEach(key => {
                        if (key === path || key.startsWith(path + '/')) {
                            delete this.prefetchCache[key];
                        }
                    });
                    Object.keys(this.files).forEach(key => {
                        if (key.startsWith(path + '/')) {
                            delete this.files[key];
                        }
                    });
                    // Mirror the prefetchCache cleanup — without it, a
                    // future folder created at the same path would be
                    // mis-identified as already-fetched.
                    Object.keys(this.fetchedDirectories).forEach(key => {
                        if (key === path || key.startsWith(path + '/')) {
                            delete this.fetchedDirectories[key];
                        }
                    });
                }

                // Refresh tree
                await this.$wire.refreshTree();
                this.rebuildPrefetchCache();

                this.$dispatch('directory-tree-deleted', { type, path });
            } catch (err) {
                console.error('Error deleting ' + type + ':', err);
                this.$dispatch('directory-tree-error', { message: 'Failed to delete ' + type });
            } finally {
                this.isDeleting = false;
                this.deletingPath = null;
                this.$dispatch('dt-deleting-state', { deleting: false });
            }
        },

        // ─────────────── Context menu ───────────────
        // Right-click on a tree row routes through openContextMenu, which
        // first selects the row (so the menu's actions read from the same
        // selection state the toolbar buttons do) and positions the menu
        // at cursor coords. After paint we re-measure and flip the menu
        // along whichever axis would clip — top-right corner of the menu
        // anchors to the cursor by default, but if the row was clicked
        // near the right edge of the viewport the menu shifts left, and
        // similarly for the bottom edge.
        openContextMenu(event, path, type, name) {
            if (this.readonly) return;
            event.preventDefault();
            event.stopPropagation();

            // Mirror OS-level behavior: right-click selects the row first,
            // so the same selection feeds delete + any future actions.
            // selectFile also sets selectedDirectory to the file's parent,
            // so a subsequent startCreating() drops new items as siblings
            // of the right-clicked file — matching VS Code / Finder.
            if (type === 'file') {
                this.selectFile(path);
            } else {
                this.selectDirectory(path);
            }

            this.contextMenu = {
                open: true,
                x: event.clientX,
                y: event.clientY,
                target: { path, type, name },
            };

            this.clampContextMenuToViewport();
        },

        // Empty-area right-click: opens the context menu with no target
        // row, so only the "New File" / "New Folder" actions render. The
        // create actions read from selectedDirectory, which we clear here
        // so the new item lands at the project root regardless of whatever
        // file or folder was previously highlighted.
        openContextMenuAtRoot(event) {
            if (this.readonly) return;
            // Don't open the menu while an inline create input is already
            // up — would race with creatingInPath and leave a stray input.
            if (this.creatingType !== null) return;
            event.preventDefault();

            this.selectedFile = null;
            this.selectedDirectory = null;
            this.$dispatch('directory-tree-selection-changed', { file: null, directory: null });

            this.contextMenu = {
                open: true,
                x: event.clientX,
                y: event.clientY,
                target: null,
            };

            this.clampContextMenuToViewport();
        },

        // Re-measure the rendered menu and nudge it back inside the
        // viewport along whichever axis would clip — by default the
        // menu's top-left anchors to the cursor.
        clampContextMenuToViewport() {
            this.$nextTick(() => {
                const menu = document.querySelector('[data-katana-context-menu]');
                if (!menu) return;
                const rect = menu.getBoundingClientRect();
                const vw = window.innerWidth;
                const vh = window.innerHeight;
                const margin = 8;
                let nx = this.contextMenu.x;
                let ny = this.contextMenu.y;
                if (nx + rect.width > vw - margin) nx = Math.max(margin, vw - rect.width - margin);
                if (ny + rect.height > vh - margin) ny = Math.max(margin, vh - rect.height - margin);
                if (nx !== this.contextMenu.x || ny !== this.contextMenu.y) {
                    this.contextMenu = { ...this.contextMenu, x: nx, y: ny };
                }
            });
        },

        closeContextMenu() {
            if (!this.contextMenu.open) return;
            // Keep x/y and target at the last open values so the
            // leave-transition fades the menu in place with its label
            // and click-targets intact. Resetting x/y to 0 would flicker
            // the menu over the top-left corner during the 75ms fade.
            // Resetting target to null would also break the Rename/Delete
            // buttons mid-fade if the user clicks them while it's still
            // visible. The next open overwrites all fields anyway.
            this.contextMenu = { ...this.contextMenu, open: false };
        },

        // ─────────────── Inline rename ───────────────
        // Mirrors the inline-create UX: target row's label is swapped for
        // an input, value seeded with the current name, stem auto-selected
        // (VS Code behavior — clicking the input's text first selects the
        // stem so the user can type the new base name without erasing the
        // extension). Enter confirms, Escape cancels, blur confirms only
        // if the value changed.
        startRename(path, type, name) {
            if (this.readonly) return;
            // v1 supports file rename only; folders fall through to a
            // no-op + toast so the menu item can stay visible for the
            // future v2 without growing a dead button now.
            if (type !== 'file') {
                this.$dispatch('directory-tree-error', { message: 'Renaming folders is coming soon.' });
                this.closeContextMenu();
                return;
            }
            this.closeContextMenu();
            this.renamingPath = path;
            this.renamingName = name;
            this.renamingOriginal = name;

            // Focus + select-stem on the next tick so the input has
            // mounted. Selecting just the chars before the last '.'
            // matches VS Code, Finder, and Windows Explorer — the most
            // common rename action is changing the base name, not the
            // extension.
            this.$nextTick(() => {
                const input = document.querySelector('[data-katana-rename-input="' + CSS.escape(path) + '"]');
                if (!input) return;
                input.focus();
                const dot = name.lastIndexOf('.');
                if (dot > 0) {
                    input.setSelectionRange(0, dot);
                } else {
                    input.select();
                }
            });
        },

        cancelRename() {
            this.renamingPath = null;
            this.renamingName = '';
            this.renamingOriginal = '';
        },

        async confirmRename() {
            if (this.readonly || this.isRenaming) return;
            if (!this.renamingPath) return;

            const original = this.renamingOriginal;
            const next = (this.renamingName || '').trim();
            const oldPath = this.renamingPath;

            // No-change or empty value — treat as cancel. Empty falls
            // through to a toast so the user knows the rename was
            // discarded (a silent no-op feels broken).
            if (next === '' || next === original) {
                if (next === '') {
                    this.$dispatch('directory-tree-error', { message: 'Filename can\'t be empty.' });
                }
                this.cancelRename();
                return;
            }

            // Same name validation as createFile — reject slashes and
            // navigation segments so the user can't escape their folder.
            // The '\\\\' is intentional: this whole script lives inside
            // an assets block (see Blade directive above) whose
            // preg_replace collapses '\\' to '\', so we need '\\\\' in
            // source to land as '\\' in the browser (a JS string with a
            // single literal backslash). See the existing validator at
            // the top of this script and CLAUDE.md section 9 patch 2.
            if (next.includes('/') || next.includes('\\\\') || next === '.' || next === '..') {
                this.$dispatch('directory-tree-error', { message: 'Invalid filename.' });
                return;
            }

            const slash = oldPath.lastIndexOf('/');
            const parent = slash >= 0 ? oldPath.slice(0, slash) : '';
            const newPath = parent ? parent + '/' + next : next;

            this.isRenaming = true;
            const csrfToken = document.querySelector('meta[name=csrf-token]');

            try {
                const response = await fetch('/katana/directory-rename', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken ? csrfToken.content : '',
                    },
                    body: JSON.stringify({
                        disk: this.config.disk,
                        baseDir: this.config.baseDir,
                        from: oldPath,
                        to: newPath,
                        type: 'file',
                        _write_token: this.writeToken,
                    }),
                });

                const data = await response.json();
                if (!response.ok || !data?.ok) {
                    this.$dispatch('directory-tree-error', { message: data?.error || 'Rename failed.' });
                    return;
                }

                // DOM swap: remove the old row's node, then re-insert via
                // insertOptimisticItem so the new row has the correct
                // click handler / data attributes baked from the template.
                // Editing in place would require rebinding the @click's
                // Blade-compiled path string — much heavier than a swap.
                const oldNode = document.querySelector('[data-file-path="' + CSS.escape(oldPath) + '"]');
                if (oldNode) oldNode.remove();
                const newNode = this.insertOptimisticItem(parent, next, newPath, 'file');
                if (newNode) newNode.removeAttribute('data-optimistic');

                // Hand the content cache over — old path becomes invalid,
                // new path inherits whatever was cached for old.
                if (this.files[oldPath] !== undefined) {
                    this.files[newPath] = this.files[oldPath];
                    delete this.files[oldPath];
                }
                // Swap the entry in the parent's childFiles list so
                // anything that derives from prefetchCache (the palette,
                // future siblings) sees the new name immediately.
                const parentCache = this.prefetchCache[parent];
                if (parentCache && parentCache.childFiles) {
                    parentCache.childFiles = parentCache.childFiles.map((p) => p === oldPath ? newPath : p);
                }

                if (this.selectedFile === oldPath) this.selectedFile = newPath;

                this.cancelRename();
                this.$dispatch('directory-tree-renamed', { from: oldPath, to: newPath, type: 'file', name: next });
            } catch (err) {
                console.error('Error renaming file:', err);
                this.$dispatch('directory-tree-error', { message: 'Rename failed.' });
            } finally {
                this.isRenaming = false;
            }
        },

        // ─────────────── Drag-to-move ───────────────
        // File AND folder rows are draggable. Folder rows and the root
        // container are drop targets. Drop computes the new parent folder
        // from the event target (closest folder ancestor, or root) and
        // reuses the same /katana/directory-rename endpoint that powers
        // inline rename — moves are just renames where the parent changed.
        //
        // Custom dataTransfer types ('application/x-katana-file' or
        // 'application/x-katana-folder') distinguish internal tree drags
        // from OS-level file drops (which the editor's full-screen
        // dropzone handles via the 'Files' type). The two coexist without
        // stepping on each other because the type check is the first line
        // of every drag handler.
        //
        // Folder drags carry a cycle guard: dropping a folder onto itself
        // or any of its descendants is suppressed both in dragover (no
        // highlight) and at the drop handler (no network call).

        _dragHasInternalNode(event) {
            const types = event && event.dataTransfer ? event.dataTransfer.types : null;
            if (!types) return false;
            const arr = Array.from(types);
            return arr.indexOf('application/x-katana-file') !== -1
                || arr.indexOf('application/x-katana-folder') !== -1;
        },

        // Where would a drop on `el` land? Folder row to folder, file row
        // to file's parent folder, anything else to project root (''). Same
        // smart routing the upload dropzone uses, so the two affordances
        // feel symmetric.
        _moveTargetForElement(el) {
            if (!el) return '';
            const folder = el.closest && el.closest('[data-dir-path]');
            if (folder) return folder.getAttribute('data-dir-path') || '';
            const file = el.closest && el.closest('[data-file-path]');
            if (file) {
                const p = file.getAttribute('data-file-path') || '';
                const slash = p.lastIndexOf('/');
                return slash > 0 ? p.slice(0, slash) : '';
            }
            return '';
        },

        // Cycle guard for folder drags. Drops on the folder itself or any
        // of its own descendants would be ambiguous (or worse, corrupt
        // the storage). null target means "invalid, don't highlight,
        // don't drop." Returns the original target for non-folder drags.
        _resolveDropTargetForDrag(rawTarget) {
            if (this.dragSourceType !== 'directory' || !this.dragSource) return rawTarget;
            if (rawTarget === this.dragSource) return null;
            if (rawTarget && rawTarget.startsWith(this.dragSource + '/')) return null;
            return rawTarget;
        },

        onFileDragStart(event, path) {
            if (this.readonly || this.isRenaming) {
                event.preventDefault();
                return;
            }
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('application/x-katana-file', path);
            // text/plain fallback — some browsers/platforms refuse drops
            // to certain targets without it (and it lets a user drag the
            // filename out to a text-aware app like a note, harmless).
            event.dataTransfer.setData('text/plain', path);
            this.dragSource = path;
            this.dragSourceType = 'file';
            this.dragOverTarget = null;
        },

        onFolderDragStart(event, path) {
            if (this.readonly || this.isRenaming) {
                event.preventDefault();
                return;
            }
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('application/x-katana-folder', path);
            event.dataTransfer.setData('text/plain', path);
            this.dragSource = path;
            this.dragSourceType = 'directory';
            this.dragOverTarget = null;
        },

        onFileDragEnd() {
            this.dragSource = null;
            this.dragSourceType = null;
            this.dragOverTarget = null;
        },

        onFolderDragEnd() {
            this.dragSource = null;
            this.dragSourceType = null;
            this.dragOverTarget = null;
        },

        // Fires on every dragover inside the tree container. The .prevent
        // is what tells the browser "yes, this is a valid drop target" —
        // without it, drop never fires. We update dragOverTarget on every
        // call (cheap) so the highlight follows the cursor frame-by-frame
        // as it crosses between folders.
        onTreeDragOver(event) {
            if (!this._dragHasInternalNode(event)) return;
            event.preventDefault();
            const raw = this._moveTargetForElement(event.target);
            const target = this._resolveDropTargetForDrag(raw);
            // Folder-cycle case — set dropEffect to 'none' so the cursor
            // shows the not-allowed badge, and clear the highlight.
            if (target === null) {
                if (event.dataTransfer) event.dataTransfer.dropEffect = 'none';
                if (this.dragOverTarget !== null) this.dragOverTarget = null;
                return;
            }
            if (event.dataTransfer) event.dataTransfer.dropEffect = 'move';
            if (target !== this.dragOverTarget) this.dragOverTarget = target;
        },

        // Fires when the cursor leaves the tree container entirely.
        // Inner element transitions also fire dragleave on the parent,
        // so we guard with relatedTarget containment — if we're still
        // inside, ignore the event.
        onTreeDragLeave(event) {
            if (!this._dragHasInternalNode(event)) return;
            const related = event.relatedTarget;
            if (related && event.currentTarget && event.currentTarget.contains(related)) return;
            this.dragOverTarget = null;
        },

        async onTreeDrop(event) {
            if (!this._dragHasInternalNode(event)) return;
            event.preventDefault();

            // Recover source path from dataTransfer (the canonical source —
            // survives across iframe / window boundaries) with Alpine
            // state as a fallback.
            const sourceFile = event.dataTransfer.getData('application/x-katana-file');
            const sourceFolder = event.dataTransfer.getData('application/x-katana-folder');
            const fromPath = sourceFile || sourceFolder || this.dragSource;
            const type = sourceFile
                ? 'file'
                : (sourceFolder ? 'directory' : this.dragSourceType);

            const rawTarget = this._moveTargetForElement(event.target);
            const targetFolder = this._resolveDropTargetForDrag(rawTarget);

            // Clear visual state right away so the dim+ring don't linger
            // through the network round-trip.
            this.dragSource = null;
            this.dragSourceType = null;
            this.dragOverTarget = null;

            if (!fromPath || targetFolder === null) return;

            const slash = fromPath.lastIndexOf('/');
            const name = slash > -1 ? fromPath.slice(slash + 1) : fromPath;
            const toPath = targetFolder ? targetFolder + '/' + name : name;

            // Same-folder drop is a no-op — no toast, no network call.
            if (toPath === fromPath) return;

            if (type === 'directory') {
                await this._moveFolderNode(fromPath, toPath, name, targetFolder);
            } else {
                await this._moveFile(fromPath, toPath, name, targetFolder);
            }
        },

        async _moveFile(fromPath, toPath, name, parentPath) {
            if (this.isRenaming) return;
            this.isRenaming = true;

            // Optimistic DOM swap: lift the source row out, drop a new
            // optimistic row into the destination. If the destination
            // folder isn't expanded, insertOptimisticItem returns null
            // silently — the file moves on the server and shows up the
            // next time the user opens that folder.
            const oldNode = document.querySelector('[data-file-path="' + CSS.escape(fromPath) + '"]');
            const oldParent = oldNode ? oldNode.parentNode : null;
            const oldNext = oldNode ? oldNode.nextSibling : null;
            if (oldNode) oldNode.remove();
            const newNode = this.insertOptimisticItem(parentPath, name, toPath, 'file');

            const restore = () => {
                if (newNode && newNode.isConnected) newNode.remove();
                if (oldNode && oldParent) {
                    if (oldNext) oldParent.insertBefore(oldNode, oldNext);
                    else oldParent.appendChild(oldNode);
                }
            };

            const csrfToken = document.querySelector('meta[name=csrf-token]');

            try {
                const response = await fetch('/katana/directory-rename', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken ? csrfToken.content : '',
                    },
                    body: JSON.stringify({
                        disk: this.config.disk,
                        baseDir: this.config.baseDir,
                        from: fromPath,
                        to: toPath,
                        type: 'file',
                        _write_token: this.writeToken,
                    }),
                });
                const data = await response.json();
                if (!response.ok || !data || !data.ok) {
                    restore();
                    this.$dispatch('directory-tree-error', { message: (data && data.error) || 'Move failed.' });
                    return;
                }

                if (newNode) newNode.removeAttribute('data-optimistic');

                // Migrate prefetch cache to the new path so the next
                // click on the moved file doesn't re-fetch.
                if (this.files[fromPath] !== undefined) {
                    this.files[toPath] = this.files[fromPath];
                    delete this.files[fromPath];
                }
                // Keep both parents' prefetchCache child lists in sync —
                // mirror confirmCreation's approach. Without this, the
                // OLD parent still thinks it owns the file (toolbar +
                // palette derive their lists from this cache), and the
                // NEW parent doesn't know it has a new child until the
                // next refreshTree+rebuildPrefetchCache.
                const oldSlash = fromPath.lastIndexOf('/');
                const oldParentPath = oldSlash > -1 ? fromPath.slice(0, oldSlash) : '';
                const oldParentCache = this.prefetchCache[oldParentPath];
                if (oldParentCache && oldParentCache.childFiles) {
                    oldParentCache.childFiles = oldParentCache.childFiles.filter((p) => p !== fromPath);
                }
                const newParentCache = this.prefetchCache[parentPath];
                if (newParentCache) {
                    newParentCache.childFiles = [...(newParentCache.childFiles || []), toPath];
                }
                if (this.selectedFile === fromPath) this.selectedFile = toPath;

                this.$dispatch('directory-tree-renamed', { from: fromPath, to: toPath, type: 'file', name: name, action: 'move' });
            } catch (err) {
                console.error('Move failed:', err);
                restore();
                this.$dispatch('directory-tree-error', { message: 'Move failed.' });
            } finally {
                this.isRenaming = false;
            }
        },

        // Folder move — recursive on the server, so the optimistic UX
        // diverges from _moveFile. Doing a per-leaf DOM swap would lose
        // expansion state and leave nested data-* paths stale. Instead
        // we dim the source folder during the round-trip, then call
        // refreshTree on success to re-render with the new structure
        // baked in. Selection state + the prefetch caches are migrated
        // by prefix so the user's working context survives.
        async _moveFolderNode(fromPath, toPath, name, parentPath) {
            if (this.isRenaming) return;
            this.isRenaming = true;

            const oldNode = document.querySelector('[data-dir-path="' + CSS.escape(fromPath) + '"]');
            const previousOpacity = oldNode ? oldNode.style.opacity : '';
            if (oldNode) {
                oldNode.style.transition = 'opacity 150ms ease-out';
                oldNode.style.opacity = '0.4';
            }

            const restoreSource = () => {
                if (oldNode) {
                    oldNode.style.opacity = previousOpacity;
                    setTimeout(() => { oldNode.style.transition = ''; }, 200);
                }
            };

            const csrfToken = document.querySelector('meta[name=csrf-token]');

            try {
                const response = await fetch('/katana/directory-rename', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken ? csrfToken.content : '',
                    },
                    body: JSON.stringify({
                        disk: this.config.disk,
                        baseDir: this.config.baseDir,
                        from: fromPath,
                        to: toPath,
                        type: 'directory',
                        _write_token: this.writeToken,
                    }),
                });
                const data = await response.json();
                if (!response.ok || !data || !data.ok) {
                    restoreSource();
                    this.$dispatch('directory-tree-error', { message: (data && data.error) || 'Move failed.' });
                    return;
                }

                // Migrate prefix-scoped state: expanded folders, prefetch
                // cache entries, file content cache, and selection. Walk
                // each map's keys, rewrite from-prefix to to-prefix.
                const fromPrefix = fromPath + '/';
                const toPrefix = toPath + '/';
                const rewriteKey = (k) => {
                    if (k === fromPath) return toPath;
                    if (k.startsWith(fromPrefix)) return toPath + k.slice(fromPath.length);
                    return null;
                };
                // fetchedDirectories included so that re-expanding the
                // moved folder triggers a fresh prefetch — without the
                // rewrite, the moved subfolder's entry under the OLD
                // prefix lingers, and a later folder created at the
                // OLD path would be mis-identified as already-fetched
                // and skip its own prefetch.
                ['expanded', 'prefetchCache', 'files', 'fetchedDirectories'].forEach((bag) => {
                    const original = this[bag];
                    const next = {};
                    Object.keys(original).forEach((k) => {
                        const nk = rewriteKey(k);
                        next[nk === null ? k : nk] = original[k];
                    });
                    this[bag] = next;
                });
                if (this.selectedFile) {
                    const rewritten = rewriteKey(this.selectedFile);
                    if (rewritten !== null) this.selectedFile = rewritten;
                }
                if (this.selectedDirectory) {
                    const rewritten = rewriteKey(this.selectedDirectory);
                    if (rewritten !== null) this.selectedDirectory = rewritten;
                }

                // Refresh the tree from the server. This re-renders the
                // structure with the new paths baked into every row's
                // click handlers + data-* attributes (vs trying to
                // rebind them in place — much heavier).
                await this.$wire.refreshTree();
                // Mirror deleteSelected: rebuild the prefetch cache so
                // it matches the newly-rendered structure (otherwise a
                // moved subfolder's row could spin "Loading…" forever
                // because prefetchCache still has stale loaded markers
                // under the old prefix).
                this.rebuildPrefetchCache();

                this.$dispatch('directory-tree-renamed', {
                    from: fromPath,
                    to: toPath,
                    type: 'directory',
                    name: name,
                    action: 'move',
                });
            } catch (err) {
                console.error('Folder move failed:', err);
                restoreSource();
                this.$dispatch('directory-tree-error', { message: 'Move failed.' });
            } finally {
                this.isRenaming = false;
            }
        },

        async refreshDirectory(path) {
            // Normalize null to empty string for root
            if (path === null) path = '';

            // Clear prefetch cache for this path so it re-fetches
            delete this.prefetchCache[path];
            delete this.pendingFetches[path];
            // Allow the post-refresh batch prefetch to run — without this, a
            // newly-created file in an already-prefetched folder would never
            // have its content cached.
            delete this.fetchedDirectories[path];

            // Find the container element for this directory.
            // Note: CSS.escape('') throws, so we handle empty string directly.
            // We query against `document` rather than `this.$el` because
            // Livewire's post-mount re-render can replace the component root,
            // leaving Alpine's $el detached from the live DOM — in which case
            // this.$el.querySelector(...) silently returns null and the new
            // item is never injected.
            const selector = path === ''
                ? '[data-children-for=""]'
                : '[data-children-for="' + CSS.escape(path) + '"]';
            const containerEl = document.querySelector(selector);
            if (containerEl) {
                containerEl.removeAttribute('data-loaded');
            }

            // Re-fetch children from the API
            const depth = path ? path.split('/').length : 0;
            const data = await this.fetchChildren(path, depth);
            if (data && containerEl) {
                this.injectChildren(path, containerEl, data);
                this.prefetchVisibleChildren(path);
            }
        },

        toggle(path, isLazy, isSymlink, level, containerEl) {
            this.expanded[path] = !this.expanded[path];

            if (!this.expanded[path] || isSymlink) {
                return;
            }

            // If children are server-rendered (in DOM with data-loaded)
            if (containerEl && containerEl.hasAttribute('data-loaded')) {
                this.prefetchVisibleChildren(path);
                return;
            }

            // If we have cached data from prefetch (including empty directories)
            if (this.prefetchCache[path] && this.prefetchCache[path].loaded) {
                this.injectChildren(path, containerEl, this.prefetchCache[path]);
                this.prefetchVisibleChildren(path);
                return;
            }

            // Otherwise fetch from API
            this.fetchChildren(path, level).then(data => {
                if (data && this.expanded[path]) {
                    this.injectChildren(path, containerEl, data);
                    this.prefetchVisibleChildren(path);
                }
            });
        },

        fetchChildren(path, level) {
            // Return cached data if available (including empty directories)
            if (this.prefetchCache[path] && this.prefetchCache[path].loaded) {
                return Promise.resolve(this.prefetchCache[path]);
            }

            // Return pending fetch if one exists
            if (this.pendingFetches[path]) {
                return this.pendingFetches[path];
            }

            const csrfToken = document.querySelector('meta[name=csrf-token]');
            this.pendingFetches[path] = fetch('/katana/directory-children', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken ? csrfToken.content : '',
                },
                body: JSON.stringify({
                    disk: this.config.disk,
                    baseDir: this.config.baseDir,
                    path: path,
                    exclude: this.config.exclude,
                    lazyDirs: this.config.lazyDirs,
                    level: level,
                    readonly: this.readonly,
                    animateCollapse: this.config.animateCollapse,
                }),
            })
            .then(r => r.json())
            .then(data => {
                this.prefetchCache[path] = {
                    html: data.html || '',
                    childDirs: data.childDirs || [],
                    childFiles: data.childFiles || [],
                    preloaded: false,
                    loaded: true,
                };
                return this.prefetchCache[path];
            })
            .catch(err => {
                console.error('Error fetching directory children:', err);
                return null;
            })
            .finally(() => {
                delete this.pendingFetches[path];
            });

            return this.pendingFetches[path];
        },

        injectChildren(path, containerEl, data) {
            if (!containerEl || !data) return;

            // Only replace innerHTML when we actually have content to inject.
            // An empty assignment would destroy sibling templates (e.g. the
            // Loading spinner) that depend on Alpine state.
            if (data.html) {
                containerEl.innerHTML = data.html;
                Alpine.initTree(containerEl);
            }
            containerEl.setAttribute('data-loaded', 'true');
        },

        prefetchVisibleChildren(parentPath) {
            const cached = this.prefetchCache[parentPath];
            if (!cached) return;

            // Structure-level prefetch: grab the HTML of nested dirs so the
            // next expand doesn't round-trip.
            (cached.childDirs || []).forEach(childPath => {
                if (!this.prefetchCache[childPath] && !this.pendingFetches[childPath]) {
                    const parentDepth = parentPath.split('/').length;
                    this.fetchChildren(childPath, parentDepth + 1);
                }
            });

            // Content-level prefetch: batch-read the files visible at this
            // level so clicking any one of them is instant.
            if ((cached.childFiles || []).length > 0) {
                this.fetchFilesInDirectory(parentPath, cached.childFiles);
            }
        },

        // File content prefetch & cache.
        //
        // `this.files[path]` values:
        //   undefined — never fetched
        //   null      — fetched but unavailable (binary, too large, or error)
        //   string    — file contents (empty string is a valid cached value)
        //
        // Callers must check `!== undefined`, not truthiness — empty files are
        // real cache hits.

        fetchFileContent(fullPath) {
            if (this.files[fullPath] !== undefined) {
                return Promise.resolve(this.files[fullPath]);
            }
            // If a batch prefetch is already reading this path, reuse its
            // Promise so a hover during a batch doesn't fire a second request.
            if (this.pendingFetches['file:' + fullPath]) {
                return this.pendingFetches['file:' + fullPath];
            }

            const csrfToken = document.querySelector('meta[name=csrf-token]');
            const promise = fetch('/katana/file-content', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken ? csrfToken.content : '',
                },
                body: JSON.stringify({
                    disk: this.config.disk,
                    baseDir: this.config.baseDir,
                    path: fullPath,
                }),
            })
            .then(response => response.json().catch(() => ({ error: 'Bad response' })))
            .then(data => {
                const content = (data && typeof data.content === 'string') ? data.content : null;
                this.files[fullPath] = content;
                return content;
            })
            .catch(() => {
                this.files[fullPath] = null;
                return null;
            })
            .finally(() => {
                delete this.pendingFetches['file:' + fullPath];
            });

            this.pendingFetches['file:' + fullPath] = promise;
            return promise;
        },

        fetchFilesInDirectory(dirPath, files) {
            if (this.fetchedDirectories[dirPath]) return;

            const filesToFetch = (files || []).filter(f =>
                this.files[f] === undefined && !this.pendingFetches['file:' + f]
            );

            if (filesToFetch.length === 0) {
                this.fetchedDirectories[dirPath] = true;
                return;
            }

            // Per-file resolver so a concurrent fetchFileContent() hover can
            // await the in-flight batch rather than starting a duplicate fetch.
            const resolvers = {};
            filesToFetch.forEach(f => {
                this.pendingFetches['file:' + f] = new Promise(resolve => {
                    resolvers[f] = resolve;
                });
            });

            const settle = (f, content) => {
                this.files[f] = content;
                resolvers[f](content);
            };

            const csrfToken = document.querySelector('meta[name=csrf-token]');
            fetch('/katana/batch-file-content', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken ? csrfToken.content : '',
                },
                body: JSON.stringify({
                    disk: this.config.disk,
                    baseDir: this.config.baseDir,
                    paths: filesToFetch,
                }),
            })
            .then(response => response.json())
            .then(data => {
                const contents = (data && data.contents) || {};
                filesToFetch.forEach(f => {
                    const content = typeof contents[f] === 'string' ? contents[f] : null;
                    settle(f, content);
                });
            })
            .catch(() => {
                filesToFetch.forEach(f => settle(f, null));
            })
            .finally(() => {
                filesToFetch.forEach(f => { delete this.pendingFetches['file:' + f]; });
                this.fetchedDirectories[dirPath] = true;
            });
        }
    }
}
</script>
@endassets
