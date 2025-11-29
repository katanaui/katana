@props([
    'orientation' => 'horizontal',
])
<div {{  $attributes->twMerge('control-group  
            w-full flex relative items-stretch justify-stretch
            data-[orientation=horizontal]:[&>*]:h-full
            [&>*]:border 
            [&>*]:-ml-px 
            [&>*:first-child]:ml-0 
            [&>*:not(:first-child):not(:last-child)]:rounded-none 
            [&>*:first-child]:rounded-l
            [&>*:first-child]:rounded-r-none 
            [&>*:last-child]:rounded-r
            [&>*:last-child]:rounded-l-none') }}
            data-orientation="{{ $orientation }}">

            {{  $slot }}
            
</div>