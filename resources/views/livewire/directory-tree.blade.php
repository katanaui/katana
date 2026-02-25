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

    public function mount($disk = 'local', $baseDir = '', $exclude = null, $lazyDirs = null)
    {
        $this->disk = $disk;
        $this->baseDir = $baseDir;

        if ($exclude !== null) {
            $this->exclude = $exclude;
        }

        if ($lazyDirs !== null) {
            $this->lazyDirs = $lazyDirs;
        }

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

<div class="relative h-full text-sm select-none bg-stone-950 scrollbar-hide">
    <div x-data="directoryTree()" x-init="init()" class="p-3">
        @foreach($structure as $name => $item)
            <x-katana.directory-tree-item
                :name="$name"
                :item="$item"
                :level="0"
            />
        @endforeach
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
                if (path) {
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
