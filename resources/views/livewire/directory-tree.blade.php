<?php

use Livewire\Volt\Component;
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

    public function mount($disk = 'local', $baseDir = '', $exclude = null, $lazyDirs = null, $showToolbar = true)
    {
        $this->disk = $disk;
        $this->baseDir = $baseDir;
        $this->showToolbar = $showToolbar;

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

    protected function getDirectoryStructure($baseDir, $depth = 1)
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

<div class="relative flex flex-col h-full text-sm select-none bg-stone-950 scrollbar-hide" x-data="directoryTree()" x-init="init()">
    @if($showToolbar)
    <div class="flex items-center justify-end gap-1 px-3 pt-2 pb-1 shrink-0">
        <button
            type="button"
            title="New File"
            :disabled="creatingType !== null"
            :class="creatingType !== null ? 'opacity-30 cursor-not-allowed' : 'hover:bg-stone-800 hover:text-white/90'"
            class="p-1 rounded text-white/50 transition-colors"
            @click="startCreating('file')"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M12 18v-6"/><path d="M9 15h6"/></svg>
        </button>
        <button
            type="button"
            title="New Folder"
            :disabled="creatingType !== null"
            :class="creatingType !== null ? 'opacity-30 cursor-not-allowed' : 'hover:bg-stone-800 hover:text-white/90'"
            class="p-1 rounded text-white/50 transition-colors"
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
                />
            @endforeach
        </div>

        {{-- Root-level inline creation input (outside container so it survives innerHTML refresh) --}}
        <template x-if="creatingType && creatingInPath === ''">
            <div class="flex items-center px-2 py-1 ml-0">
                <span class="mr-1.5 ml-3.5">
                    <template x-if="creatingType === 'folder'">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 text-white stroke-current" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 20a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.69-.9L9.6 3.9A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2Z"/></svg>
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
                    class="flex-1 px-1 py-0 text-sm bg-stone-800 border border-blue-500/50 rounded text-white/90 outline-none focus:border-blue-500"
                    placeholder="Enter name..."
                />
            </div>
        </template>
    </div>
</div>

<script>
function directoryTree() {
    return {
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

        config: {
            disk: @js($disk),
            baseDir: @js($baseDir),
            exclude: @js($exclude),
            lazyDirs: @js($lazyDirs),
        },

        init() {
            // Mark server-rendered directories as preloaded
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
                const input = this.$el.querySelector('[data-creation-input="' + CSS.escape(this.creatingInPath) + '"]');
                if (input) {
                    input.focus();
                }
            });
        },

        async confirmCreation() {
            const name = this.creatingName.trim();
            if (!name) {
                this.cancelCreation();
                return;
            }

            const type = this.creatingType;
            const parentPath = this.creatingInPath;
            const endpoint = type === 'file' ? '/katana/directory-create-file' : '/katana/directory-create-folder';

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
                    }),
                });

                const data = await response.json();

                if (data.error) {
                    this.$dispatch('directory-tree-error', { message: data.error });
                    this.cancelCreation();
                    return;
                }

                // Success — refresh the directory
                this.cancelCreation();
                await this.refreshDirectory(parentPath);

                // Select the newly created item
                if (type === 'file') {
                    this.selectFile(data.path);
                } else {
                    this.selectDirectory(data.path);
                }

                this.$dispatch('directory-tree-created', { type, path: data.path, name });
            } catch (err) {
                console.error('Error creating ' + type + ':', err);
                this.$dispatch('directory-tree-error', { message: 'Failed to create ' + type });
                this.cancelCreation();
            }
        },

        cancelCreation() {
            this.creatingType = null;
            this.creatingInPath = null;
            this.creatingName = '';
        },

        async refreshDirectory(path) {
            // Normalize null to empty string for root
            if (path === null) path = '';

            // Clear prefetch cache for this path so it re-fetches
            delete this.prefetchCache[path];
            delete this.pendingFetches[path];

            // Find the container element for this directory
            const containerEl = this.$el.querySelector('[data-children-for="' + CSS.escape(path) + '"]');
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

            // If we have cached HTML from prefetch
            if (this.prefetchCache[path] && this.prefetchCache[path].html) {
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
            // Return cached data if available
            if (this.prefetchCache[path] && this.prefetchCache[path].html) {
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
                }),
            })
            .then(r => r.json())
            .then(data => {
                this.prefetchCache[path] = {
                    html: data.html,
                    childDirs: data.childDirs || [],
                    preloaded: false,
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
            if (!containerEl || !data || !data.html) return;

            containerEl.innerHTML = data.html;
            containerEl.setAttribute('data-loaded', 'true');

            // Initialize Alpine on the new DOM elements
            Alpine.initTree(containerEl);
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

        // Keep existing file fetching methods
        fetchFileContent(fullPath) {
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
            .then(response => response.json())
            .then(data => {
                if (!data.error) {
                    this.files[fullPath] = data.content;
                    return data.content;
                } else {
                    throw new Error(data.error);
                }
            })
            .catch(error => {
                console.error('Error fetching file:', error);
                throw error;
            })
            .finally(() => {
                delete this.pendingFetches['file:' + fullPath];
            });
            return this.pendingFetches['file:' + fullPath];
        },

        fetchFilesInDirectory(dirPath, files) {
            if (this.fetchedDirectories[dirPath]) {
                return;
            }
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
            .then(response => response.json())
            .then(data => {
                const contents = data.contents || {};
                Object.entries(contents).forEach(([file, content]) => {
                    this.files[file] = content;
                });
            })
            .catch(error => {
                console.error('Error batch fetching files:', error);
            })
            .finally(() => {
                filesToFetch.forEach(f => { delete this.pendingFetches['file:' + f]; });
                this.fetchedDirectories[dirPath] = true;
            });
        }
    }
}
</script>
