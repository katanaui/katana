@props([
    'variant' => 'default',
    'icon' => null,
    'iconPosition' => 'before',
    'size' => 'md',
])

@php
    $classes = \Illuminate\Support\Arr::toCssClasses([
        'inline-flex items-center justify-center tracking-tight rounded-[var(--radius)] text-xs [&>svg]:w-4 [&>svg]:h-4 px-2 py-0.5',
        'text-gray-600 bg-gray-100' => (! $variant) || ($variant === 'secondary'),
        'text-danger-700 bg-danger-500/10' => $variant === 'destructive',
        'text-primary-foreground bg-primary' => $variant === 'default',
        'text-stone-700 border border-stone-200' => $variant === 'outline',
        '[&>svg]:-ml-1 [&>svg]:mr-1' => $iconPosition === 'before',
        '[&>svg]:-mr-1 [&>svg]:ml-1' => $iconPosition === 'after',
    ]);
@endphp

<span {{ $attributes->twMerge($classes) }}>
    @if ($icon && $iconPosition === 'before')
        <x-dynamic-component :component="$icon" />
    @endif

    <span>{{ $slot }}</span>

    @if ($icon && $iconPosition === 'after')
        <x-dynamic-component :component="$icon" />
    @endif
</span>
