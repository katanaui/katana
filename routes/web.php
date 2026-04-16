<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Encryption\DecryptException;

function validateWriteToken(Request $request): bool
{
    $token = $request->input('_write_token');
    if (!$token) {
        return false;
    }

    try {
        $payload = json_decode(Crypt::decryptString($token), true);
    } catch (DecryptException $e) {
        return false;
    }

    if (!is_array($payload) || empty($payload['writable'])) {
        return false;
    }

    // Verify the token is scoped to the same disk + baseDir
    if (($payload['disk'] ?? '') !== ($request->input('disk') ?? '')) {
        return false;
    }
    if (($payload['baseDir'] ?? '') !== ($request->input('baseDir') ?? '')) {
        return false;
    }

    return true;
}

/**
 * Normalize a user-supplied relative path. Strips leading/trailing slashes,
 * collapses duplicate separators, and rejects any `.` or `..` segment.
 * Returns the cleaned path (possibly empty) or false if traversal was attempted.
 */
function katanaNormalizeDiskPath(string $path): string|false
{
    $path = str_replace('\\', '/', $path);
    $segments = [];

    foreach (explode('/', $path) as $segment) {
        if ($segment === '' || $segment === '.') {
            continue;
        }
        if ($segment === '..') {
            return false;
        }
        $segments[] = $segment;
    }

    return implode('/', $segments);
}

function katanaJoinDiskPath(string $baseDir, string $relative): string|false
{
    $base = katanaNormalizeDiskPath($baseDir);
    $rel = katanaNormalizeDiskPath($relative);

    if ($base === false || $rel === false) {
        return false;
    }

    if ($base === '' && $rel === '') return '';
    if ($base === '') return $rel;
    if ($rel === '') return $base;
    return $base.'/'.$rel;
}

function katanaIsLocalDisk(string $disk): bool
{
    return (config("filesystems.disks.{$disk}.driver") ?? '') === 'local';
}

Route::post('/katana/directory-children', function (Request $request) {
    $validated = $request->validate([
        'disk' => 'required|string',
        'baseDir' => 'nullable|string',
        'path' => 'nullable|string',
        'exclude' => 'nullable|array',
        'lazyDirs' => 'nullable|array',
        'level' => 'nullable|integer|min:0|max:50',
        'animateCollapse' => 'nullable|boolean',
    ]);

    $disk = $validated['disk'];
    $baseDir = $validated['baseDir'] ?? '';
    $path = $validated['path'] ?? '';
    $exclude = $validated['exclude'] ?? [];
    $lazyDirs = $validated['lazyDirs'] ?? ['node_modules', 'vendor'];
    $level = $validated['level'] ?? 1;
    $animateCollapse = $validated['animateCollapse'] ?? false;

    $diskConfig = config("filesystems.disks.{$disk}");
    if (!$diskConfig) {
        return response()->json(['error' => 'Invalid disk'], 422);
    }

    $items = katanaIsLocalDisk($disk)
        ? katanaListChildrenLocal($diskConfig, $baseDir, $path, $exclude, $lazyDirs)
        : katanaListChildrenViaStorage($disk, $baseDir, $path, $exclude, $lazyDirs);

    if ($items === false) {
        return response()->json(['error' => 'Invalid path'], 422);
    }

    // Sort: directories first, then alphabetical
    uksort($items, function ($a, $b) use ($items) {
        $aIsDir = $items[$a]['type'] === 'directory';
        $bIsDir = $items[$b]['type'] === 'directory';
        if ($aIsDir !== $bIsDir) {
            return $aIsDir ? -1 : 1;
        }
        return strcasecmp($a, $b);
    });

    $html = '';
    foreach ($items as $name => $item) {
        $html .= view('katana::katana.directory-tree-item', [
            'name' => $name,
            'item' => $item,
            'level' => $level,
            'readonly' => $request->boolean('readonly', false),
            'animateCollapse' => $animateCollapse,
        ])->render();
    }

    $childDirs = [];
    foreach ($items as $item) {
        if ($item['type'] === 'directory' && empty($item['symlink']) && empty($item['lazy'])) {
            $childDirs[] = $item['path'];
        }
    }

    return response()->json([
        'html' => $html,
        'childDirs' => $childDirs,
    ]);
})->middleware('web');

function katanaListChildrenLocal(array $diskConfig, string $baseDir, string $path, array $exclude, array $lazyDirs): array|false
{
    $root = rtrim($diskConfig['root'] ?? '', '/');
    $diskPath = $baseDir ? rtrim($baseDir, '/') . '/' . ltrim($path, '/') : $path;
    $fullDir = $root . '/' . ltrim($diskPath, '/');

    $realRoot = realpath($root);
    $realDir = realpath($fullDir);
    if (!$realRoot || !$realDir || !str_starts_with($realDir, $realRoot)) {
        return false;
    }

    if (!is_dir($realDir)) {
        return false;
    }

    $entries = scandir($realDir);
    $baseDirPrefix = rtrim($baseDir, '/');
    $items = [];

    foreach ($entries as $entry) {
        if ($entry === '.' || $entry === '..' || $entry[0] === '.') {
            continue;
        }

        $entryFullPath = $realDir . '/' . $entry;
        $entryDiskPath = $diskPath ? rtrim($diskPath, '/') . '/' . $entry : $entry;
        $relativePath = $baseDirPrefix !== ''
            ? ltrim(substr($entryDiskPath, strlen($baseDirPrefix)), '/')
            : ltrim($entryDiskPath, '/');

        if (is_link($entryFullPath)) {
            $items[$entry] = [
                'type' => 'directory',
                'path' => $relativePath,
                'symlink' => true,
                'lazy' => false,
                'children' => [],
            ];
        } elseif (is_dir($entryFullPath)) {
            if (in_array($entry, $exclude)) continue;
            $items[$entry] = [
                'type' => 'directory',
                'path' => $relativePath,
                'lazy' => in_array($entry, $lazyDirs),
                'children' => [],
            ];
        } else {
            $items[$entry] = [
                'type' => 'file',
                'path' => $relativePath,
            ];
        }
    }

    return $items;
}

function katanaListChildrenViaStorage(string $diskName, string $baseDir, string $path, array $exclude, array $lazyDirs): array|false
{
    $diskPath = katanaJoinDiskPath($baseDir, $path);
    if ($diskPath === false) {
        return false;
    }

    $baseDirPrefix = rtrim($baseDir, '/');
    $disk = Storage::disk($diskName);
    $items = [];

    foreach ($disk->directories($diskPath) as $dirKey) {
        $entry = basename($dirKey);
        if ($entry === '' || $entry[0] === '.' || in_array($entry, $exclude)) {
            continue;
        }

        $relativePath = $baseDirPrefix !== ''
            ? ltrim(substr($dirKey, strlen($baseDirPrefix)), '/')
            : ltrim($dirKey, '/');

        $items[$entry] = [
            'type' => 'directory',
            'path' => $relativePath,
            'lazy' => in_array($entry, $lazyDirs),
            'children' => [],
        ];
    }

    foreach ($disk->files($diskPath) as $fileKey) {
        $entry = basename($fileKey);
        if ($entry === '' || $entry[0] === '.') {
            continue;
        }

        $relativePath = $baseDirPrefix !== ''
            ? ltrim(substr($fileKey, strlen($baseDirPrefix)), '/')
            : ltrim($fileKey, '/');

        $items[$entry] = [
            'type' => 'file',
            'path' => $relativePath,
        ];
    }

    return $items;
}

Route::post('/katana/directory-create-file', function (Request $request) {
    $validated = $request->validate([
        'disk' => 'required|string',
        'baseDir' => 'nullable|string',
        'parentPath' => 'nullable|string',
        'name' => 'required|string|max:255',
        '_write_token' => 'required|string',
    ]);

    if (!validateWriteToken($request)) {
        return response()->json(['error' => 'Forbidden'], 403);
    }

    $disk = $validated['disk'];
    $baseDir = $validated['baseDir'] ?? '';
    $parentPath = $validated['parentPath'] ?? '';
    $name = $validated['name'];

    if (str_contains($name, '/') || str_contains($name, '\\') || $name === '.' || $name === '..') {
        return response()->json(['error' => 'Invalid filename'], 422);
    }

    $diskConfig = config("filesystems.disks.{$disk}");
    if (!$diskConfig) {
        return response()->json(['error' => 'Invalid disk'], 422);
    }

    $relativePath = $parentPath ? rtrim($parentPath, '/') . '/' . $name : $name;

    if (katanaIsLocalDisk($disk)) {
        $root = rtrim($diskConfig['root'] ?? '', '/');
        $parentDiskPath = $baseDir
            ? ($parentPath ? rtrim($baseDir, '/') . '/' . ltrim($parentPath, '/') : $baseDir)
            : $parentPath;
        $parentFullPath = $root . ($parentDiskPath ? '/' . ltrim($parentDiskPath, '/') : '');

        $realRoot = realpath($root);
        $realParent = realpath($parentFullPath);
        if (!$realRoot || !$realParent || !str_starts_with($realParent, $realRoot)) {
            return response()->json(['error' => 'Invalid path'], 422);
        }

        if (!is_dir($realParent)) {
            return response()->json(['error' => 'Parent directory does not exist'], 422);
        }

        $targetFullPath = $realParent . '/' . $name;
        if (file_exists($targetFullPath)) {
            return response()->json(['error' => 'A file or folder with that name already exists'], 422);
        }

        file_put_contents($targetFullPath, '');
    } else {
        $diskPath = katanaJoinDiskPath($baseDir, $relativePath);
        if ($diskPath === false) {
            return response()->json(['error' => 'Invalid path'], 422);
        }

        $storage = Storage::disk($disk);
        if ($storage->exists($diskPath)) {
            return response()->json(['error' => 'A file or folder with that name already exists'], 422);
        }

        $storage->put($diskPath, '', ['visibility' => 'public', 'CacheControl' => 'no-cache, max-age=0']);
    }

    return response()->json(['success' => true, 'path' => $relativePath]);
})->middleware('web');

Route::post('/katana/directory-create-folder', function (Request $request) {
    $validated = $request->validate([
        'disk' => 'required|string',
        'baseDir' => 'nullable|string',
        'parentPath' => 'nullable|string',
        'name' => 'required|string|max:255',
        '_write_token' => 'required|string',
    ]);

    if (!validateWriteToken($request)) {
        return response()->json(['error' => 'Forbidden'], 403);
    }

    $disk = $validated['disk'];
    $baseDir = $validated['baseDir'] ?? '';
    $parentPath = $validated['parentPath'] ?? '';
    $name = $validated['name'];

    if (str_contains($name, '/') || str_contains($name, '\\') || $name === '.' || $name === '..') {
        return response()->json(['error' => 'Invalid folder name'], 422);
    }

    $diskConfig = config("filesystems.disks.{$disk}");
    if (!$diskConfig) {
        return response()->json(['error' => 'Invalid disk'], 422);
    }

    $relativePath = $parentPath ? rtrim($parentPath, '/') . '/' . $name : $name;

    if (katanaIsLocalDisk($disk)) {
        $root = rtrim($diskConfig['root'] ?? '', '/');
        $parentDiskPath = $baseDir
            ? ($parentPath ? rtrim($baseDir, '/') . '/' . ltrim($parentPath, '/') : $baseDir)
            : $parentPath;
        $parentFullPath = $root . ($parentDiskPath ? '/' . ltrim($parentDiskPath, '/') : '');

        $realRoot = realpath($root);
        $realParent = realpath($parentFullPath);
        if (!$realRoot || !$realParent || !str_starts_with($realParent, $realRoot)) {
            return response()->json(['error' => 'Invalid path'], 422);
        }

        if (!is_dir($realParent)) {
            return response()->json(['error' => 'Parent directory does not exist'], 422);
        }

        $targetFullPath = $realParent . '/' . $name;
        if (file_exists($targetFullPath)) {
            return response()->json(['error' => 'A file or folder with that name already exists'], 422);
        }

        mkdir($targetFullPath, 0755);
    } else {
        $diskPath = katanaJoinDiskPath($baseDir, $relativePath);
        if ($diskPath === false) {
            return response()->json(['error' => 'Invalid path'], 422);
        }

        $storage = Storage::disk($disk);
        // Blob storage has no real directories; persist an empty .gitkeep marker.
        $marker = $diskPath.'/.gitkeep';
        if ($storage->exists($marker) || in_array($diskPath, $storage->directories(dirname($diskPath) ?: ''), true)) {
            return response()->json(['error' => 'A file or folder with that name already exists'], 422);
        }

        $storage->put($marker, '', ['visibility' => 'public', 'CacheControl' => 'no-cache, max-age=0']);
    }

    return response()->json(['success' => true, 'path' => $relativePath]);
})->middleware('web');

Route::post('/katana/directory-delete', function (Request $request) {
    $validated = $request->validate([
        'disk' => 'required|string',
        'baseDir' => 'nullable|string',
        'path' => 'required|string',
        'type' => 'required|string|in:file,directory',
        '_write_token' => 'required|string',
    ]);

    if (!validateWriteToken($request)) {
        return response()->json(['error' => 'Forbidden'], 403);
    }

    $disk = $validated['disk'];
    $baseDir = $validated['baseDir'] ?? '';
    $path = $validated['path'];
    $type = $validated['type'];

    $diskConfig = config("filesystems.disks.{$disk}");
    if (!$diskConfig) {
        return response()->json(['error' => 'Invalid disk'], 422);
    }

    if (katanaIsLocalDisk($disk)) {
        $root = rtrim($diskConfig['root'] ?? '', '/');
        $diskPath = $baseDir ? rtrim($baseDir, '/') . '/' . ltrim($path, '/') : $path;
        $fullPath = $root . '/' . ltrim($diskPath, '/');

        $realRoot = realpath($root);
        $realPath = realpath($fullPath);
        if (!$realRoot || !$realPath || !str_starts_with($realPath, $realRoot)) {
            return response()->json(['error' => 'Invalid path'], 422);
        }

        if ($realPath === $realRoot) {
            return response()->json(['error' => 'Cannot delete root directory'], 422);
        }

        if ($type === 'file') {
            if (!is_file($realPath)) {
                return response()->json(['error' => 'File not found'], 404);
            }
            unlink($realPath);
        } else {
            if (!is_dir($realPath)) {
                return response()->json(['error' => 'Directory not found'], 404);
            }
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($realPath, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($iterator as $item) {
                if ($item->isDir()) {
                    rmdir($item->getPathname());
                } else {
                    unlink($item->getPathname());
                }
            }
            rmdir($realPath);
        }
    } else {
        $diskPath = katanaJoinDiskPath($baseDir, $path);
        if ($diskPath === false || $diskPath === '') {
            return response()->json(['error' => 'Invalid path'], 422);
        }

        $storage = Storage::disk($disk);

        if ($type === 'file') {
            if (!$storage->exists($diskPath)) {
                return response()->json(['error' => 'File not found'], 404);
            }
            $storage->delete($diskPath);
        } else {
            // deleteDirectory removes the prefix and everything under it,
            // including the .gitkeep marker used to represent the folder.
            $storage->deleteDirectory($diskPath);
        }
    }

    return response()->json(['success' => true, 'path' => $path]);
})->middleware('web');
