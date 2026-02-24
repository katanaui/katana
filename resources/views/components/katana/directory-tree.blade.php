@props(['disk' => 'local', 'baseDir' => '', 'exclude' => []])

<section class="w-full h-full">
    <livewire:directory-tree :disk="$disk" :base-dir="$baseDir" :exclude="$exclude" />
</section>
