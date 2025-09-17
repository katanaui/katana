@props([
    'title' => '',
    'open' => false, // default open state
])

@php
    $id = Str::uuid()->toString();
@endphp

<div 
    x-data="{ id: '{{ $id }}' }" 
    x-init="
        if (@js($open)) {
            if (activeAccordions !== undefined) {
                if (Array.isArray(activeAccordions)) {
                    activeAccordions.push(id)
                } else {
                    activeAccordions = id
                }
            }
        }
    "
    {{ $attributes->twMerge('group') }}
>
    <button 
        @click="toggle(id)" 
        {{ $attributes->twMergeFor('title', 'flex w-full items-center text-foreground/80 group-hover:text-foreground justify-between py-4 px-3 text-left select-none font-medium group-hover:underline') }}
    >
        <span>{{ $title }}</span>
        <span  {{ $attributes->twMergeFor('icon', 'w-4 h-4 duration-200 ease-out transform') }} :class="{ 'rotate-180': isOpen(id) }">
            @if($icon ?? false)
                {!! $icon !!}
            @else
                <x-phosphor-caret-down class="w-full h-full" />
            @endif
        </span>
    </button>
    <div 
        x-show="isOpen(id)" 
        x-collapse 
        x-cloak
        {{ $attributes->twMergeFor('content', 'px-3 pb-4 pt-0 text-sm text-foreground/70') }}
    >
        {{ $slot }}
    </div>
</div>