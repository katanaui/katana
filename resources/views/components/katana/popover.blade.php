@props([
    'position' => 'bottom',
    'align' => 'left',
    'gap' => '2',
])

<div x-data="{
    popoverOpen: false,
    triggerPosition: { top: 0, left: 0, width: 0, height: 0 },
    
    calculateTriggerPosition() {
        const triggerRect = this.$refs.trigger.getBoundingClientRect();
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        const scrollLeft = window.pageXOffset || document.documentElement.scrollLeft;
        
        this.triggerPosition = {
            top: triggerRect.top + scrollTop,
            left: triggerRect.left + scrollLeft,
            width: triggerRect.width,
            height: triggerRect.height
        };
    },
    
    getPopoverStyles() {
        let top = this.triggerPosition.top;
        let left = this.triggerPosition.left;
        let transform = '';
        let transformOrigin = '';
        
        // Build transform components
        let transformX = '';
        let transformY = '';
        
        // Position based on the position prop
        if ('{{ $position }}' === 'bottom') {
            top += this.triggerPosition.height + {{ $gap * 4 }}; // Convert gap to pixels (gap * 4px)
        } else if ('{{ $position }}' === 'top') {
            top -= {{ $gap * 4 }};
            transformY = 'translateY(-100%)';
        } else if ('{{ $position }}' === 'right') {
            left += this.triggerPosition.width + {{ $gap * 4 }}; 
        } else if ('{{ $position }}' === 'left') {
            left -= {{ $gap * 4 }};
            transformX = 'translateX(-100%)';
        }
        
        // Align based on the align prop
        if ('{{ $align }}' === 'center' && ('{{ $position }}' === 'top' || '{{ $position }}' === 'bottom')) {
            left += (this.triggerPosition.width / 2);
            transformX = 'translateX(-50%)';
        } else if ('{{ $align }}' === 'right' && ('{{ $position }}' === 'top' || '{{ $position }}' === 'bottom')) {
            left += this.triggerPosition.width;
            transformX = 'translateX(-100%)';
        } else if ('{{ $align }}' === 'center' && ('{{ $position }}' === 'right' || '{{ $position }}' === 'left')) {
            top += (this.triggerPosition.height / 2);
            transformY = 'translateY(-50%)';
        } else if ('{{ $align }}' === 'bottom' && ('{{ $position }}' === 'right' || '{{ $position }}' === 'left')) {
            top += this.triggerPosition.height;
            transformY = 'translateY(-100%)';
        }
        
        // Combine transforms
        transform = [transformX, transformY].filter(t => t).join(' ');
        
        // Set transform origin based on position and alignment
        if ('{{ $position }}' === 'bottom') {
           transformOrigin = 'top left';
        } else if ('{{ $position }}' === 'top') {
            transformOrigin = 'bottom left';
        } else if ('{{ $position }}' === 'right' || '{{ $position }}' === 'left') {
            transformOrigin = 'bottom left';
        }
        
        return {
            position: 'absolute',
            top: top + 'px',
            left: left + 'px',
            transform: transform,
            transformOrigin: transformOrigin,
            zIndex: 50
        };
    },
    
    openPopover() {
        this.calculateTriggerPosition();
        this.popoverOpen = true;
    }
}" 
@resize.window="if (popoverOpen) calculateTriggerPosition()"
@scroll.window="if (popoverOpen) calculateTriggerPosition()"
@class([
    'relative w-auto items-start inline-flex',
    'flex-col' => $position === 'bottom' || $position === 'top',
    'flex-row' => $position === 'right' || $position === 'left',
])>

    <div x-ref="trigger" x-on:click="openPopover()">
        @if ($trigger ?? false)
            {!! $trigger !!}
        @else
            <div class="relative inline-flex items-center justify-center rounded-md border bg-white p-2 text-sm font-medium text-neutral-700 transition-colors hover:bg-neutral-100 focus:bg-white focus:outline-none active:bg-white disabled:pointer-events-none disabled:opacity-50">
                button
            </div>
        @endif
    </div>

    <template x-teleport="body">
        <div
            x-show="popoverOpen"
            x-on:click.away="popoverOpen=false"
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-2 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 -translate-y-2 scale-95"
            :style="getPopoverStyles()"
            x-cloak
            class="w-auto max-w-sm"
        >
            {{ $slot }}
        </div>
    </template>
</div>
