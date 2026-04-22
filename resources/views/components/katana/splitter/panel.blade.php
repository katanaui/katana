@props(['size' => null])

<div
    @if($size)
        data-size="{{ $size }}"
    @endif
    class="w-full h-full min-w-0 min-h-0 katana-split-pane"
    wire:ignore.self
>
    {{ $slot }}
</div>