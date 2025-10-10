@props([
    'grid' => false,
    'center' => true,
    'centerCanvasOnResize' => false,
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
      this.centerCanvas();
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

      @if($centerCanvasOnResize)
        window.addEventListener('resize', () => {
          this.centerCanvas(this.$el.clientWidth, this.$el.clientHeight);
        });
      @endif
    },

    centerCanvas() {
      let posX = -(50000 - this.$el.clientWidth / 2);
      let posY = -(50000 - this.$el.clientHeight / 2);
      this.positionCanvas(posX, posY);
      
      @if($center)
      //this.pos.y = -(50000 - h / 2);
      @else
      // Position so the top of the content is visible (accounting for the content being centered in the infinite canvas)
      //this.pos.y = -(49700 - h);
      @endif
    },
    positionCanvas(w, h) {
      if(w != null){
        this.pos.x = w;
      }
      if(h != null){
        this.pos.y = h;
      }
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
  class="overflow-hidden relative w-full h-full"
  @canvas-zoom-in.window="scale+=0.1; commitTransform()"
  @canvas-zoom-out.window="scale-=0.1; commitTransform()"
  @canvas-center.window="centerCanvas()"
  @canvas-position.window="positionCanvas($event.detail.x, $event.detail.y)"
>
  <div x-ref="canvas" class="absolute inset-0 w-full h-full origin-center infinite-canvas">
    <!-- optional background grid -->
    <!-- <div class="fixed inset-0 w-full h-full bg-grid"></div> -->
    <div class="absolute top-1/2 left-1/2 w-full h-auto transform -translate-x-1/2 -translate-y-1/2">
        {{ $slot }}
    </div>
  </div>
</div>

