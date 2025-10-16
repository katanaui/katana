@props([
    'position' => 'bottom',
    'align' => 'left',
    'gap' => '2',
])

<div x-data="{
    popoverOpen: false,
}" @class([
    'relative w-auto items-start inline-flex',
    'flex-col' => $position === 'bottom' || $position === 'top',
    'flex-row' => $position === 'right' || $position === 'left',
])>

    <div x-on:click="popoverOpen=true">
        @if ($trigger ?? false)
            {!! $trigger !!}
        @else
            <div class="relative inline-flex items-center justify-center rounded-md border bg-white p-2 text-sm font-medium text-neutral-700 transition-colors hover:bg-neutral-100 focus:bg-white focus:outline-none active:bg-white disabled:pointer-events-none disabled:opacity-50">
                button
            </div>
        @endif
    </div>

    <div class="relative w-full max-w-md">
        <div x-show="popoverOpen" x-on:click.away="popoverOpen=false" x-transition:enter="ease-out duration-200" x-transition:enter-start="-translate-y-2" x-transition:enter-end="translate-y-0" x-cloak @class([
            'absolute top-0 z-50 w-auto',
            'left-0' => $align === 'left',
            'right-0' => $align === 'right',
            'left-1/2 -translate-x-1/2' => $align === 'center',
            'bottom-0' => $position === 'bottom',
            'top-0' => $position === 'top',
        ])>
            {{ $slot }}
        </div>
    </div>
</div>
