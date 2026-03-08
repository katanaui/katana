@props([
    'type' => 'text',
    'label' => null
])

@php
    $classes = \Illuminate\Support\Arr::toCssClasses([
        'placeholder:text-stone-300 dark:placeholder:text-stone-600 sm:text-left text-center text-foreground bg-background selection:bg-primary selection:text-primary-foreground focus:ring-offset-2 focus:ring-offset-background dark:bg-input/30 border-input h-9 w-full min-w-0 rounded border px-3 py-1 text-base shadow-xs transition-[color,box-shadow] outline-none disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm focus-visible:border-ring focus-visible:ring-primary/5 focus-visible:ring-[3px] aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive',
        'border-red-500' => $errors->has('email')
    ]);
@endphp

<input
    type="{{ $type ?? 'text' }}"
    {{ $attributes->twMerge($classes) }}
/>
