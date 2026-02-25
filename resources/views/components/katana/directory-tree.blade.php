@props(['disk' => 'local', 'baseDir' => '', 'exclude' => [], 'lazyDirs' => ['node_modules', 'vendor'], 'showToolbar' => true])

<section class="w-full h-full">
    <livewire:directory-tree :disk="$disk" :base-dir="$baseDir" :exclude="$exclude" :lazy-dirs="$lazyDirs" :show-toolbar="$showToolbar" />
</section>
