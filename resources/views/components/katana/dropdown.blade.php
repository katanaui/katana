@props([
    'position' => 'bottom',
    'align' => 'left',
    'gap' => '2'
])

<div x-data="{
    dropdownOpen: false,
}"
@class([
    'relative w-auto items-start inline-flex',
    'flex-col' => $position === 'bottom' || $position === 'top',
    'flex-row' => $position === 'right' || $position === 'left',
])>

    <div x-on:click="dropdownOpen=true">
        @if($trigger ?? false)
            {!! $trigger !!}
        @else
            <div class="inline-flex relative justify-center items-center p-2 text-sm font-medium bg-white rounded-md border transition-colors text-neutral-700 hover:bg-neutral-100 active:bg-white focus:bg-white focus:outline-none disabled:opacity-50 disabled:pointer-events-none">
                button
            </div>
        @endif
    </div>
    
    <div class="relative w-full max-w-md">
    <div x-show="dropdownOpen" 
        x-on:click.away="dropdownOpen=false"
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="-translate-y-2"
        x-transition:enter-end="translate-y-0"
        @class([
            'absolute top-0 z-50 w-auto',
            'left-0' => $align === 'left',
            'right-0' => $align === 'right',
            'left-1/2 -translate-x-1/2' => $align === 'center',
            'bottom-0' => $position === 'bottom',
            'top-0' => $position === 'top'
        ])
        x-cloak>
        {{ $slot }}
    </div>
</div>
</div>
