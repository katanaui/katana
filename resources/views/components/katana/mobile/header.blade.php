@props([
    'title' => '',
    'leftIcon' => '',
    'leftUrl' => '',
    'rightIcon' => '',
    'rightUrl' => '',
    'bottomBorder' => true,
])

@php
<<<<<<< HEAD
    $classes = \Illuminate\Support\Arr::toCssClasses(['flex relative z-50 justify-between items-center text-gray-900 px-3 w-full h-16 shrink-0', 'border-b border-gray-200' => $bottomBorder]);
=======
    $classes = \Illuminate\Support\Arr::toCssClasses([
        'flex relative z-50 justify-between items-center text-gray-900 px-3 w-full h-16 shrink-0',
        'border-b border-gray-200' => $bottomBorder
    ]);
>>>>>>> ce93a45476b2d817eb5e85b2232dd61263b5867d
@endphp

<div {{ $attributes->twMerge($classes) }}>
    @if ($leftIcon && $leftUrl)
        <a class="relative z-10 p-2" href="{{ $leftUrl ?? '' }}">
            <x-dynamic-component class="h-5 w-5" :component="$leftIcon" />
        </a>
    @endif
    @if ($leftSlot ?? false)
        {{ $leftSlot }}
    @endif
    <h1 class="absolute inset-0 z-0 flex w-full items-center justify-center text-lg font-semibold">{{ $title ?? '' }}</h1>
    @if ($rightSlot ?? false)
        {{ $rightSlot }}
    @endif
    @if ($rightIcon && $rightUrl)
        <a class="relative z-10 p-2" href="{{ $rightUrl ?? '' }}">
            <x-dynamic-component class="h-5 w-5" :component="$rightIcon" />
        </a>
    @endif
</div>
