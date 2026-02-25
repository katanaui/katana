@props(['disk' => 'local', 'baseDir' => '', 'exclude' => [], 'lazyDirs' => ['node_modules', 'vendor']])

<section class="w-full h-full">
    <livewire:directory-tree :disk="$disk" :base-dir="$baseDir" :exclude="$exclude" :lazy-dirs="$lazyDirs" />
</section>
