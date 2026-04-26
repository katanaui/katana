@props([
    'baseColor' => [0.1, 0.1, 0.1],
    'speed' => 0.2,
    'amplitude' => 0.3,
    'frequencyX' => 3,
    'frequencyY' => 3,
    'interactive' => true,
])

@php
$config = [
    'baseColor' => array_values((array) $baseColor),
    'speed' => (float) $speed,
    'amplitude' => (float) $amplitude,
    'frequencyX' => (float) $frequencyX,
    'frequencyY' => (float) $frequencyY,
    'interactive' => (bool) $interactive,
];
@endphp

<div
    x-data="liquidChromeComponent({{ Js::from($config) }})"
    x-init="init()"
    x-destroy="destroy()"
    {{ $attributes->twMerge('w-full h-full') }}>
</div>

@once
<script>
window.liquidChromeComponent = function(options) {
    return {
        options: options,
        renderer: null,
        gl: null,
        program: null,
        mesh: null,
        animationId: null,
        resizeHandler: null,
        mouseHandler: null,
        touchHandler: null,

        async init() {
            try {
                if (!window.OGL) {
                    window.OGL = await import('https://esm.sh/ogl@1.0.11');
                }
                this.$nextTick(() => this.setup());
            } catch (error) {
                console.error('Failed to load OGL library for liquid-chrome:', error);
            }
        },

        setup() {
            var container = this.$el;
            var OGL = window.OGL;
            var Renderer = OGL.Renderer;
            var Program = OGL.Program;
            var Mesh = OGL.Mesh;
            var Triangle = OGL.Triangle;

            var renderer = new Renderer({ antialias: true });
            this.renderer = renderer;
            var gl = renderer.gl;
            this.gl = gl;
            gl.clearColor(1, 1, 1, 1);

            var vertexShader = '\n' +
                '                attribute vec2 position;\n' +
                '                attribute vec2 uv;\n' +
                '                varying vec2 vUv;\n' +
                '                void main() {\n' +
                '                    vUv = uv;\n' +
                '                    gl_Position = vec4(position, 0.0, 1.0);\n' +
                '                }\n';

            var fragmentShader = '\n' +
                '                precision highp float;\n' +
                '                uniform float uTime;\n' +
                '                uniform vec3 uResolution;\n' +
                '                uniform vec3 uBaseColor;\n' +
                '                uniform float uAmplitude;\n' +
                '                uniform float uFrequencyX;\n' +
                '                uniform float uFrequencyY;\n' +
                '                uniform vec2 uMouse;\n' +
                '                varying vec2 vUv;\n' +
                '\n' +
                '                vec4 renderImage(vec2 uvCoord) {\n' +
                '                    vec2 fragCoord = uvCoord * uResolution.xy;\n' +
                '                    vec2 uv = (2.0 * fragCoord - uResolution.xy) / min(uResolution.x, uResolution.y);\n' +
                '\n' +
                '                    for (float i = 1.0; i < 10.0; i++){\n' +
                '                        uv.x += uAmplitude / i * cos(i * uFrequencyX * uv.y + uTime + uMouse.x * 3.14159);\n' +
                '                        uv.y += uAmplitude / i * cos(i * uFrequencyY * uv.x + uTime + uMouse.y * 3.14159);\n' +
                '                    }\n' +
                '\n' +
                '                    vec2 diff = (uvCoord - uMouse);\n' +
                '                    float dist = length(diff);\n' +
                '                    float falloff = exp(-dist * 20.0);\n' +
                '                    float ripple = sin(10.0 * dist - uTime * 2.0) * 0.03;\n' +
                '                    uv += (diff / (dist + 0.0001)) * ripple * falloff;\n' +
                '\n' +
                '                    vec3 color = uBaseColor / abs(sin(uTime - uv.y - uv.x));\n' +
                '                    return vec4(color, 1.0);\n' +
                '                }\n' +
                '\n' +
                '                void main() {\n' +
                '                    vec4 col = vec4(0.0);\n' +
                '                    int samples = 0;\n' +
                '                    for (int i = -1; i <= 1; i++){\n' +
                '                        for (int j = -1; j <= 1; j++){\n' +
                '                            vec2 offset = vec2(float(i), float(j)) * (1.0 / min(uResolution.x, uResolution.y));\n' +
                '                            col += renderImage(vUv + offset);\n' +
                '                            samples++;\n' +
                '                        }\n' +
                '                    }\n' +
                '                    gl_FragColor = col / float(samples);\n' +
                '                }\n';

            var geometry = new Triangle(gl);
            var program = new Program(gl, {
                vertex: vertexShader,
                fragment: fragmentShader,
                uniforms: {
                    uTime: { value: 0 },
                    uResolution: {
                        value: new Float32Array([
                            gl.canvas.width,
                            gl.canvas.height,
                            gl.canvas.width / gl.canvas.height
                        ])
                    },
                    uBaseColor: { value: new Float32Array(this.options.baseColor) },
                    uAmplitude: { value: this.options.amplitude },
                    uFrequencyX: { value: this.options.frequencyX },
                    uFrequencyY: { value: this.options.frequencyY },
                    uMouse: { value: new Float32Array([0, 0]) }
                }
            });
            this.program = program;
            var mesh = new Mesh(gl, { geometry: geometry, program: program });
            this.mesh = mesh;

            var self = this;

            this.resizeHandler = function() {
                renderer.setSize(container.offsetWidth, container.offsetHeight);
                var resUniform = program.uniforms.uResolution.value;
                resUniform[0] = gl.canvas.width;
                resUniform[1] = gl.canvas.height;
                resUniform[2] = gl.canvas.width / gl.canvas.height;
            };
            window.addEventListener('resize', this.resizeHandler);
            this.resizeHandler();

            if (this.options.interactive) {
                this.mouseHandler = function(event) {
                    var rect = container.getBoundingClientRect();
                    var x = (event.clientX - rect.left) / rect.width;
                    var y = 1 - (event.clientY - rect.top) / rect.height;
                    var mouseUniform = program.uniforms.uMouse.value;
                    mouseUniform[0] = x;
                    mouseUniform[1] = y;
                };
                this.touchHandler = function(event) {
                    if (event.touches.length > 0) {
                        var touch = event.touches[0];
                        var rect = container.getBoundingClientRect();
                        var x = (touch.clientX - rect.left) / rect.width;
                        var y = 1 - (touch.clientY - rect.top) / rect.height;
                        var mouseUniform = program.uniforms.uMouse.value;
                        mouseUniform[0] = x;
                        mouseUniform[1] = y;
                    }
                };
                container.addEventListener('mousemove', this.mouseHandler);
                container.addEventListener('touchmove', this.touchHandler);
            }

            var update = function(t) {
                self.animationId = requestAnimationFrame(update);
                program.uniforms.uTime.value = t * 0.001 * self.options.speed;
                renderer.render({ scene: mesh });
            };
            this.animationId = requestAnimationFrame(update);

            container.appendChild(gl.canvas);
        },

        destroy() {
            if (this.animationId) cancelAnimationFrame(this.animationId);
            if (this.resizeHandler) window.removeEventListener('resize', this.resizeHandler);
            var container = this.$el;
            if (this.options.interactive && container) {
                if (this.mouseHandler) container.removeEventListener('mousemove', this.mouseHandler);
                if (this.touchHandler) container.removeEventListener('touchmove', this.touchHandler);
            }
            if (this.gl) {
                if (this.gl.canvas && this.gl.canvas.parentElement) {
                    this.gl.canvas.parentElement.removeChild(this.gl.canvas);
                }
                var loseCtx = this.gl.getExtension('WEBGL_lose_context');
                if (loseCtx) loseCtx.loseContext();
            }
        }
    };
};
</script>
@endonce
