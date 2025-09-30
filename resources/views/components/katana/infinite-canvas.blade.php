@props([
    'grid' => false
])

<style>
    .infinite-canvas {
        width: 100000px;
        height: 100000px;
        will-change: transform;
        
    }
    .bg-grid{
        background-image: 
            linear-gradient(rgba(0,0,0,0.1) 1px, transparent 1px),
            linear-gradient(90deg, rgba(0,0,0,0.1) 1px, transparent 1px);
        background-size: 100px 100px;
    }
</style>
<div x-data="{
        position: { x: -50000, y: -50000 },
        
        init() {
        // Center the canvas on load
        const containerWidth = this.$el.clientWidth;
        const containerHeight = this.$el.clientHeight;
        
        this.centerCanvas(containerWidth, containerHeight);
        
        // Handle wheel events for scrolling
        this.$el.addEventListener('wheel', (e) => {
            e.preventDefault();
            const deltaX = e.deltaX;
            const deltaY = e.deltaY;
            const scrollSpeed = 1;
            
            this.position.x -= deltaX * scrollSpeed;
            this.position.y -= deltaY * scrollSpeed;
            
            this.updatePosition();
        }, { passive: false });
        
        // Handle window resize
        window.addEventListener('resize', () => {
            const containerWidth = this.$el.clientWidth;
            const containerHeight = this.$el.clientHeight;
            
            this.centerCanvas(containerWidth, containerHeight);
        });
        },
        
        centerCanvas(containerWidth, containerHeight) {
        // Calculate center position based on container dimensions
        this.position.x = -(50000 - containerWidth/2);
        this.position.y = -(50000 - containerHeight/2);
        
        this.updatePosition();
        },
        
        updatePosition() {
        requestAnimationFrame(() => {
            this.$refs.canvas.style.transform = `translate(${this.position.x}px, ${this.position.y}px)`;
        });
        }
    }" 
    class="w-full h-100vh overflow-hidden relative"
    >
    <div x-ref="canvas" class="infinite-canvas origin-center relative">
        @if($grid)
            <div class="w-full h-full fixed inset-0 bg-grid"></div>
        @endif
        <div class="absolute left-1/2 top-1/2 transform -translate-x-1/2 -translate-y-1/2">
            {{ $slot }}
        </div>
    </div>
</div>