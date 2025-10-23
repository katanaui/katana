@props([
    'speed' => 5,
    'scale' => 1,
    'color' => '#7B7481',
    'noiseIntensity' => 1.5,
    'rotation' => 0,
])

<div x-data="{
    silkInstance: null,
    resizeHandler: null,
    resizeObserver: null,
    isLoading: true,
    maxRetries: 3,
    currentRetry: 0,

    async initSilk() {
        try {
            // First wait for THREE.js to be available
            await this.waitForTHREE();

            // Then wait for the shader to be available
            await this.waitForShader();

            if (window.initSilkShader) {
                this.silkInstance = window.initSilkShader($el, {
                    speed: {{ $speed }},
                    scale: {{ $scale }},
                    color: '{{ $color }}',
                    noiseIntensity: {{ $noiseIntensity }},
                    rotation: {{ $rotation }}
                });

                this.setupResizeHandlers();
                this.isLoading = false;
            }
        } catch (error) {
            console.error('Failed to initialize silk shader:', error);
            if (this.currentRetry < this.maxRetries) {
                this.currentRetry++;
                setTimeout(() => this.initSilk(), 1000 * this.currentRetry);
            } else {
                this.isLoading = false;
            }
        }
    },

    async waitForTHREE() {
        return new Promise((resolve, reject) => {
            let attempts = 0;
            const checkTHREE = () => {
                if (window.THREE) {
                    resolve();
                } else if (attempts >= this.maxRetries) {
                    reject(new Error('THREE.js failed to load after maximum retries'));
                } else {
                    attempts++;
                    setTimeout(checkTHREE, 500);
                }
            };
            checkTHREE();
        });
    },

    async waitForShader() {
        return new Promise((resolve, reject) => {
            let attempts = 0;
            const checkShader = () => {
                if (window.initSilkShader) {
                    resolve();
                } else if (attempts >= this.maxRetries) {
                    reject(new Error('Shader failed to load after maximum retries'));
                } else {
                    attempts++;
                    setTimeout(checkShader, 500);
                }
            };
            checkShader();
        });
    },

    setupResizeHandlers() {
        this.resizeHandler = () => {
            const canvas = $el.querySelector('canvas');
            if (canvas && this.silkInstance && this.silkInstance.renderer) {
                const rect = $el.getBoundingClientRect();
                canvas.width = rect.width;
                canvas.height = rect.height;
                this.silkInstance.renderer.setSize(rect.width, rect.height);
            }
        };

        window.addEventListener('resize', this.resizeHandler);

        this.resizeObserver = new ResizeObserver(() => {
            this.resizeHandler();
        });
        this.resizeObserver.observe($el);
    }
}" x-init="$nextTick(() => initSilk())" x-destroy="
        if (resizeHandler) { window.removeEventListener('resize', resizeHandler); }
        if (resizeObserver) { resizeObserver.disconnect(); }
    " {{ $attributes->twMerge('relative w-full h-full min-h-[360px]') }}>
    <canvas x-show="!isLoading" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" class="relative h-full w-full bg-black" height="100%" width="100%"></canvas>

    <!-- Loading state -->
    <div x-show="isLoading" class="absolute inset-0 bg-black"></div>
</div>

@once
    <script type="module">
        // Only import THREE.js if it's not already available
        if (typeof(window.THREE) == 'undefined') {
            const THREE = await import('https://cdn.jsdelivr.net/npm/three@0.160.0/build/three.module.js');
            window.THREE = THREE;
        }

        // Only define the silk shader function if it doesn't already exist
        if (!window.initSilkShader) {
            window.initSilkShader = function(container, options = {}) {
                const {
                    speed = 5,
                        scale = 1,
                        color = '#7B7481',
                        noiseIntensity = 1.5,
                        rotation = 0,
                } = options;

                const canvas = container.querySelector("canvas");

                const renderer = new window.THREE.WebGLRenderer({
                    canvas,
                    alpha: true
                });
                renderer.setSize(canvas.clientWidth, canvas.clientHeight);

                const scene = new window.THREE.Scene();
                const camera = new window.THREE.OrthographicCamera(-1, 1, 1, -1, 0, 1);

                const geometry = new window.THREE.PlaneGeometry(2, 2);

                const vertexShader = `
        varying vec2 vUv;
        varying vec3 vPosition;
    
        void main() {
          vPosition = position;
          vUv = uv;
          gl_Position = vec4(position, 1.0);
        }
      `;

                const fragmentShader = `
        varying vec2 vUv;
        varying vec3 vPosition;
    
        uniform float uTime;
        uniform vec3  uColor;
        uniform float uSpeed;
        uniform float uScale;
        uniform float uRotation;
        uniform float uNoiseIntensity;
    
        const float e = 2.718281828459045;
    
        float noise(vec2 texCoord) {
          float G = e;
          vec2  r = (G * sin(G * texCoord));
          return fract(r.x * r.y * (1.0 + texCoord.x));
        }
    
        vec2 rotateUvs(vec2 uv, float angle) {
          float c = cos(angle);
          float s = sin(angle);
          mat2  rot = mat2(c, -s, s, c);
          return rot * uv;
        }
    
        void main() {
          float rnd        = noise(gl_FragCoord.xy);
          vec2  uv         = rotateUvs(vUv * uScale, uRotation);
          vec2  tex        = uv * uScale;
          float tOffset    = uSpeed * uTime;
    
          tex.y += 0.03 * sin(8.0 * tex.x - tOffset);
    
          float pattern = 0.6 +
                          0.4 * sin(5.0 * (tex.x + tex.y +
                                           cos(3.0 * tex.x + 5.0 * tex.y) +
                                           0.02 * tOffset) +
                                   sin(20.0 * (tex.x + tex.y - 0.1 * tOffset)));
    
          vec4 col = vec4(uColor, 1.0) * vec4(pattern) - rnd / 15.0 * uNoiseIntensity;
          col.a = 1.0;
          gl_FragColor = col;
        }
      `;

                const uniforms = {
                    uTime: {
                        value: 0
                    },
                    uColor: {
                        value: new window.THREE.Color(color)
                    },
                    uSpeed: {
                        value: speed
                    },
                    uScale: {
                        value: scale
                    },
                    uRotation: {
                        value: rotation
                    },
                    uNoiseIntensity: {
                        value: noiseIntensity
                    },
                };

                const material = new window.THREE.ShaderMaterial({
                    uniforms,
                    vertexShader,
                    fragmentShader,
                });

                const mesh = new window.THREE.Mesh(geometry, material);
                scene.add(mesh);

                function animate(time) {
                    uniforms.uTime.value = time / 1000;
                    renderer.render(scene, camera);
                    requestAnimationFrame(animate);
                }

                animate();

                return {
                    renderer
                };
            };
        }
    </script>
@endonce
