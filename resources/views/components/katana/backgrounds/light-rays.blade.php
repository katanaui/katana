@props([
    'raysOrigin' => 'top-center',
    'raysColor' => '#ffffff',
    'raysSpeed' => 1,
    'lightSpread' => 1,
    'rayLength' => 2,
    'pulsating' => false,
    'fadeDistance' => 1.0,
    'saturation' => 1.0,
    'followMouse' => true,
    'mouseInfluence' => 0.1,
    'noiseAmount' => 0.0,
    'distortion' => 0.0,
])

@php
$config = [
    'raysOrigin' => (string) $raysOrigin,
    'raysColor' => (string) $raysColor,
    'raysSpeed' => (float) $raysSpeed,
    'lightSpread' => (float) $lightSpread,
    'rayLength' => (float) $rayLength,
    'pulsating' => (bool) $pulsating,
    'fadeDistance' => (float) $fadeDistance,
    'saturation' => (float) $saturation,
    'followMouse' => (bool) $followMouse,
    'mouseInfluence' => (float) $mouseInfluence,
    'noiseAmount' => (float) $noiseAmount,
    'distortion' => (float) $distortion,
];
@endphp

<div
    x-data="lightRaysComponent({{ Js::from($config) }})"
    x-init="init()"
    x-destroy="destroy()"
    {{ $attributes->twMerge('relative w-full h-full overflow-hidden pointer-events-none') }}
    style="z-index: 3;">
</div>

@once
<script>
window.lightRaysComponent = function(options) {
    return {
        options: options,
        renderer: null,
        gl: null,
        mesh: null,
        uniforms: null,
        animationId: null,
        intersectionObserver: null,
        resizeHandler: null,
        mouseHandler: null,
        updatePlacement: null,
        mouse: { x: 0.5, y: 0.5 },
        smoothMouse: { x: 0.5, y: 0.5 },
        isVisible: false,
        webglReady: false,

        async init() {
            try {
                if (!window.OGL) {
                    window.OGL = await import('https://esm.sh/ogl@1.0.11');
                }
                this.$nextTick(() => this.setupObserver());
            } catch (error) {
                console.error('Failed to load OGL library for light-rays:', error);
            }
        },

        setupObserver() {
            var self = this;
            this.intersectionObserver = new IntersectionObserver(function(entries) {
                var entry = entries[0];
                var nowVisible = entry.isIntersecting;
                if (nowVisible && !self.isVisible) {
                    self.isVisible = true;
                    if (!self.webglReady) {
                        self.setupWebGL();
                    } else {
                        self.startLoop();
                    }
                } else if (!nowVisible && self.isVisible) {
                    self.isVisible = false;
                    self.stopLoop();
                }
            }, { threshold: 0.1 });
            this.intersectionObserver.observe(this.$el);
        },

        setupWebGL() {
            var container = this.$el;
            var OGL = window.OGL;
            var Renderer = OGL.Renderer;
            var Program = OGL.Program;
            var Triangle = OGL.Triangle;
            var Mesh = OGL.Mesh;

            var renderer = new Renderer({
                dpr: Math.min(window.devicePixelRatio, 2),
                alpha: true
            });
            this.renderer = renderer;
            var gl = renderer.gl;
            this.gl = gl;
            gl.canvas.style.width = '100%';
            gl.canvas.style.height = '100%';

            while (container.firstChild) {
                container.removeChild(container.firstChild);
            }
            container.appendChild(gl.canvas);

            var vert = `
attribute vec2 position;
varying vec2 vUv;
void main() {
    vUv = position * 0.5 + 0.5;
    gl_Position = vec4(position, 0.0, 1.0);
}`;

            var frag = `precision highp float;

uniform float iTime;
uniform vec2  iResolution;

uniform vec2  rayPos;
uniform vec2  rayDir;
uniform vec3  raysColor;
uniform float raysSpeed;
uniform float lightSpread;
uniform float rayLength;
uniform float pulsating;
uniform float fadeDistance;
uniform float saturation;
uniform vec2  mousePos;
uniform float mouseInfluence;
uniform float noiseAmount;
uniform float distortion;

varying vec2 vUv;

float noise(vec2 st) {
    return fract(sin(dot(st.xy, vec2(12.9898,78.233))) * 43758.5453123);
}

float rayStrength(vec2 raySource, vec2 rayRefDirection, vec2 coord,
                  float seedA, float seedB, float speed) {
    vec2 sourceToCoord = coord - raySource;
    vec2 dirNorm = normalize(sourceToCoord);
    float cosAngle = dot(dirNorm, rayRefDirection);

    float distortedAngle = cosAngle + distortion * sin(iTime * 2.0 + length(sourceToCoord) * 0.01) * 0.2;

    float spreadFactor = pow(max(distortedAngle, 0.0), 1.0 / max(lightSpread, 0.001));

    float distance = length(sourceToCoord);
    float maxDistance = iResolution.x * rayLength;
    float lengthFalloff = clamp((maxDistance - distance) / maxDistance, 0.0, 1.0);

    float fadeFalloff = clamp((iResolution.x * fadeDistance - distance) / (iResolution.x * fadeDistance), 0.5, 1.0);
    float pulse = pulsating > 0.5 ? (0.8 + 0.2 * sin(iTime * speed * 3.0)) : 1.0;

    float baseStrength = clamp(
        (0.45 + 0.15 * sin(distortedAngle * seedA + iTime * speed)) +
        (0.3 + 0.2 * cos(-distortedAngle * seedB + iTime * speed)),
        0.0, 1.0
    );

    return baseStrength * lengthFalloff * fadeFalloff * spreadFactor * pulse;
}

void mainImage(out vec4 fragColor, in vec2 fragCoord) {
    vec2 coord = vec2(fragCoord.x, iResolution.y - fragCoord.y);

    vec2 finalRayDir = rayDir;
    if (mouseInfluence > 0.0) {
        vec2 mouseScreenPos = mousePos * iResolution.xy;
        vec2 mouseDirection = normalize(mouseScreenPos - rayPos);
        finalRayDir = normalize(mix(rayDir, mouseDirection, mouseInfluence));
    }

    vec4 rays1 = vec4(1.0) *
                 rayStrength(rayPos, finalRayDir, coord, 36.2214, 21.11349,
                             1.5 * raysSpeed);
    vec4 rays2 = vec4(1.0) *
                 rayStrength(rayPos, finalRayDir, coord, 22.3991, 18.0234,
                             1.1 * raysSpeed);

    fragColor = rays1 * 0.5 + rays2 * 0.4;

    if (noiseAmount > 0.0) {
        float n = noise(coord * 0.01 + iTime * 0.1);
        fragColor.rgb *= (1.0 - noiseAmount + noiseAmount * n);
    }

    float brightness = 1.0 - (coord.y / iResolution.y);
    fragColor.x *= 0.1 + brightness * 0.8;
    fragColor.y *= 0.3 + brightness * 0.6;
    fragColor.z *= 0.5 + brightness * 0.5;

    if (saturation != 1.0) {
        float gray = dot(fragColor.rgb, vec3(0.299, 0.587, 0.114));
        fragColor.rgb = mix(vec3(gray), fragColor.rgb, saturation);
    }

    fragColor.rgb *= raysColor;
}

void main() {
    vec4 color;
    mainImage(color, gl_FragCoord.xy);
    gl_FragColor  = color;
}`;

            var opts = this.options;
            var uniforms = {
                iTime: { value: 0 },
                iResolution: { value: [1, 1] },
                rayPos: { value: [0, 0] },
                rayDir: { value: [0, 1] },
                raysColor: { value: this.hexToRgb(opts.raysColor) },
                raysSpeed: { value: opts.raysSpeed },
                lightSpread: { value: opts.lightSpread },
                rayLength: { value: opts.rayLength },
                pulsating: { value: opts.pulsating ? 1.0 : 0.0 },
                fadeDistance: { value: opts.fadeDistance },
                saturation: { value: opts.saturation },
                mousePos: { value: [0.5, 0.5] },
                mouseInfluence: { value: opts.mouseInfluence },
                noiseAmount: { value: opts.noiseAmount },
                distortion: { value: opts.distortion }
            };
            this.uniforms = uniforms;

            var geometry = new Triangle(gl);
            var program = new Program(gl, {
                vertex: vert,
                fragment: frag,
                uniforms: uniforms
            });
            var mesh = new Mesh(gl, { geometry: geometry, program: program });
            this.mesh = mesh;

            var self = this;
            this.updatePlacement = function() {
                if (!self.renderer) return;
                self.renderer.dpr = Math.min(window.devicePixelRatio, 2);
                var wCSS = container.clientWidth;
                var hCSS = container.clientHeight;
                self.renderer.setSize(wCSS, hCSS);
                var dpr = self.renderer.dpr;
                var w = wCSS * dpr;
                var h = hCSS * dpr;
                uniforms.iResolution.value = [w, h];
                var placement = self.getAnchorAndDir(self.options.raysOrigin, w, h);
                uniforms.rayPos.value = placement.anchor;
                uniforms.rayDir.value = placement.dir;
            };

            this.resizeHandler = this.updatePlacement;
            window.addEventListener('resize', this.resizeHandler);
            this.updatePlacement();

            if (this.options.followMouse) {
                this.mouseHandler = function(e) {
                    if (!self.renderer) return;
                    var rect = container.getBoundingClientRect();
                    var x = (e.clientX - rect.left) / rect.width;
                    var y = (e.clientY - rect.top) / rect.height;
                    self.mouse = { x: x, y: y };
                };
                window.addEventListener('mousemove', this.mouseHandler);
            }

            this.webglReady = true;
            this.startLoop();
        },

        startLoop() {
            if (this.animationId) return;
            var self = this;
            var loop = function(t) {
                if (!self.renderer || !self.uniforms || !self.mesh) return;
                self.uniforms.iTime.value = t * 0.001;

                if (self.options.followMouse && self.options.mouseInfluence > 0.0) {
                    var smoothing = 0.92;
                    self.smoothMouse.x = self.smoothMouse.x * smoothing + self.mouse.x * (1 - smoothing);
                    self.smoothMouse.y = self.smoothMouse.y * smoothing + self.mouse.y * (1 - smoothing);
                    self.uniforms.mousePos.value = [self.smoothMouse.x, self.smoothMouse.y];
                }

                try {
                    self.renderer.render({ scene: self.mesh });
                    self.animationId = requestAnimationFrame(loop);
                } catch (error) {
                    console.warn('WebGL rendering error:', error);
                }
            };
            this.animationId = requestAnimationFrame(loop);
        },

        stopLoop() {
            if (this.animationId) {
                cancelAnimationFrame(this.animationId);
                this.animationId = null;
            }
        },

        hexToRgb(hex) {
            var m = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
            return m
                ? [parseInt(m[1], 16) / 255, parseInt(m[2], 16) / 255, parseInt(m[3], 16) / 255]
                : [1, 1, 1];
        },

        getAnchorAndDir(origin, w, h) {
            var outside = 0.2;
            switch (origin) {
                case 'top-left':
                    return { anchor: [0, -outside * h], dir: [0, 1] };
                case 'top-right':
                    return { anchor: [w, -outside * h], dir: [0, 1] };
                case 'left':
                    return { anchor: [-outside * w, 0.5 * h], dir: [1, 0] };
                case 'right':
                    return { anchor: [(1 + outside) * w, 0.5 * h], dir: [-1, 0] };
                case 'bottom-left':
                    return { anchor: [0, (1 + outside) * h], dir: [0, -1] };
                case 'bottom-center':
                    return { anchor: [0.5 * w, (1 + outside) * h], dir: [0, -1] };
                case 'bottom-right':
                    return { anchor: [w, (1 + outside) * h], dir: [0, -1] };
                default:
                    return { anchor: [0.5 * w, -outside * h], dir: [0, 1] };
            }
        },

        destroy() {
            this.stopLoop();
            if (this.intersectionObserver) {
                this.intersectionObserver.disconnect();
                this.intersectionObserver = null;
            }
            if (this.resizeHandler) {
                window.removeEventListener('resize', this.resizeHandler);
                this.resizeHandler = null;
            }
            if (this.mouseHandler) {
                window.removeEventListener('mousemove', this.mouseHandler);
                this.mouseHandler = null;
            }
            if (this.renderer) {
                try {
                    var canvas = this.renderer.gl.canvas;
                    var loseCtx = this.renderer.gl.getExtension('WEBGL_lose_context');
                    if (loseCtx) loseCtx.loseContext();
                    if (canvas && canvas.parentNode) {
                        canvas.parentNode.removeChild(canvas);
                    }
                } catch (error) {
                    console.warn('Error during WebGL cleanup:', error);
                }
            }
            this.renderer = null;
            this.gl = null;
            this.mesh = null;
            this.uniforms = null;
            this.webglReady = false;
        }
    };
};
</script>
@endonce
