@php /*
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

        // 👇 NEW: Handle messages from iframe
        window.addEventListener('message', (e) => {
            if (e.data?.type === 'canvas-scroll') {
            this.position.x -= e.data.deltaX;
            this.position.y -= e.data.deltaY;
            this.updatePosition();
            }
        });
        
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
        <div class="absolute left-1/2 top-1/2 transform w-full max-w-7xl h-auto -translate-x-1/2 -translate-y-1/2">
            {{ $slot }}
        </div>
    </div>
</div> */ @endphp

@props([
    'grid' => false
])

<style>
    .infinite-canvas {
        width: 100000px;
        height: 100000px;
        will-change: transform;
        /* Optional: prevents browser overscroll/bounce on iOS/macOS */
        overscroll-behavior: none;
    }
</style>
<div
  x-data="{
    pos: { x: -50000, y: -50000 },
    scale: 0.75,
    vx: 0, vy: 0,
    speed: 0.3,   // 👈 reduce sensitivity
    friction: 0.7,
    rafId: null,

    init() {
      this.centerCanvas(this.$el.clientWidth, this.$el.clientHeight);
      this.startLoop();

      this.$el.addEventListener('wheel', (e) => {
        e.preventDefault();
        const { dx, dy } = this.normalizeWheel(e);
        this.applyDelta(dx, dy);
      }, { passive: false });

      window.addEventListener('message', (e) => {
        if (e?.data?.type === 'canvas-scroll') {
          this.applyDelta(e.data.deltaX, e.data.deltaY);
        }
      });

      window.addEventListener('resize', () => {
        this.centerCanvas(this.$el.clientWidth, this.$el.clientHeight);
      });
    },

    centerCanvas(w, h) {
      this.pos.x = -(50000 - w / 2);
      this.pos.y = -(50000 - h / 2);
      this.commitTransform();
    },

    applyDelta(dx, dy) {
      this.vx += dx * this.speed;
      this.vy += dy * this.speed;
    },

    startLoop() {
      const step = () => {
        if (Math.abs(this.vx) > 0.01 || Math.abs(this.vy) > 0.01) {
          this.pos.x -= this.vx;
          this.pos.y -= this.vy;
          this.vx *= this.friction;
          this.vy *= this.friction;
          this.commitTransform();
        }
        this.rafId = requestAnimationFrame(step);
      };
      step();
    },

    commitTransform() {
      this.$refs.canvas.style.transform =
        `translate(${this.pos.x}px, ${this.pos.y}px) scale(${this.scale})`;
    },

    normalizeWheel(e) {
      let dx = e.deltaX, dy = e.deltaY;
      if (e.deltaMode === 1) { // lines
        dx *= 16; dy *= 16;
      }
      return { dx, dy };
    },
  }"
  class="w-full h-100vh overflow-hidden relative"
  @canvas-zoom-in.window="scale+=0.1; commitTransform()"
  @canvas-zoom-out.window="scale-=0.1; commitTransform()"
>
  <div x-ref="canvas" class="infinite-canvas origin-center relative">
    <!-- optional background grid -->
    <!-- <div class="w-full h-full fixed inset-0 bg-grid"></div> -->
    <div class="absolute left-1/2 top-1/2 w-full h-auto transform -translate-x-1/2 -translate-y-1/2">
        {{ $slot }}
    </div>
  </div>
</div>

