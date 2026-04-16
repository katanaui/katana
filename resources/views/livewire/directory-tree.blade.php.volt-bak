<?php

use Livewire\Component;
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
    public bool $readonly = false;
    public bool $animateCollapse = false;
    public ?string $writeToken = null;

    public function mount($disk = 'local', $baseDir = '', $exclude = null, $lazyDirs = null, $showToolbar = true, $readonly = false, $animateCollapse = false)
    {
        $this->disk = $disk;
        $this->baseDir = $baseDir;
        $this->showToolbar = $showToolbar;
        $this->readonly = $readonly;
        $this->animateCollapse = $animateCollapse;

        if (!$this->readonly) {
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
                $children = [];

                // Recurse into non-lazy directories when depth > 1
                if ($depth > 1 && !$isLazy) {
                    $children = $this->getDirectoryStructure($diskPath, $depth - 1);
                }

                $structure[$entry] = [
                    'type' => 'directory',
                    'path' => $relativePath,
                    'lazy' => $isLazy,
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
            $children = [];

            if ($depth > 1 && !$isLazy) {
                $children = $this->getDirectoryStructure($dirKeyChild, $depth - 1);
            }

            $structure[$entry] = [
                'type' => 'directory',
                'path' => $relativePath,
                'lazy' => $isLazy,
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

<div class="relative flex flex-col h-full text-sm select-none scrollbar-hide" x-data="directoryTree(@js($readonly), @js($writeToken))" x-init="init()" @refresh-directory-tree.window="$wire.refreshTree()" @if(!$readonly) @dt-start-creating.window="startCreating($event.detail.type)" @dt-delete-selected.window="deleteSelected()" @endif>
    @if($showToolbar && !$readonly)
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
    <div class="flex-1 p-3 overflow-y-auto scrollbar-hide">
        <div data-children-for="" data-loaded="true">
            @foreach($structure as $name => $item)
                <x-katana.directory-tree-item
                    :name="$name"
                    :item="$item"
                    :level="0"
                    :readonly="$readonly"
                    :animateCollapse="$animateCollapse"
                />
            @endforeach
        </div>

        @if(!$readonly)
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
</div>

@script
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

        config: {
            disk: @js($disk),
            baseDir: @js($baseDir),
            exclude: @js($exclude),
            lazyDirs: @js($lazyDirs),
            animateCollapse: @js($animateCollapse),
        },

        init() {
            this.rebuildPrefetchCache();
            // Broadcast creating state so the wrapper toolbar can disable buttons
            this.$watch('creatingType', (value) => {
                this.$dispatch('dt-creating-state', { creating: value !== null });
            });
        },

        rebuildPrefetchCache() {
            // Mark server-rendered directories as preloaded
            this.prefetchCache = {};
            this.$el.querySelectorAll('[data-loaded]').forEach(el => {
                const path = el.getAttribute('data-children-for');
                if (path !== null) {
                    // Collect child dirs from the rendered HTML
                    const childDirs = [];
                    el.querySelectorAll(':scope > [data-dir-path]').forEach(child => {
                        const dirPath = child.getAttribute('data-dir-path');
                        const isLazy = child.getAttribute('data-lazy') === 'true';
                        if (dirPath && !isLazy) {
                            childDirs.push(dirPath);
                        }
                    });
                    this.prefetchCache[path] = { html: null, childDirs: childDirs, preloaded: true };
                }
            });
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

            // Auto-expand the target directory if it's not root and not already expanded
            if (this.creatingInPath !== '' && !this.expanded[this.creatingInPath]) {
                this.expanded[this.creatingInPath] = true;
            }

            this.$nextTick(() => {
                // Focus the input — check root input first, then look for directory-scoped inputs
                const rootInput = this.$refs.rootCreationInput;
                if (rootInput) {
                    rootInput.focus();
                    return;
                }
                // CSS.escape('') throws, so guard against empty string
                const escapedPath = this.creatingInPath === ''
                    ? ''
                    : CSS.escape(this.creatingInPath);
                const input = this.$el.querySelector('[data-creation-input="' + escapedPath + '"]');
                if (input) {
                    input.focus();
                }
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

            this.isCreating = true;
            const type = this.creatingType;
            const parentPath = this.creatingInPath;
            const newPath = parentPath ? parentPath + '/' + name : name;
            const endpoint = type === 'file' ? '/katana/directory-create-file' : '/katana/directory-create-folder';

            // OPTIMISTIC UI — dismiss the input and (for files) open the
            // editor on the empty new file before the server round-trip.
            // The actual S3 write and tree refresh happen below; the tree
            // gets the real DOM node when refreshTree morphs it in.
            this.cancelCreation();
            if (type === 'file') {
                this.selectFile(newPath);
                this.$dispatch('file-selected', [{ file: newPath, content: '', focus: true }]);
            } else {
                this.selectDirectory(newPath);
            }

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
                    this.$dispatch('directory-tree-error', { message: data.error });
                    return;
                }

                await this.$wire.refreshTree();
                this.rebuildPrefetchCache();

                // refreshTree only walks 2 levels deep, so creates inside
                // nested folders (parentPath like "foo/bar") leave the parent's
                // children container empty in the morphed DOM AND wipe its
                // prefetchCache entry, which surfaces as an infinite spinner.
                // Force-refresh that container directly so the new item shows
                // without requiring a manual collapse+expand.
                if (parentPath.includes('/')) {
                    await this.refreshDirectory(parentPath);
                }

                this.$dispatch('directory-tree-created', { type, path: data.path, name });
            } catch (err) {
                console.error('Error creating ' + type + ':', err);
                this.$dispatch('directory-tree-error', { message: 'Failed to create ' + type });
            } finally {
                this.isCreating = false;
            }
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

        async refreshDirectory(path) {
            // Normalize null to empty string for root
            if (path === null) path = '';

            // Clear prefetch cache for this path so it re-fetches
            delete this.prefetchCache[path];
            delete this.pendingFetches[path];

            // Find the container element for this directory
            // Note: CSS.escape('') throws, so we handle empty string directly
            const selector = path === ''
                ? '[data-children-for=""]'
                : '[data-children-for="' + CSS.escape(path) + '"]';
            const containerEl = this.$el.querySelector(selector);
            if (containerEl) {
                containerEl.removeAttribute('data-loaded');
            }

            // Re-fetch children from the API
            const depth = path ? path.split('/').length : 0;
            const data = await this.fetchChildren(path, depth);
            if (data && containerEl) {
                this.injectChildren(path, containerEl, data);
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
            if (!cached || !cached.childDirs) return;

            cached.childDirs.forEach(childPath => {
                if (!this.prefetchCache[childPath] && !this.pendingFetches[childPath]) {
                    // Determine the level from the path depth
                    const parentDepth = parentPath.split('/').length;
                    this.fetchChildren(childPath, parentDepth + 1);
                }
            });
        },

        // File content fetching — disabled automatically if the endpoint returns 404
        _fileContentAvailable: null,

        fetchFileContent(fullPath) {
            // If we've detected the endpoint doesn't exist, bail out silently
            if (this._fileContentAvailable === false) {
                return Promise.resolve(null);
            }
            if (this.files[fullPath]) {
                return Promise.resolve(this.files[fullPath]);
            }
            if (this.pendingFetches['file:' + fullPath]) {
                return this.pendingFetches['file:' + fullPath];
            }
            const csrfToken = document.querySelector('meta[name=csrf-token]');
            this.pendingFetches['file:' + fullPath] = fetch('/file-content', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken ? csrfToken.content : '',
                },
                body: JSON.stringify({ path: fullPath })
            })
            .then(response => {
                if (response.status === 404) {
                    this._fileContentAvailable = false;
                    return null;
                }
                this._fileContentAvailable = true;
                return response.json();
            })
            .then(data => {
                if (!data) return null;
                if (!data.error) {
                    this.files[fullPath] = data.content;
                    return data.content;
                }
                return null;
            })
            .catch(error => {
                return null;
            })
            .finally(() => {
                delete this.pendingFetches['file:' + fullPath];
            });
            return this.pendingFetches['file:' + fullPath];
        },

        fetchFilesInDirectory(dirPath, files) {
            if (this._fileContentAvailable === false) return;
            if (this.fetchedDirectories[dirPath]) return;

            const filesToFetch = files.filter(f =>
                !this.files[f] && !this.pendingFetches['file:' + f]
            );
            if (filesToFetch.length === 0) {
                this.fetchedDirectories[dirPath] = true;
                return;
            }
            filesToFetch.forEach(f => { this.pendingFetches['file:' + f] = true; });
            const csrfToken = document.querySelector('meta[name=csrf-token]');
            fetch('/batch-file-content', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken ? csrfToken.content : '',
                },
                body: JSON.stringify({ paths: filesToFetch })
            })
            .then(response => {
                if (response.status === 404) {
                    this._fileContentAvailable = false;
                    return null;
                }
                return response.json();
            })
            .then(data => {
                if (!data) return;
                const contents = data.contents || {};
                Object.entries(contents).forEach(([file, content]) => {
                    this.files[file] = content;
                });
            })
            .catch(error => {
                // Silently handle — endpoint may not exist
            })
            .finally(() => {
                filesToFetch.forEach(f => { delete this.pendingFetches['file:' + f]; });
                this.fetchedDirectories[dirPath] = true;
            });
        }
    }
}
</script>
@endscript
