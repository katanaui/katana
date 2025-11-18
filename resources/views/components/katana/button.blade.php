@props([
    'type' => 'button',
    'size' => 'md',
    'variant' => 'primary',
    'href' => null,
    'loader' => false,
    'loading' => false,
])

@php
    switch ($size ?? 'md') {
        case 'xs':
            $sizeClasses = 'px-2.5 py-1 leading-4 text-[11px]';
            $loaderClasses = 'size-3';
            break;
        case 'sm':
            $sizeClasses = 'px-3 py-1.5 leading-4 text-xs';
            $loaderClasses = 'size-3';
            break;
        case 'md':
            $sizeClasses = 'px-3.5 py-2 leading-4 text-xs';
            $loaderClasses = 'size-3.5';
            break;
        case 'lg':
            $sizeClasses = 'px-4 py-2.5 leading-5 text-sm';
            $loaderClasses = 'size-4';
            break;
        case 'xl':
            $sizeClasses = 'px-5 py-2.5 leading-6 text-base';
            $loaderClasses = 'size-4';
            break;
        case '2xl':
            $sizeClasses = 'px-6 py-3 leading-6 text-base';
            $loaderClasses = 'size-5';
            break;
        case '3xl':
            $sizeClasses = 'px-6 py-3.5 leading-7 text-lg';
            $loaderClasses = 'size-5';
            break;
        default:
            $sizeClasses = 'px-3.5 py-2 leading-4 text-xs';
            $loaderClasses = 'size-3.5';
            break;
    }
@endphp

@php
    $topHighlight = ' inset-shadow-xs inset-shadow-white/20';
    $defaultClasses = 'border-transparent no-underline focus:outline-none bg-primary text-primary-foreground select-none';
    switch ($variant ?? 'primary') {
        case 'primary':
            $typeClasses = $defaultClasses . $topHighlight;
            break;
        case 'secondary':
            $typeClasses = 'border-transparent no-underline text-secondary-foreground bg-secondary';
            break;
        case 'destructive':
            $typeClasses = 'border-transparent no-underline focus:outline-none bg-red-600 text-white' . $topHighlight;
            break;
        case 'outline':
            $typeClasses = 'border-transparent no-underline text-foreground hover:bg-secondary border-foreground/10';
            break;
        case 'ghost':
            $typeClasses = 'border-transparent no-underline text-foreground hover:bg-secondary';
            break;
        case 'link':
            $typeClasses = 'border-transparent no-underline text-foreground hover:underline';
            break;
        default:
            $typeClasses = $defaultClasses;
            break;
    }
@endphp

@php
    switch ($type ?? 'button') {
        case 'button':
            $typeAttr = 'button type="button"';
            $typeClose = 'button';
            break;
        case 'submit':
            $typeAttr = 'button type="submit"';
            $typeClose = 'button';
            break;
        case 'a':
            $link = $href ?? '';
            $typeAttr = 'a  href="' . $link . '"';
            $typeClose = 'a';
            break;
        default:
            $typeAttr = 'button type="button"';
            $typeClose = 'button';
            break;
    }
@endphp

<{!! $typeAttr !!} {{ $attributes->twMerge($sizeClasses . ' ' . $typeClasses . ' cursor-pointer border inline-flex rounded-[var(--radius)] items-center justify-center items-center font-medium focus:outline-none') }}>
    @if ($loading ?? false)
        <span class="flex absolute justify-center items-center w-full h-full"><svg xmlns="http://www.w3.org/2000/svg" class="{{ $loaderClasses }} animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg></span>
    @endif
    @if ($loader ?? false)
        <span class="flex absolute justify-center items-center w-full h-full" wire:loading.flex wire:target="{{ $attributes->get('wire:click') }}"><svg xmlns="http://www.w3.org/2000/svg" class="{{ $loaderClasses }} animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg></span>
    @endif
    <span @class(['flex items-center', 'opacity-0' => $loading]) wire:loading.class="opacity-0" wire:target="{{ $attributes->get('wire:click') }}">{{ $slot }}</span>
</{{ $typeClose }}>
