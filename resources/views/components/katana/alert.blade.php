@props([
    'variant' => 'primary',
    'icon' => null,
    'title' => '',
    'description' => '',
])

@php
    $classes = \Illuminate\Support\Arr::toCssClasses([
        'relative text-sm w-full rounded-[var(--radius-medium)] border max-w-xl p-4 [&>svg]:absolute [&>svg]:left-4 [&>svg]:top-4 [&>svg+div]:translate-y-[-3px] [&:has(svg)]:pl-11 text-neutral-900',
        'text-stone-900 bg-white dark:bg-stone-900 dark:border-stone-800 dark:text-stone-100 border-stone-200' => $variant === 'primary',
        'text-gray-600 bg-stone-100 dark:bg-stone-700 dark:border-stone-600 dark:text-stone-100 border-stone-200' => $variant === 'secondary',
        'text-red-500 bg-white dark:bg-red-500 dark:border-red-400 dark:text-stone-100 border-red-200' => $variant === 'destructive',
        'text-blue-600 bg-white dark:bg-blue-500 dark:border-blue-400 dark:text-stone-100 border-blue-200' => $variant === 'info',
        'text-green-600 bg-white dark:bg-green-500 dark:border-green-400 dark:text-stone-100 border-green-300' => $variant === 'success',
        'text-amber-700 bg-white dark:bg-amber-600 dark:border-amber-500 dark:text-stone-100 border-amber-300' => $variant === 'warning',
    ]);
@endphp

<div {{ $attributes->twMerge($classes) }}>
    @if ($icon ?? false)
        <x-dynamic-component class="w-4 h-4" :component="$icon" />
    @endif
    <h5 class="font-medium leading-none tracking-tight">{{ $title }}</h5>
    @if ($description)
        <div class="mt-1 opacity-60 dark:opacity-80">{!! $description !!}</div>
    @endif
</div>
