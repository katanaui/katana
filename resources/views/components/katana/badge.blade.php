@props([
    'variant' => 'primary',
    'icon' => null,
    'size' => 'sm',
    'iconPosition' => 'before',
])

@php
    $classes = \Illuminate\Support\Arr::toCssClasses([
        'inline-flex items-center justify-center tracking-tight rounded-[var(--radius)] text-xs',
        'px-2 py-0.5 [&>svg]:w-3 [&>svg]:h-3' => $size == 'sm',
        /* Size variants */
        'px-3 py-1 [&>svg]:w-3.5 [&>svg]:h-3.5' => $size == 'md',
        'px-4 py-1.5 [&>svg]:w-4 [&>svg]:h-4' => $size == 'lg',
        /* Color variants */
        'text-primary-foreground bg-primary' => $variant === 'primary',
        'text-secondary-foreground bg-secondary' => !$variant || $variant === 'secondary',
        'text-white bg-destructive' => $variant === 'destructive',
        'text-foreground border border-input/90' => $variant === 'outline',
        'text-white bg-blue-600' => $variant === 'info',
        'text-white bg-green-600' => $variant === 'success',
        'text-white bg-yellow-500' => $variant === 'warning',
        /* Icon positioning */
        '[&>svg]:-ml-0.5 [&>svg]:mr-1' => $iconPosition === 'before',
        '[&>svg]:-ml-1 [&>svg]:mr-1' => $iconPosition === 'before' && $size == 'md',
        '[&>svg]:-ml-1.5 [&>svg]:mr-1' => $iconPosition === 'before' && $size == 'lg',
        '[&>svg]:-mr-0.5 [&>svg]:ml-1' => $iconPosition === 'after',
        '[&>svg]:-mr-1 [&>svg]:ml-1' => $iconPosition === 'after' && $size == 'md',
        '[&>svg]:-mr-1.5 [&>svg]:ml-1' => $iconPosition === 'after' && $size == 'lg',
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
