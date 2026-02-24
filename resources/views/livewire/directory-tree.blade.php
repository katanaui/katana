<?php

use Livewire\Volt\Component;
use Illuminate\Support\Facades\Storage;

new class extends Component {

    public string $disk = 'local';
    public string $baseDir = '';
    public array $exclude = ['node_modules', 'vendor', '.git', '.github', 'storage', '.claude'];
    public $structure = [];
    public $currentPath = '';
    public $files = [];

    public function mount($disk = 'local', $baseDir = '', $exclude = null)
    {
        $this->disk = $disk;
        $this->baseDir = $baseDir;

        if ($exclude !== null) {
            $this->exclude = $exclude;
        }

        $this->structure = $this->getDirectoryStructure($this->baseDir);
    }

    protected function getDiskRootPath()
    {
        $diskConfig = config("filesystems.disks.{$this->disk}");
        return rtrim($diskConfig['root'] ?? '', '/');
    }

    protected function getDirectoryStructure($baseDir)
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
                    'children' => [],
                ];
            } elseif (is_dir($fullPath)) {
                if (in_array($entry, $this->exclude)) {
                    continue;
                }
                $structure[$entry] = [
                    'type' => 'directory',
                    'path' => $relativePath,
                    'children' => [],
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

    public function loadChildren($relativePath)
    {
        $diskPath = $this->baseDir ? rtrim($this->baseDir, '/') . '/' . $relativePath : $relativePath;
        $children = $this->getDirectoryStructure($diskPath);

        // Navigate the nested structure to find the correct node
        // Use a local copy and reassign to ensure Livewire detects the change
        $parts = explode('/', $relativePath);
        $structure = $this->structure;
        $current = &$structure;

        foreach ($parts as $i => $part) {
            if (!isset($current[$part])) {
                return [];
            }
            if ($i < count($parts) - 1) {
                $current = &$current[$part]['children'];
            } else {
                $current = &$current[$part];
            }
        }

        $current['children'] = $children;
        $this->structure = $structure;

        // Return file paths so Alpine can batch-fetch contents
        $filePaths = [];
        foreach ($children as $child) {
            if ($child['type'] === 'file') {
                $filePaths[] = $child['path'];
            }
        }

        return $filePaths;
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
    <div x-data="directoryTree()" class="p-3">
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
        loadedDirs: {},
        files: {},
        pendingFetches: {},
        fetchFileContent(fullPath) {
            if (this.files[fullPath]) {
                return Promise.resolve(this.files[fullPath]);
            }
            if (this.pendingFetches[fullPath]) {
                return this.pendingFetches[fullPath];
            }
            this.pendingFetches[fullPath] = fetch('/file-content', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
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
                delete this.pendingFetches[fullPath];
            });
            return this.pendingFetches[fullPath];
        },
        fetchedDirectories: {},
        fetchFilesInDirectory(dirPath, files) {
            if (this.fetchedDirectories[dirPath]) {
                return;
            }
            const filesToFetch = files.filter(f =>
                !this.files[f] && !this.pendingFetches[f]
            );
            if (filesToFetch.length === 0) {
                this.fetchedDirectories[dirPath] = true;
                return;
            }
            filesToFetch.forEach(f => { this.pendingFetches[f] = true; });
            fetch('/batch-file-content', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
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
                filesToFetch.forEach(f => { delete this.pendingFetches[f]; });
                this.fetchedDirectories[dirPath] = true;
            });
        }
    }
}
</script>
