@props([
    'size' => 'md',
])

@php
    switch ($size ?? 'md') {
        case 'sm':
            $sizeClasses = 'rounded p-4 text-sm';
            break;
        case 'md':
            $sizeClasses = 'rounded-medium p-5 text-sm';
            break;
        case 'lg':
            $sizeClasses = 'rounded-large p-6 text-base';
            break;
        default:
            $sizeClasses = 'rounded-medium p-5 text-sm';
            break;
    }
@endphp

<div {{ $attributes->twMerge( $sizeClasses . ' bg-background dark:bg-secondary/50 border border-foreground/10 dark:border-foreground/12 shadow-xs text-foreground/50 w-full h-full flex items-center justify-center') }}>
    {{ $slot }}
</div>