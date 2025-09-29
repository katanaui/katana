@props(['size' => null])

<div
    @if($size)
        data-size="{{ $size }}"
    @endif
    class="w-full h-full katana-split-pane"
    wire:ignore.self
>
    {{ $slot }}
</div>