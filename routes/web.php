<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/katana/directory-children', function (Request $request) {
    $validated = $request->validate([
        'disk' => 'required|string',
        'baseDir' => 'nullable|string',
        'path' => 'required|string',
        'exclude' => 'nullable|array',
        'lazyDirs' => 'nullable|array',
        'level' => 'nullable|integer|min:0|max:50',
    ]);

    $disk = $validated['disk'];
    $baseDir = $validated['baseDir'] ?? '';
    $path = $validated['path'];
    $exclude = $validated['exclude'] ?? [];
    $lazyDirs = $validated['lazyDirs'] ?? ['node_modules', 'vendor'];
    $level = $validated['level'] ?? 1;

    // Validate disk is local-driver only
    $diskConfig = config("filesystems.disks.{$disk}");
    if (!$diskConfig || !in_array($diskConfig['driver'] ?? '', ['local'])) {
        return response()->json(['error' => 'Invalid disk'], 422);
    }

    $root = rtrim($diskConfig['root'] ?? '', '/');
    $diskPath = $baseDir ? rtrim($baseDir, '/') . '/' . ltrim($path, '/') : $path;
    $fullDir = $root . '/' . ltrim($diskPath, '/');

    // Security: block path traversal
    $realRoot = realpath($root);
    $realDir = realpath($fullDir);
    if (!$realRoot || !$realDir || !str_starts_with($realDir, $realRoot)) {
        return response()->json(['error' => 'Invalid path'], 422);
    }

    if (!is_dir($realDir)) {
        return response()->json(['error' => 'Not a directory'], 422);
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
            if (in_array($entry, $exclude)) {
                continue;
            }
            $isLazy = in_array($entry, $lazyDirs);
            $items[$entry] = [
                'type' => 'directory',
                'path' => $relativePath,
                'lazy' => $isLazy,
                'children' => [],
            ];
        } else {
            $items[$entry] = [
                'type' => 'file',
                'path' => $relativePath,
            ];
        }
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

    // Render each item via the Blade component
    $html = '';
    foreach ($items as $name => $item) {
        $html .= view('katana::katana.directory-tree-item', [
            'name' => $name,
            'item' => $item,
            'level' => $level,
        ])->render();
    }

    // Collect non-lazy child directories for prefetching
    $childDirs = [];
    foreach ($items as $name => $item) {
        if ($item['type'] === 'directory' && empty($item['symlink']) && empty($item['lazy'])) {
            $childDirs[] = $item['path'];
        }
    }

    return response()->json([
        'html' => $html,
        'childDirs' => $childDirs,
    ]);
})->middleware('web');
