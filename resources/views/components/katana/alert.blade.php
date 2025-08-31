@props([
    'variant' => 'default',
    'icon' => null,
    'title' => '',
    'description' => '',
])

@php
    $classes = \Illuminate\Support\Arr::toCssClasses([
        'relative w-full rounded-lg border bg-white p-4 [&>svg]:absolute [&>svg]:text-foreground [&>svg]:left-4 [&>svg]:top-4 [&>svg+div]:translate-y-[-3px] [&:has(svg)]:pl-11 text-neutral-900',
        'text-gray-600 bg-gray-100' => (! $variant) || ($variant === 'secondary'),
        'text-danger-700 bg-danger-500/10' => $variant === 'destructive',
        'text-primary-700 bg-primary-500/10' => $variant === 'default',
        'text-stone-700 border border-stone-200' => $variant === 'outline'
    ]);
@endphp

<div {{ $attributes->twMerge($classes) }}>
    @if($icon ?? false)
        <x-dynamic-component class="w-4 h-4" :component="$icon" />
    @endif
    <h5 class="font-medium tracking-tight leading-none">{{ $title }}</h5>
    @if($description)
        <div class="mt-1 text-sm opacity-70">{!! $description !!}</div>
    @endif
</div>