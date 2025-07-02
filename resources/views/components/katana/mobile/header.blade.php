@props([
    'title' => '',
    'leftIcon' => '',
    'leftUrl' => '',
    'rightIcon' => '',
    'rightUrl' => '',
    'bottomBorder' => true
])

<div class="flex relative z-50 justify-start items-center px-3 w-full h-16 @if($bottomBorder ?? true) border-b border-gray-200 @endif">
    @if($leftIcon && $leftUrl)
        <a href="{{ $leftUrl ?? '' }}" class="relative z-10 p-2">
            <x-dynamic-component :component="$leftIcon" class="w-5 h-5" />
        </a>
    @endif
    @if($leftSlot ?? false)
        {{ $leftSlot }}
    @endif
    <h1 class="flex absolute inset-0 z-0 justify-center items-center w-full text-lg font-semibold text-gray-900">{{ $title ?? '' }}</h1>
    @if($rightSlot ?? false)
        {{ $rightSlot }}
    @endif
    @if($rightIcon && $rightUrl)
        <a href="{{ $rightUrl ?? '' }}" class="relative z-10 p-2">
            <x-dynamic-component :component="$rightIcon" class="w-5 h-5" />
        </a>
    @endif
</div>