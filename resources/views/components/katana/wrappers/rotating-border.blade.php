@props([
    'class' => '',
    'color' => '#75a522',
    'colorDark' => null,
    'onHover' => false,
    'infinite' => true,
    'duration' => 3,
    'borderWidth' => '1px',
    'autoplay' => true,
])

@php
    // Helper function to lighten a hex color
    $lightenColor = function($hex) {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        $r = min(255, hexdec(substr($hex, 0, 2)) + (255 - hexdec(substr($hex, 0, 2))) * 40 / 100);
        $g = min(255, hexdec(substr($hex, 2, 2)) + (255 - hexdec(substr($hex, 2, 2))) * 40 / 100);
        $b = min(255, hexdec(substr($hex, 4, 2)) + (255 - hexdec(substr($hex, 4, 2))) * 40 / 100);
        return sprintf('#%02x%02x%02x', $r, $g, $b);
    };

    // Generate light variants
    $colorLight = $lightenColor($color);
    
    // Use color as fallback for dark mode if not specified
    $colorDark = $colorDark ?? $color;
    $colorDarkLight = $lightenColor($colorDark);

    // Determine animation mode
    if ($onHover && $infinite) {
        $animationMode = 'hover';
    } elseif ($onHover && !$infinite) {
        $animationMode = 'hover-once';
    } elseif (!$onHover && $infinite && $autoplay) {
        $animationMode = 'always';
    } elseif (!$onHover && !$infinite && $autoplay) {
        $animationMode = 'always-once';
    } else {
        $animationMode = 'controlled';
    }
@endphp

@once
<style>
    @property --border-angle {
        inherits: false;
        initial-value: 0deg;
        syntax: '<angle>';
    }

    @keyframes rotating-border-spin {
        to { --border-angle: 360deg; }
    }

    @keyframes rotating-border-spin-fade {
        0% { --border-angle: 0deg; opacity: 0; }
        10% { opacity: 1; }
        90% { opacity: 1; }
        100% { --border-angle: 360deg; opacity: 0; }
    }

    /* Animated border pseudo-element */
    .rotating-border-wrapper::before {
        content: '';
        position: absolute;
        inset: calc(var(--rb-border-width) * -1);
        padding: var(--rb-border-width);
        border-radius: inherit;
        pointer-events: none;
        opacity: 0;
        --border-angle: 0deg;
        --rb-current-color: var(--rb-color);
        --rb-current-color-light: var(--rb-color-light);
        background: conic-gradient(
            from var(--border-angle),
            var(--color-background) 80%,
            var(--rb-current-color) 86%,
            var(--rb-current-color-light) 90%,
            var(--rb-current-color) 94%,
            var(--color-background)
        );
        -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
        -webkit-mask-composite: xor;
        mask-composite: exclude;
    }

    /* Dark mode color override */
    .dark .rotating-border-wrapper::before {
        --rb-current-color: var(--rb-color-dark);
        --rb-current-color-light: var(--rb-color-dark-light);
    }

    /* Always animate (infinite) */
    .rotating-border-wrapper[data-mode="always"]::before {
        opacity: 1;
        animation: rotating-border-spin var(--rb-duration) linear infinite;
    }

    /* Always once - plays once on load with fade */
    .rotating-border-wrapper[data-mode="always-once"]::before {
        animation: rotating-border-spin-fade var(--rb-duration) linear 1 forwards;
    }

    /* Hover infinite */
    .rotating-border-wrapper[data-mode="hover"]:hover::before {
        opacity: 1;
        animation: rotating-border-spin var(--rb-duration) linear infinite;
    }

    /* Hover once */
    .rotating-border-wrapper[data-mode="hover-once"]:hover::before {
        animation: rotating-border-spin-fade var(--rb-duration) linear 1 forwards;
    }

    /* Alpine-controlled states */
    .rotating-border-wrapper[data-playing="true"]::before {
        opacity: 1;
        animation: rotating-border-spin var(--rb-duration) linear infinite;
    }

    .rotating-border-wrapper[data-playing="once"]::before {
        animation: rotating-border-spin-fade var(--rb-duration) linear 1 forwards;
    }

    .rotating-border-wrapper[data-playing="false"]::before {
        opacity: 0;
        animation: none;
    }
</style>
@endonce

<div 
    x-data="rotatingBorder({{ $duration }})"
    {{ $attributes->twMerge(['class' => "rotating-border-wrapper relative bg-background border border-transparent rounded-2xl {$class}"]) }}
    data-mode="{{ $animationMode }}"
    x-bind:data-playing="playing"
    style="--rb-color: {{ $color }}; --rb-color-light: {{ $colorLight }}; --rb-color-dark: {{ $colorDark }}; --rb-color-dark-light: {{ $colorDarkLight }}; --rb-duration: {{ $duration }}s; --rb-border-width: {{ $borderWidth }};"
>
    {{ $slot }}
</div>

@once
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('rotatingBorder', (duration) => ({
            playing: null,
            duration: duration,

            play() {
                this.playing = 'false';
                this.$nextTick(() => {
                    this.playing = 'true';
                });
            },

            playOnce() {
                this.playing = 'false';
                this.$nextTick(() => {
                    this.playing = 'once';
                    setTimeout(() => {
                        if (this.playing === 'once') {
                            this.playing = 'false';
                        }
                    }, this.duration * 1000);
                });
            },

            stop() {
                this.playing = 'false';
            },

            toggle(once = false) {
                if (this.playing === 'true' || this.playing === 'once') {
                    this.stop();
                } else {
                    once ? this.playOnce() : this.play();
                }
            }
        }));
    });
</script>
@endonce