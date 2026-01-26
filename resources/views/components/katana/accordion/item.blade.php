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
    {{ $attributes->twMerge('group/accordion-item') }}
>
    <button 
        @click="toggle(id)" 
        {{ $attributes->twMergeFor('title', 'flex w-full items-center text-foreground/80 group-hover/accordion-item:text-foreground justify-between py-4 px-3 text-left select-none font-medium group-hover/accordion-item:underline') }}
    >
        <span>{{ $title }}</span>
        <span  {{ $attributes->twMergeFor('icon', 'w-3.5 h-3.5 duration-200 ease-out transform') }} :class="{ 'rotate-180': isOpen(id) }">
            @if($icon ?? false)
                {!! $icon !!}
            @else
                <svg class="w-full h-full" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256"><path fill="none" d="M0 0h256v256H0z"/><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="24" d="m208 96-80 80-80-80"/></svg>
            @endif
        </span>
    </button>
    <div
        x-show="isOpen(id)"
        x-collapse
        x-cloak
    >
        <div {{ $attributes->twMergeFor('content', 'px-3 pb-4 pt-0 text-sm text-foreground/70') }}>
            {{ $slot }}
        </div>
    </div>
</div>