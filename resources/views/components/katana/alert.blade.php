@props([
    'variant' => 'primary',
    'icon' => null,
    'title' => '',
    'description' => '',
])

@php
    $classes = \Illuminate\Support\Arr::toCssClasses([
        'relative text-sm w-full rounded-lg border max-w-xl bg-white p-4 [&>svg]:absolute [&>svg]:left-4 [&>svg]:top-4 [&>svg+div]:translate-y-[-3px] [&:has(svg)]:pl-11 text-neutral-900',
        'text-stone-900 border-stone-200' => $variant === 'primary',
        'text-gray-600 bg-gray-100 border-stone-100' => $variant === 'secondary',
        'text-red-500 border-red-200' => $variant === 'destructive',
        'text-blue-600 border-blue-200' => $variant === 'info',
        'text-green-600 border-green-300' => $variant === 'success',
        'text-amber-600 border-amber-300' => $variant === 'warning',
    ]);
@endphp

<div {{ $attributes->twMerge($classes) }}>
    @if($icon ?? false)
        <x-dynamic-component class="w-4 h-4" :component="$icon" />
    @endif
    <h5 class="font-medium leading-none tracking-tight">{{ $title }}</h5>
    @if($description)
        <div class="mt-1 opacity-60">{!! $description !!}</div>
    @endif
</div>