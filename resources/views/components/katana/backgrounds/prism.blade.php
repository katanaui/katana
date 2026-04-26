@props([
    'height' => 3.5,
    'baseWidth' => 5.5,
    'animationType' => 'rotate',
    'glow' => 1,
    'offset' => ['x' => 0, 'y' => 0],
    'noise' => 0.5,
    'transparent' => true,
    'scale' => 3.6,
    'hueShift' => 0,
    'colorFrequency' => 1,
    'hoverStrength' => 2,
    'inertia' => 0.05,
    'bloom' => 1,
    'suspendWhenOffscreen' => false,
    'timeScale' => 0.5,
])

@php
$offsetArr = is_array($offset) ? $offset : (array) $offset;
$config = [
    'height' => (float) $height,
    'baseWidth' => (float) $baseWidth,
    'animationType' => (string) $animationType,
    'glow' => (float) $glow,
    'offset' => [
        'x' => (float) ($offsetArr['x'] ?? 0),
        'y' => (float) ($offsetArr['y'] ?? 0),
    ],
    'noise' => (float) $noise,
    'transparent' => (bool) $transparent,
    'scale' => (float) $scale,
    'hueShift' => (float) $hueShift,
    'colorFrequency' => (float) $colorFrequency,
    'hoverStrength' => (float) $hoverStrength,
    'inertia' => (float) $inertia,
    'bloom' => (float) $bloom,
    'suspendWhenOffscreen' => (bool) $suspendWhenOffscreen,
    'timeScale' => (float) $timeScale,
];
@endphp

<div
    x-data="prismComponent({{ Js::from($config) }})"
    x-init="init()"
    x-destroy="destroy()"
    {{ $attributes->twMerge('relative w-full h-full') }}>
</div>

@once
<script>
window.prismComponent = function(options) {
    return {
        options: options,
        gl: null,
        canvas: null,
        animationId: 0,
        resizeObserver: null,
        intersectionObserver: null,
        onPointerMove: null,
        onLeave: null,
        onBlur: null,

        async init() {
            try {
                if (!window.OGL) {
                    window.OGL = await import('https://esm.sh/ogl@1.0.11');
                }
                this.$nextTick(() => this.setup());
            } catch (error) {
                console.error('Failed to load OGL library for prism:', error);
            }
        },

        setup() {
            var container = this.$el;
            var OGL = window.OGL;
            var Renderer = OGL.Renderer;
            var Program = OGL.Program;
            var Mesh = OGL.Mesh;
            var Triangle = OGL.Triangle;

            var opts = this.options;
            var H = Math.max(0.001, opts.height);
            var BW = Math.max(0.001, opts.baseWidth);
            var BASE_HALF = BW * 0.5;
            var GLOW = Math.max(0.0, opts.glow);
            var NOISE = Math.max(0.0, opts.noise);
            var offX = opts.offset && opts.offset.x != null ? opts.offset.x : 0;
            var offY = opts.offset && opts.offset.y != null ? opts.offset.y : 0;
            var SAT = opts.transparent ? 1.5 : 1;
            var SCALE = Math.max(0.001, opts.scale);
            var HUE = opts.hueShift || 0;
            var CFREQ = Math.max(0.0, opts.colorFrequency || 1);
            var BLOOM = Math.max(0.0, opts.bloom || 1);
            var RSX = 1, RSY = 1, RSZ = 1;
            var TS = Math.max(0, opts.timeScale || 1);
            var HOVSTR = Math.max(0, opts.hoverStrength || 1);
            var INERT = Math.max(0, Math.min(1, opts.inertia || 0.12));

            var dpr = Math.min(2, window.devicePixelRatio || 1);
            var renderer = new Renderer({
                dpr: dpr,
                alpha: opts.transparent,
                antialias: false
            });
            var gl = renderer.gl;
            this.gl = gl;
            this.canvas = gl.canvas;
            gl.disable(gl.DEPTH_TEST);
            gl.disable(gl.CULL_FACE);
            gl.disable(gl.BLEND);

            Object.assign(gl.canvas.style, {
                position: 'absolute',
                inset: '0',
                width: '100%',
                height: '100%',
                display: 'block'
            });
            container.appendChild(gl.canvas);

            var vertex = `
                attribute vec2 position;
                void main() {
                    gl_Position = vec4(position, 0.0, 1.0);
                }
            `;

            var fragment = `
                precision highp float;

                uniform vec2  iResolution;
                uniform float iTime;

                uniform float uHeight;
                uniform float uBaseHalf;
                uniform mat3  uRot;
                uniform int   uUseBaseWobble;
                uniform float uGlow;
                uniform vec2  uOffsetPx;
                uniform float uNoise;
                uniform float uSaturation;
                uniform float uScale;
                uniform float uHueShift;
                uniform float uColorFreq;
                uniform float uBloom;
                uniform float uCenterShift;
                uniform float uInvBaseHalf;
                uniform float uInvHeight;
                uniform float uMinAxis;
                uniform float uPxScale;
                uniform float uTimeScale;

                vec4 tanh4(vec4 x){
                    vec4 e2x = exp(2.0*x);
                    return (e2x - 1.0) / (e2x + 1.0);
                }

                float rand(vec2 co){
                    return fract(sin(dot(co, vec2(12.9898, 78.233))) * 43758.5453123);
                }

                float sdOctaAnisoInv(vec3 p){
                    vec3 q = vec3(abs(p.x) * uInvBaseHalf, abs(p.y) * uInvHeight, abs(p.z) * uInvBaseHalf);
                    float m = q.x + q.y + q.z - 1.0;
                    return m * uMinAxis * 0.5773502691896258;
                }

                float sdPyramidUpInv(vec3 p){
                    float oct = sdOctaAnisoInv(p);
                    float halfSpace = -p.y;
                    return max(oct, halfSpace);
                }

                mat3 hueRotation(float a){
                    float c = cos(a), s = sin(a);
                    mat3 W = mat3(
                        0.299, 0.587, 0.114,
                        0.299, 0.587, 0.114,
                        0.299, 0.587, 0.114
                    );
                    mat3 U = mat3(
                         0.701, -0.587, -0.114,
                        -0.299,  0.413, -0.114,
                        -0.300, -0.588,  0.886
                    );
                    mat3 V = mat3(
                         0.168, -0.331,  0.500,
                         0.328,  0.035, -0.500,
                        -0.497,  0.296,  0.201
                    );
                    return W + U * c + V * s;
                }

                void main(){
                    vec2 f = (gl_FragCoord.xy - 0.5 * iResolution.xy - uOffsetPx) * uPxScale;

                    float z = 5.0;
                    float d = 0.0;

                    vec3 p;
                    vec4 o = vec4(0.0);

                    float centerShift = uCenterShift;
                    float cf = uColorFreq;

                    mat2 wob = mat2(1.0);
                    if (uUseBaseWobble == 1) {
                        float t = iTime * uTimeScale;
                        float c0 = cos(t + 0.0);
                        float c1 = cos(t + 33.0);
                        float c2 = cos(t + 11.0);
                        wob = mat2(c0, c1, c2, c0);
                    }

                    const int STEPS = 100;
                    for (int i = 0; i < STEPS; i++) {
                        p = vec3(f, z);
                        p.xz = p.xz * wob;
                        p = uRot * p;
                        vec3 q = p;
                        q.y += centerShift;
                        d = 0.1 + 0.2 * abs(sdPyramidUpInv(q));
                        z -= d;
                        o += (sin((p.y + z) * cf + vec4(0.0, 1.0, 2.0, 3.0)) + 1.0) / d;
                    }

                    o = tanh4(o * o * (uGlow * uBloom) / 1e5);

                    vec3 col = o.rgb;
                    float n = rand(gl_FragCoord.xy + vec2(iTime));
                    col += (n - 0.5) * uNoise;
                    col = clamp(col, 0.0, 1.0);

                    float L = dot(col, vec3(0.2126, 0.7152, 0.0722));
                    col = clamp(mix(vec3(L), col, uSaturation), 0.0, 1.0);

                    if(abs(uHueShift) > 0.0001){
                        col = clamp(hueRotation(uHueShift) * col, 0.0, 1.0);
                    }

                    gl_FragColor = vec4(col, o.a);
                }
            `;

            var geometry = new Triangle(gl);
            var iResBuf = new Float32Array(2);
            var offsetPxBuf = new Float32Array(2);

            var program = new Program(gl, {
                vertex: vertex,
                fragment: fragment,
                uniforms: {
                    iResolution: { value: iResBuf },
                    iTime: { value: 0 },
                    uHeight: { value: H },
                    uBaseHalf: { value: BASE_HALF },
                    uUseBaseWobble: { value: 1 },
                    uRot: { value: new Float32Array([1, 0, 0, 0, 1, 0, 0, 0, 1]) },
                    uGlow: { value: GLOW },
                    uOffsetPx: { value: offsetPxBuf },
                    uNoise: { value: NOISE },
                    uSaturation: { value: SAT },
                    uScale: { value: SCALE },
                    uHueShift: { value: HUE },
                    uColorFreq: { value: CFREQ },
                    uBloom: { value: BLOOM },
                    uCenterShift: { value: H * 0.25 },
                    uInvBaseHalf: { value: 1 / BASE_HALF },
                    uInvHeight: { value: 1 / H },
                    uMinAxis: { value: Math.min(BASE_HALF, H) },
                    uPxScale: {
                        value: 1 / ((gl.drawingBufferHeight || 1) * 0.1 * SCALE)
                    },
                    uTimeScale: { value: TS }
                }
            });
            var mesh = new Mesh(gl, { geometry: geometry, program: program });

            var self = this;

            var resize = function() {
                var w = container.clientWidth || 1;
                var h = container.clientHeight || 1;
                renderer.setSize(w, h);
                iResBuf[0] = gl.drawingBufferWidth;
                iResBuf[1] = gl.drawingBufferHeight;
                offsetPxBuf[0] = offX * dpr;
                offsetPxBuf[1] = offY * dpr;
                program.uniforms.uPxScale.value = 1 / ((gl.drawingBufferHeight || 1) * 0.1 * SCALE);
            };
            this.resizeObserver = new ResizeObserver(resize);
            this.resizeObserver.observe(container);
            resize();

            var rotBuf = new Float32Array(9);
            var setMat3FromEuler = function(yawY, pitchX, rollZ, out) {
                var cy = Math.cos(yawY), sy = Math.sin(yawY);
                var cx = Math.cos(pitchX), sx = Math.sin(pitchX);
                var cz = Math.cos(rollZ), sz = Math.sin(rollZ);
                var r00 = cy * cz + sy * sx * sz;
                var r01 = -cy * sz + sy * sx * cz;
                var r02 = sy * cx;
                var r10 = cx * sz;
                var r11 = cx * cz;
                var r12 = -sx;
                var r20 = -sy * cz + cy * sx * sz;
                var r21 = sy * sz + cy * sx * cz;
                var r22 = cy * cx;
                out[0] = r00; out[1] = r10; out[2] = r20;
                out[3] = r01; out[4] = r11; out[5] = r21;
                out[6] = r02; out[7] = r12; out[8] = r22;
                return out;
            };

            var NOISE_IS_ZERO = NOISE < 1e-6;
            var t0 = performance.now();

            var startRAF = function() {
                if (self.animationId) return;
                self.animationId = requestAnimationFrame(render);
            };
            var stopRAF = function() {
                if (!self.animationId) return;
                cancelAnimationFrame(self.animationId);
                self.animationId = 0;
            };

            var rnd = function() { return Math.random(); };
            var wX = (0.3 + rnd() * 0.6) * RSX;
            var wY = (0.2 + rnd() * 0.7) * RSY;
            var wZ = (0.1 + rnd() * 0.5) * RSZ;
            var phX = rnd() * Math.PI * 2;
            var phZ = rnd() * Math.PI * 2;

            var yaw = 0, pitch = 0, roll = 0;
            var targetYaw = 0, targetPitch = 0;
            var lerp = function(a, b, t) { return a + (b - a) * t; };

            var pointer = { x: 0, y: 0, inside: true };
            var onMove = function(e) {
                var ww = Math.max(1, window.innerWidth);
                var wh = Math.max(1, window.innerHeight);
                var cx = ww * 0.5;
                var cy = wh * 0.5;
                var nx = (e.clientX - cx) / (ww * 0.5);
                var ny = (e.clientY - cy) / (wh * 0.5);
                pointer.x = Math.max(-1, Math.min(1, nx));
                pointer.y = Math.max(-1, Math.min(1, ny));
                pointer.inside = true;
            };
            this.onLeave = function() { pointer.inside = false; };
            this.onBlur = function() { pointer.inside = false; };

            if (opts.animationType === 'hover') {
                this.onPointerMove = function(e) {
                    onMove(e);
                    startRAF();
                };
                window.addEventListener('pointermove', this.onPointerMove, { passive: true });
                window.addEventListener('mouseleave', this.onLeave);
                window.addEventListener('blur', this.onBlur);
                program.uniforms.uUseBaseWobble.value = 0;
            } else if (opts.animationType === '3drotate') {
                program.uniforms.uUseBaseWobble.value = 0;
            } else {
                program.uniforms.uUseBaseWobble.value = 1;
            }

            var render = function(t) {
                var time = (t - t0) * 0.001;
                program.uniforms.iTime.value = time;

                var continueRAF = true;

                if (opts.animationType === 'hover') {
                    var maxPitch = 0.6 * HOVSTR;
                    var maxYaw = 0.6 * HOVSTR;
                    targetYaw = (pointer.inside ? -pointer.x : 0) * maxYaw;
                    targetPitch = (pointer.inside ? pointer.y : 0) * maxPitch;
                    var prevYaw = yaw;
                    var prevPitch = pitch;
                    var prevRoll = roll;
                    yaw = lerp(prevYaw, targetYaw, INERT);
                    pitch = lerp(prevPitch, targetPitch, INERT);
                    roll = lerp(prevRoll, 0, 0.1);
                    program.uniforms.uRot.value = setMat3FromEuler(yaw, pitch, roll, rotBuf);

                    if (NOISE_IS_ZERO) {
                        var settled =
                            Math.abs(yaw - targetYaw) < 1e-4 &&
                            Math.abs(pitch - targetPitch) < 1e-4 &&
                            Math.abs(roll) < 1e-4;
                        if (settled) continueRAF = false;
                    }
                } else if (opts.animationType === '3drotate') {
                    var tScaled = time * TS;
                    yaw = tScaled * wY;
                    pitch = Math.sin(tScaled * wX + phX) * 0.6;
                    roll = Math.sin(tScaled * wZ + phZ) * 0.5;
                    program.uniforms.uRot.value = setMat3FromEuler(yaw, pitch, roll, rotBuf);
                    if (TS < 1e-6) continueRAF = false;
                } else {
                    rotBuf[0] = 1; rotBuf[1] = 0; rotBuf[2] = 0;
                    rotBuf[3] = 0; rotBuf[4] = 1; rotBuf[5] = 0;
                    rotBuf[6] = 0; rotBuf[7] = 0; rotBuf[8] = 1;
                    program.uniforms.uRot.value = rotBuf;
                    if (TS < 1e-6) continueRAF = false;
                }

                renderer.render({ scene: mesh });
                if (continueRAF) {
                    self.animationId = requestAnimationFrame(render);
                } else {
                    self.animationId = 0;
                }
            };

            if (opts.suspendWhenOffscreen) {
                this.intersectionObserver = new IntersectionObserver(function(entries) {
                    var vis = entries.some(function(e) { return e.isIntersecting; });
                    if (vis) startRAF();
                    else stopRAF();
                });
                this.intersectionObserver.observe(container);
                startRAF();
            } else {
                startRAF();
            }
        },

        destroy() {
            if (this.animationId) {
                cancelAnimationFrame(this.animationId);
                this.animationId = 0;
            }
            if (this.resizeObserver) {
                this.resizeObserver.disconnect();
                this.resizeObserver = null;
            }
            if (this.intersectionObserver) {
                this.intersectionObserver.disconnect();
                this.intersectionObserver = null;
            }
            if (this.options.animationType === 'hover') {
                if (this.onPointerMove) window.removeEventListener('pointermove', this.onPointerMove);
                if (this.onLeave) window.removeEventListener('mouseleave', this.onLeave);
                if (this.onBlur) window.removeEventListener('blur', this.onBlur);
            }
            if (this.canvas && this.canvas.parentElement === this.$el) {
                this.$el.removeChild(this.canvas);
            }
            this.canvas = null;
            this.gl = null;
        }
    };
};
</script>
@endonce
