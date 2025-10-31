@props([
    'title' => '',
    'leftIcon' => '',
    'leftUrl' => '',
    'rightIcon' => '',
    'rightUrl' => '',
    'bottomBorder' => true
])

@php
    $classes = \Illuminate\Support\Arr::toCssClasses([
        'flex relative z-50 justify-between items-center text-gray-900 px-3 w-full h-16 shrink-0',
        'border-b border-gray-200' => $bottomBorder
    ]);
@endphp

<div {{ $attributes->twMerge($classes) }}>
    @if($leftIcon && $leftUrl)
        <a href="{{ $leftUrl ?? '' }}" class="relative z-10 p-2">
            <x-dynamic-component :component="$leftIcon" class="w-5 h-5" />
        </a>
    @endif
    @if($leftSlot ?? false)
        {{ $leftSlot }}
    @endif
    <h1 class="flex absolute inset-0 z-0 justify-center items-center w-full text-lg font-semibold">{{ $title ?? '' }}</h1>
    @if($rightSlot ?? false)
        {{ $rightSlot }}
    @endif
    @if($rightIcon && $rightUrl)
        <a href="{{ $rightUrl ?? '' }}" class="relative z-10 p-2">
            <x-dynamic-component :component="$rightIcon" class="w-5 h-5" />
        </a>
    @endif
</div>
