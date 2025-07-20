@props([
    'type' => 'button', 
    'size' => 'md', 
    'variant' => 'primary', 
    'href' => null
])

@php
switch ($size ?? 'md') {
    case 'xs':
        $sizeClasses = 'px-2 py-1 leading-4 text-xs';
        break;
    case 'sm':
        $sizeClasses = 'px-3 py-1.5 leading-4 text-xs';
        break;
    case 'md':
        $sizeClasses = 'px-4 py-2 leading-4 text-xs';
        break;
    case 'lg':
        $sizeClasses = 'px-4 py-2.5 leading-5 text-sm';
        break;
    case 'xl':
        $sizeClasses = 'px-5 py-2.5 leading-6 text-base';
        break;
    case '2xl':
        $sizeClasses = 'px-6 py-3 leading-6 text-base';
        break;
    case '3xl':
        $sizeClasses = 'px-6 py-3.5 leading-7 text-lg';
        break;
}
@endphp

@php
$primaryTypeClasses = 'border-transparent focus:outline-none inset-shadow-xs bg-stone-900 text-stone-100 hover:text-white inset-shadow-white/20';
switch ($variant ?? 'primary') {
    case 'primary':
        $typeClasses = $primaryTypeClasses;
        break;
    case 'secondary':
        $typeClasses = 'border-transparent text-stone-700 bg-stone-100';
        break;
    case 'destructive':
        $typeClasses = 'border-transparent text-white/90 focus:outline-none bg-red-600 hover:text-white';
        break;
    case 'outline':
        $typeClasses = 'border-transparent text-stone-700 hover:bg-stone-100 border-stone-200';
        break;
    case 'ghost':
        $typeClasses = 'border-transparent text-stone-700 hover:bg-stone-100';
        break;
    case 'link':
        $typeClasses = 'border-transparent text-stone-700 hover:bg-stone-100';
        break;
    default:
        $typeClasses = $primaryTypeClasses;
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

<{!! $typeAttr !!} {{ $attributes->twMerge($sizeClasses . ' ' . $typeClasses . ' cursor-pointer border inline-flex rounded-xl items-center w-full justify-center items-center font-medium focus:outline-none ease-out duration-300') }}>
    {{ $slot }}
</{{ $typeClose }}>
