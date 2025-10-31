{{-- Tooltip Component --}}
@props([
    'content' => '',
    'side' => 'top',
    'align' => 'center',
    'delayDuration' => 100,
    'skipDelayDuration' => 300,
    'disableHoverableContent' => false,
    'sideOffset' => 4,
    'alignOffset' => 0,
    'arrowPadding' => 5,
    'collisionPadding' => 10,
    'sticky' => 'partial',
    'hideWhenDetached' => false,
    'avoidCollisions' => true,
    'class' => '',
    'contentClass' => '',
    'triggerClass' => '',
    'arrowClass' => '',
    'showArrow' => true,
])

@php
    $tooltipId = 'tooltip-' . uniqid();
    $triggerId = 'tooltip-trigger-' . uniqid();
    
    $contentBaseClasses = 'z-50 overflow-hidden rounded-md bg-primary px-3 py-1.5 text-xs text-primary-foreground animate-in fade-in-0 zoom-in-95 data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=closed]:zoom-out-95';
    
    $sideClasses = match($side) {
        'top' => 'data-[side=top]:slide-in-from-bottom-2',
        'right' => 'data-[side=right]:slide-in-from-left-2',
        'bottom' => 'data-[side=bottom]:slide-in-from-top-2',
        'left' => 'data-[side=left]:slide-in-from-right-2',
        default => 'data-[side=top]:slide-in-from-bottom-2'
    };
    
    $finalContentClasses = $contentBaseClasses . ' ' . $sideClasses . ' ' . $contentClass;
    
    $arrowClasses = 'fill-primary ' . $arrowClass;
@endphp

<!-- Tooltip Container (No Visual Wrapper) -->
<div
    {{ $attributes->merge(['class' => 'contents ' . $class]) }}
    x-data="tooltipContainer({
        content: @js($content),
        side: @js($side),
        align: @js($align),
        delayDuration: @js($delayDuration),
        skipDelayDuration: @js($skipDelayDuration),
        disableHoverableContent: @js($disableHoverableContent),
        sideOffset: @js($sideOffset),
        alignOffset: @js($alignOffset),
        arrowPadding: @js($arrowPadding),
        collisionPadding: @js($collisionPadding),
        sticky: @js($sticky),
        hideWhenDetached: @js($hideWhenDetached),
        avoidCollisions: @js($avoidCollisions),
        showArrow: @js($showArrow),
        tooltipId: @js($tooltipId),
        triggerId: @js($triggerId),
        triggerClass: @js($triggerClass),
        contentClasses: @js($finalContentClasses),
        arrowClasses: @js($arrowClasses)
    })"
    x-init="init()"
>
    {{ $slot }}

    <!-- Portal for tooltip content -->
    <template x-teleport="body">
        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            :id="tooltipId"
            :data-state="stateAttribute"
            :data-side="actualSide || side"
            :class="contentClasses"
            x-ref="content"
            @mouseenter="handleContentEnter($event)"
            @mouseleave="handleContentLeave($event)"
            role="tooltip"
            :style="contentStyles"
            style="position: fixed; z-index: 50;"
        >
            <!-- Arrow -->
            <template x-if="showArrow && open">
                <div
                    class="absolute"
                    :style="arrowStyles"
                    x-ref="arrow"
                >
                    <svg
                        width="10"
                        height="5"
                        viewBox="0 0 30 10"
                        preserveAspectRatio="none"
                        :class="arrowClasses"
                    >
                        <polygon points="0,0 30,0 15,10"></polygon>
                    </svg>
                </div>
            </template>
            
            <!-- Content -->
            <div x-html="content"></div>
        </div>
    </template>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('tooltipContainer', (config) => ({
        // Configuration
        content: config.content || '',
        side: config.side || 'top',
        align: config.align || 'center',
        delayDuration: config.delayDuration || 700,
        skipDelayDuration: config.skipDelayDuration || 300,
        disableHoverableContent: config.disableHoverableContent || false,
        sideOffset: config.sideOffset || 4,
        alignOffset: config.alignOffset || 0,
        arrowPadding: config.arrowPadding || 5,
        collisionPadding: config.collisionPadding || 10,
        sticky: config.sticky || 'partial',
        hideWhenDetached: config.hideWhenDetached || false,
        avoidCollisions: config.avoidCollisions || true,
        showArrow: config.showArrow !== false,
        tooltipId: config.tooltipId,
        triggerId: config.triggerId,
        triggerClass: config.triggerClass || '',
        contentClasses: config.contentClasses || '',
        arrowClasses: config.arrowClasses || '',
        
        // Target element reference
        targetElement: null,
        
        // State
        open: false,
        wasOpenDelayed: false,
        isPointerDown: false,
        hasPointerMoveOpened: false,
        openTimer: null,
        skipDelayTimer: null,
        pointerGraceArea: null,
        isPointerInTransit: false,
        actualSide: null,
        
        // Computed
        get stateAttribute() {
            return this.open ? (this.wasOpenDelayed ? 'delayed-open' : 'instant-open') : 'closed';
        },
        
        get contentStyles() {
            if (!this.open || !this.targetElement || !this.$refs.content) return {};
            
            const triggerRect = this.targetElement.getBoundingClientRect();
            const contentRect = this.$refs.content.getBoundingClientRect();
            const viewportWidth = window.innerWidth;
            const viewportHeight = window.innerHeight;
            const padding = this.collisionPadding;
            
            // Determine the best side based on available space
            let finalSide = this.side;
            
            if (this.avoidCollisions) {
                const spaceTop = triggerRect.top;
                const spaceBottom = viewportHeight - triggerRect.bottom;
                const spaceLeft = triggerRect.left;
                const spaceRight = viewportWidth - triggerRect.right;
                
                const requiredHeight = contentRect.height + this.sideOffset + padding;
                const requiredWidth = contentRect.width + this.sideOffset + padding;
                
                // Check if preferred side has enough space
                switch (this.side) {
                    case 'top':
                        if (spaceTop < requiredHeight) {
                            // Try bottom first, then sides
                            if (spaceBottom >= requiredHeight) {
                                finalSide = 'bottom';
                            } else if (spaceRight >= requiredWidth) {
                                finalSide = 'right';
                            } else if (spaceLeft >= requiredWidth) {
                                finalSide = 'left';
                            }
                        }
                        break;
                    case 'bottom':
                        if (spaceBottom < requiredHeight) {
                            // Try top first, then sides
                            if (spaceTop >= requiredHeight) {
                                finalSide = 'top';
                            } else if (spaceRight >= requiredWidth) {
                                finalSide = 'right';
                            } else if (spaceLeft >= requiredWidth) {
                                finalSide = 'left';
                            }
                        }
                        break;
                    case 'left':
                        if (spaceLeft < requiredWidth) {
                            // Try right first, then top/bottom
                            if (spaceRight >= requiredWidth) {
                                finalSide = 'right';
                            } else if (spaceTop >= requiredHeight) {
                                finalSide = 'top';
                            } else if (spaceBottom >= requiredHeight) {
                                finalSide = 'bottom';
                            }
                        }
                        break;
                    case 'right':
                        if (spaceRight < requiredWidth) {
                            // Try left first, then top/bottom
                            if (spaceLeft >= requiredWidth) {
                                finalSide = 'left';
                            } else if (spaceTop >= requiredHeight) {
                                finalSide = 'top';
                            } else if (spaceBottom >= requiredHeight) {
                                finalSide = 'bottom';
                            }
                        }
                        break;
                }
            }
            
            let x, y;
            
            // Calculate position based on final side
            switch (finalSide) {
                case 'top':
                    x = triggerRect.left + (triggerRect.width / 2) - (contentRect.width / 2);
                    y = triggerRect.top - contentRect.height - this.sideOffset;
                    break;
                case 'bottom':
                    x = triggerRect.left + (triggerRect.width / 2) - (contentRect.width / 2);
                    y = triggerRect.bottom + this.sideOffset;
                    break;
                case 'left':
                    x = triggerRect.left - contentRect.width - this.sideOffset;
                    y = triggerRect.top + (triggerRect.height / 2) - (contentRect.height / 2);
                    break;
                case 'right':
                    x = triggerRect.right + this.sideOffset;
                    y = triggerRect.top + (triggerRect.height / 2) - (contentRect.height / 2);
                    break;
            }
            
            // Apply alignment offset
            if (finalSide === 'top' || finalSide === 'bottom') {
                if (this.align === 'start') {
                    x = triggerRect.left + this.alignOffset;
                } else if (this.align === 'end') {
                    x = triggerRect.right - contentRect.width - this.alignOffset;
                }
            } else {
                if (this.align === 'start') {
                    y = triggerRect.top + this.alignOffset;
                } else if (this.align === 'end') {
                    y = triggerRect.bottom - contentRect.height - this.alignOffset;
                }
            }
            
            // Final boundary checks (without flipping sides)
            if (this.avoidCollisions) {
                if (x < padding) x = padding;
                if (x + contentRect.width > viewportWidth - padding) {
                    x = viewportWidth - contentRect.width - padding;
                }
                if (y < padding) y = padding;
                if (y + contentRect.height > viewportHeight - padding) {
                    y = viewportHeight - contentRect.height - padding;
                }
            }
            
            // Store the final side for arrow positioning
            this.actualSide = finalSide;
            
            return {
                left: `${x}px`,
                top: `${y}px`,
            };
        },
        
        get arrowStyles() {
            if (!this.showArrow || !this.open || !this.targetElement || !this.$refs.content) return {};
            
            const triggerRect = this.targetElement.getBoundingClientRect();
            const contentRect = this.$refs.content.getBoundingClientRect();
            
            let arrowX, arrowY, transform = '';
            const actualSide = this.actualSide || this.side;
            
            switch (actualSide) {
                case 'top':
                    arrowX = (triggerRect.left + triggerRect.width / 2) - contentRect.left - 5;
                    arrowY = contentRect.height;
                    transform = 'rotate(180deg)';
                    break;
                case 'bottom':
                    arrowX = (triggerRect.left + triggerRect.width / 2) - contentRect.left - 5;
                    arrowY = -5;
                    break;
                case 'left':
                    arrowX = contentRect.width;
                    arrowY = (triggerRect.top + triggerRect.height / 2) - contentRect.top - 2.5;
                    transform = 'rotate(-90deg)';
                    break;
                case 'right':
                    arrowX = -5;
                    arrowY = (triggerRect.top + triggerRect.height / 2) - contentRect.top - 2.5;
                    transform = 'rotate(90deg)';
                    break;
            }
            
            return {
                left: `${arrowX}px`,
                top: `${arrowY}px`,
                transform: transform,
            };
        },
        
        init() {
            // Find the target element (first child of the container)
            this.targetElement = this.$el.firstElementChild;
            
            if (!this.targetElement) {
                console.warn('Tooltip: No target element found in slot');
                return;
            }
            
            // Add tooltip attributes and classes to target element
            this.setupTargetElement();
            
            // Global tooltip management
            if (!window.tooltipProvider) {
                window.tooltipProvider = {
                    isOpenDelayed: true,
                    activeTooltips: new Set(),
                    skipDelayTimer: null,
                };
            }
            
            // Listen for other tooltips opening
            document.addEventListener('tooltip:open', (e) => {
                if (e.detail.id !== this.tooltipId && this.open) {
                    this.handleClose();
                }
            });
        },
        
        setupTargetElement() {
            const target = this.targetElement;
            
            // Add ID and ARIA attributes
            target.id = this.triggerId;
            target.setAttribute('aria-describedby', '');
            
            // Add trigger classes if specified
            if (this.triggerClass) {
                target.classList.add(...this.triggerClass.split(' ').filter(c => c));
            }
            
            // Make focusable if not already
            if (!target.hasAttribute('tabindex')) {
                target.setAttribute('tabindex', '0');
            }
            
            // Add event listeners
            target.addEventListener('mouseenter', (e) => this.handleTriggerEnter(e));
            target.addEventListener('mouseleave', (e) => this.handleTriggerLeave(e));
            target.addEventListener('focus', (e) => this.handleTriggerFocus(e));
            target.addEventListener('blur', (e) => this.handleTriggerBlur(e));
            target.addEventListener('click', (e) => this.handleTriggerClick(e));
            target.addEventListener('pointerdown', (e) => this.handlePointerDown(e));
            target.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') this.handleEscape();
            });
            
            // Watch for state changes to update aria-describedby
            this.$watch('open', (value) => {
                target.setAttribute('aria-describedby', value ? this.tooltipId : '');
                target.setAttribute('data-state', this.stateAttribute);
            });
        },
        
        handleTriggerEnter(event) {
            if (event.pointerType === 'touch') return;
            
            if (!this.hasPointerMoveOpened && !this.isPointerInTransit) {
                if (window.tooltipProvider.isOpenDelayed) {
                    this.handleDelayedOpen();
                } else {
                    this.handleOpen();
                }
                this.hasPointerMoveOpened = true;
            }
        },
        
        handleTriggerLeave() {
            if (this.disableHoverableContent) {
                this.handleClose();
            } else {
                this.clearOpenTimer();
            }
            this.hasPointerMoveOpened = false;
        },
        
        handleTriggerFocus() {
            if (!this.isPointerDown) {
                this.handleOpen();
            }
        },
        
        handleTriggerBlur() {
            this.handleClose();
        },
        
        handleTriggerClick() {
            this.handleClose();
        },
        
        handlePointerDown() {
            if (this.open) {
                this.handleClose();
            }
            this.isPointerDown = true;
            
            const handlePointerUp = () => {
                this.isPointerDown = false;
                document.removeEventListener('pointerup', handlePointerUp);
            };
            
            document.addEventListener('pointerup', handlePointerUp, { once: true });
        },
        
        handleContentEnter() {
            this.clearOpenTimer();
        },
        
        handleContentLeave() {
            if (!this.disableHoverableContent) {
                this.handleClose();
            }
        },
        
        handleOpen() {
            this.clearOpenTimer();
            this.wasOpenDelayed = false;
            this.open = true;
            window.tooltipProvider.activeTooltips.add(this.tooltipId);
            
            // Notify other tooltips
            document.dispatchEvent(new CustomEvent('tooltip:open', {
                detail: { id: this.tooltipId }
            }));
            
            this.$nextTick(() => {
                if (this.$refs.content) {
                    this.updatePosition();
                }
            });
        },
        
        handleClose() {
            this.clearOpenTimer();
            this.open = false;
            window.tooltipProvider.activeTooltips.delete(this.tooltipId);
            
            // Reset skip delay
            if (window.tooltipProvider.skipDelayTimer) {
                clearTimeout(window.tooltipProvider.skipDelayTimer);
            }
            
            window.tooltipProvider.skipDelayTimer = setTimeout(() => {
                window.tooltipProvider.isOpenDelayed = true;
            }, this.skipDelayDuration);
        },
        
        handleDelayedOpen() {
            this.clearOpenTimer();
            this.openTimer = setTimeout(() => {
                this.wasOpenDelayed = true;
                this.open = true;
                window.tooltipProvider.activeTooltips.add(this.tooltipId);
                
                document.dispatchEvent(new CustomEvent('tooltip:open', {
                    detail: { id: this.tooltipId }
                }));
                
                this.$nextTick(() => {
                    if (this.$refs.content) {
                        this.updatePosition();
                    }
                });
            }, this.delayDuration);
        },
        
        clearOpenTimer() {
            if (this.openTimer) {
                clearTimeout(this.openTimer);
                this.openTimer = null;
            }
        },
        
        handleEscape() {
            if (this.open) {
                this.handleClose();
            }
        },
        
        handleScroll(event) {
            if (this.open && this.targetElement && event.target.contains(this.targetElement)) {
                this.handleClose();
            }
        },
        
        updatePosition() {
            // Force recalculation of position
            this.$nextTick(() => {
                // Position will be recalculated via contentStyles getter
            });
        }
    }));
});
</script>