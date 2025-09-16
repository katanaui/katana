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
    class="group"
>
    <button 
        @click="toggle(id)" 
        class="flex w-full items-center justify-between py-4 px-3 text-left select-none font-medium"
    >
        <span>{{ $title }}</span>
        <span class="w-4 h-4 duration-200 ease-out transform" :class="{ 'rotate-180': isOpen(id) }">
            <x-phosphor-caret-down class="w-full h-full" />
        </span>
    </button>
    <div 
        x-show="isOpen(id)" 
        x-collapse 
        x-cloak
        class="px-3 pb-4 pt-0 text-sm text-foreground/70"
    >
        {{ $slot }}
    </div>
</div>