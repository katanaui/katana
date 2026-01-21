@props([
    'angle' => 65,
    'cellSize' => 60,
    'opacity' => 0.5,
    'lightLineColor' => 'gray',
    'darkLineColor' => 'gray',
])

<style>
    @keyframes retro-grid-scroll {
        0% { transform: translateY(-50%); }
        100% { transform: translateY(0); }
    }
    
    .retro-grid-animate {
        animation: retro-grid-scroll 20s linear infinite;
        will-change: transform;
    }
    
    .retro-grid-lines {
        background-repeat: repeat;
        height: 300vh;
        width: 200%;
        left: -50%;
        position: absolute;
        top: 0;
    }
    
    /* Light mode grid lines */
    :not(.dark) .retro-grid-lines-light {
        background-image: 
            linear-gradient(to right, var(--retro-grid-line-color, rgba(128, 128, 128, 0.4)) 1px, transparent 1px),
            linear-gradient(to bottom, var(--retro-grid-line-color, rgba(128, 128, 128, 0.4)) 1px, transparent 1px);
    }
    
    /* Dark mode grid lines */
    .dark .retro-grid-lines-dark {
        background-image: 
            linear-gradient(to right, var(--retro-grid-line-color-dark, rgba(255, 255, 255, 0.15)) 1px, transparent 1px),
            linear-gradient(to bottom, var(--retro-grid-line-color-dark, rgba(255, 255, 255, 0.15)) 1px, transparent 1px);
    }
</style>

<div {{ $attributes->twMerge('absolute inset-0 w-full h-full overflow-hidden') }}>
    <!-- Perspective wrapper - perspective must be on parent of transformed element -->
    <div class="absolute inset-0 overflow-hidden" style="perspective: 300px;">
        <!-- Grid container with perspective transform -->
        <div 
            class="absolute inset-0"
            style="transform: rotateX({{ $angle }}deg); transform-origin: center center;"
        >
            <!-- Animated grid lines -->
            <div 
                class="retro-grid-animate retro-grid-lines retro-grid-lines-light retro-grid-lines-dark"
                style="
                    background-size: {{ $cellSize }}px {{ $cellSize }}px;
                    opacity: {{ $opacity }};
                    --retro-grid-line-color: {{ $lightLineColor === 'gray' ? 'rgba(128, 128, 128, 0.4)' : $lightLineColor }};
                    --retro-grid-line-color-dark: {{ $darkLineColor === 'gray' ? 'rgba(255, 255, 255, 0.15)' : $darkLineColor }};
                "
            ></div>
        </div>
    </div>
    
    <!-- Gradient fade overlay - fades grid at top and bottom -->
    <div class="absolute inset-0 bg-gradient-to-t from-background via-transparent to-background pointer-events-none"></div>
    
    <!-- Additional bottom fade for stronger effect -->
    <div class="absolute inset-x-0 bottom-0 h-1/2 bg-gradient-to-t from-background to-transparent pointer-events-none"></div>
</div>
