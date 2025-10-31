@props([
    'beamWidth' => 2,
    'beamHeight' => 15,
    'beamNumber' => 12,
    'lightColor' => '#ffffff',
    'speed' => 2,
    'noiseIntensity' => 1.75,
    'scale' => 0.2,
    'rotation' => 0,
])

<div x-data="{
    beamsInstance: null,
    resizeHandler: null,
    resizeObserver: null,
    isLoading: true,
    maxRetries: 3,
    currentRetry: 0,

    async initBeams() {
        try {
            await this.waitForTHREE();
            await this.waitForShader();

            if (window.initBeamsShader) {
                this.beamsInstance = window.initBeamsShader($el, {
                    beamWidth: {{ $beamWidth }},
                    beamHeight: {{ $beamHeight }},
                    beamNumber: {{ $beamNumber }},
                    lightColor: '{{ $lightColor }}',
                    speed: {{ $speed }},
                    noiseIntensity: {{ $noiseIntensity }},
                    scale: {{ $scale }},
                    rotation: {{ $rotation }}
                });

                this.setupResizeHandlers();
                this.isLoading = false;
            }
        } catch (error) {
            console.error('Failed to initialize beams shader:', error);
            if (this.currentRetry < this.maxRetries) {
                this.currentRetry++;
                setTimeout(() => this.initBeams(), 1000 * this.currentRetry);
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
                if (window.initBeamsShader) {
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
            if (canvas && this.beamsInstance) {
                const rect = $el.getBoundingClientRect();
                canvas.width = rect.width;
                canvas.height = rect.height;
                this.beamsInstance.renderer.setSize(rect.width, rect.height);
                if (this.beamsInstance.camera) {
                    this.beamsInstance.camera.aspect = rect.width / rect.height;
                    this.beamsInstance.camera.updateProjectionMatrix();
                }
            }
        };

        window.addEventListener('resize', this.resizeHandler);

        this.resizeObserver = new ResizeObserver(() => {
            this.resizeHandler();
        });
        this.resizeObserver.observe($el);
    }
}" x-init="$nextTick(() => initBeams())" x-destroy="
        if (beamsInstance && beamsInstance.animationFrame) {
            cancelAnimationFrame(beamsInstance.animationFrame);
        }
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

        // Only define the beams shader function if it doesn't already exist
        if (!window.initBeamsShader) {
            window.initBeamsShader = function(container, options = {}) {
                const {
                    beamWidth = 2,
                    beamHeight = 15,
                    beamNumber = 12,
                    lightColor = '#ffffff',
                    speed = 2,
                    noiseIntensity = 1.75,
                    scale = 0.2,
                    rotation = 0
                } = options;

                const canvas = container.querySelector('canvas');
                const renderer = new window.THREE.WebGLRenderer({
                    canvas,
                    alpha: true,
                    antialias: true
                });
                renderer.setSize(canvas.clientWidth, canvas.clientHeight);
                renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));

                const scene = new window.THREE.Scene();
                scene.background = new window.THREE.Color('#000000');
                scene.fog = new window.THREE.Fog('#000000', 0, 1000);

                const camera = new window.THREE.PerspectiveCamera(
                    30,
                    canvas.clientWidth / canvas.clientHeight,
                    0.1,
                    1000
                );
                camera.position.set(0, 0, 20);

                // Noise function shader code (3D Perlin noise)
                const noiseShader = `
float random (in vec2 st) {
    return fract(sin(dot(st.xy, vec2(12.9898,78.233))) * 43758.5453123);
}
float noise (in vec2 st) {
    vec2 i = floor(st);
    vec2 f = fract(st);
    float a = random(i);
    float b = random(i + vec2(1.0, 0.0));
    float c = random(i + vec2(0.0, 1.0));
    float d = random(i + vec2(1.0, 1.0));
    vec2 u = f * f * (3.0 - 2.0 * f);
    return mix(a, b, u.x) + (c - a)* u.y * (1.0 - u.x) + (d - b) * u.x * u.y;
}
vec4 permute(vec4 x){return mod(((x*34.0)+1.0)*x, 289.0);}
vec4 taylorInvSqrt(vec4 r){return 1.79284291400159 - 0.85373472095314 * r;}
vec3 fade(vec3 t) {return t*t*t*(t*(t*6.0-15.0)+10.0);}
float cnoise(vec3 P){
  vec3 Pi0 = floor(P);
  vec3 Pi1 = Pi0 + vec3(1.0);
  Pi0 = mod(Pi0, 289.0);
  Pi1 = mod(Pi1, 289.0);
  vec3 Pf0 = fract(P);
  vec3 Pf1 = Pf0 - vec3(1.0);
  vec4 ix = vec4(Pi0.x, Pi1.x, Pi0.x, Pi1.x);
  vec4 iy = vec4(Pi0.yy, Pi1.yy);
  vec4 iz0 = Pi0.zzzz;
  vec4 iz1 = Pi1.zzzz;
  vec4 ixy = permute(permute(ix) + iy);
  vec4 ixy0 = permute(ixy + iz0);
  vec4 ixy1 = permute(ixy + iz1);
  vec4 gx0 = ixy0 / 7.0;
  vec4 gy0 = fract(floor(gx0) / 7.0) - 0.5;
  gx0 = fract(gx0);
  vec4 gz0 = vec4(0.5) - abs(gx0) - abs(gy0);
  vec4 sz0 = step(gz0, vec4(0.0));
  gx0 -= sz0 * (step(0.0, gx0) - 0.5);
  gy0 -= sz0 * (step(0.0, gy0) - 0.5);
  vec4 gx1 = ixy1 / 7.0;
  vec4 gy1 = fract(floor(gx1) / 7.0) - 0.5;
  gx1 = fract(gx1);
  vec4 gz1 = vec4(0.5) - abs(gx1) - abs(gy1);
  vec4 sz1 = step(gz1, vec4(0.0));
  gx1 -= sz1 * (step(0.0, gx1) - 0.5);
  gy1 -= sz1 * (step(0.0, gy1) - 0.5);
  vec3 g000 = vec3(gx0.x,gy0.x,gz0.x);
  vec3 g100 = vec3(gx0.y,gy0.y,gz0.y);
  vec3 g010 = vec3(gx0.z,gy0.z,gz0.z);
  vec3 g110 = vec3(gx0.w,gy0.w,gz0.w);
  vec3 g001 = vec3(gx1.x,gy1.x,gz1.x);
  vec3 g101 = vec3(gx1.y,gy1.y,gz1.y);
  vec3 g011 = vec3(gx1.z,gy1.z,gz1.z);
  vec3 g111 = vec3(gx1.w,gy1.w,gz1.w);
  vec4 norm0 = taylorInvSqrt(vec4(dot(g000,g000),dot(g010,g010),dot(g100,g100),dot(g110,g110)));
  g000 *= norm0.x; g010 *= norm0.y; g100 *= norm0.z; g110 *= norm0.w;
  vec4 norm1 = taylorInvSqrt(vec4(dot(g001,g001),dot(g011,g011),dot(g101,g101),dot(g111,g111)));
  g001 *= norm1.x; g011 *= norm1.y; g101 *= norm1.z; g111 *= norm1.w;
  float n000 = dot(g000, Pf0);
  float n100 = dot(g100, vec3(Pf1.x,Pf0.yz));
  float n010 = dot(g010, vec3(Pf0.x,Pf1.y,Pf0.z));
  float n110 = dot(g110, vec3(Pf1.xy,Pf0.z));
  float n001 = dot(g001, vec3(Pf0.xy,Pf1.z));
  float n101 = dot(g101, vec3(Pf1.x,Pf0.y,Pf1.z));
  float n011 = dot(g011, vec3(Pf0.x,Pf1.yz));
  float n111 = dot(g111, Pf1);
  vec3 fade_xyz = fade(Pf0);
  vec4 n_z = mix(vec4(n000,n100,n010,n110),vec4(n001,n101,n011,n111),fade_xyz.z);
  vec2 n_yz = mix(n_z.xy,n_z.zw,fade_xyz.y);
  float n_xyz = mix(n_yz.x,n_yz.y,fade_xyz.x);
  return 2.2 * n_xyz;
}
`;

                // Create stacked planes geometry
                function createStackedPlanesBufferGeometry(n, width, height, spacing, heightSegments) {
                    const geometry = new window.THREE.BufferGeometry();
                    const numVertices = n * (heightSegments + 1) * 2;
                    const numFaces = n * heightSegments * 2;
                    const positions = new Float32Array(numVertices * 3);
                    const indices = new Uint32Array(numFaces * 3);
                    const uvs = new Float32Array(numVertices * 2);

                    let vertexOffset = 0;
                    let indexOffset = 0;
                    let uvOffset = 0;
                    const totalWidth = n * width + (n - 1) * spacing;
                    const xOffsetBase = -totalWidth / 2;

                    for (let i = 0; i < n; i++) {
                        const xOffset = xOffsetBase + i * (width + spacing);
                        const uvXOffset = Math.random() * 300;
                        const uvYOffset = Math.random() * 300;

                        for (let j = 0; j <= heightSegments; j++) {
                            const y = height * (j / heightSegments - 0.5);
                            const v0 = [xOffset, y, 0];
                            const v1 = [xOffset + width, y, 0];
                            positions.set([...v0, ...v1], vertexOffset * 3);

                            const uvY = j / heightSegments;
                            uvs.set([uvXOffset, uvY + uvYOffset, uvXOffset + 1, uvY + uvYOffset], uvOffset);

                            if (j < heightSegments) {
                                const a = vertexOffset,
                                    b = vertexOffset + 1,
                                    c = vertexOffset + 2,
                                    d = vertexOffset + 3;
                                indices.set([a, b, c, c, b, d], indexOffset);
                                indexOffset += 6;
                            }
                            vertexOffset += 2;
                            uvOffset += 4;
                        }
                    }

                    geometry.setAttribute('position', new window.THREE.BufferAttribute(positions, 3));
                    geometry.setAttribute('uv', new window.THREE.BufferAttribute(uvs, 2));
                    geometry.setIndex(new window.THREE.BufferAttribute(indices, 1));
                    geometry.computeVertexNormals();
                    return geometry;
                }

                const geometry = createStackedPlanesBufferGeometry(beamNumber, beamWidth, beamHeight, 0, 100);

                // Convert hex color to RGB (normalized 0-1)
                function hexToNormalizedRGB(hex) {
                    const clean = hex.replace('#', '');
                    const r = parseInt(clean.substring(0, 2), 16) / 255;
                    const g = parseInt(clean.substring(2, 4), 16) / 255;
                    const b = parseInt(clean.substring(4, 6), 16) / 255;
                    return [r, g, b];
                }

                // Get the base physical shader
                const physical = window.THREE.ShaderLib.physical;
                const baseVert = physical.vertexShader;
                const baseFrag = physical.fragmentShader;
                const baseUniforms = window.THREE.UniformsUtils.clone(physical.uniforms);

                // Add custom uniforms
                baseUniforms.time = { value: 0 };
                baseUniforms.uSpeed = { value: speed };
                baseUniforms.uNoiseIntensity = { value: noiseIntensity };
                baseUniforms.uScale = { value: scale };

                // Set material properties
                baseUniforms.diffuse.value = new window.THREE.Color(...hexToNormalizedRGB('#000000'));
                baseUniforms.roughness.value = 0.3;
                baseUniforms.metalness.value = 0.3;
                baseUniforms.envMapIntensity.value = 10;

                // Modify vertex shader to include noise deformation
                const customVertexHeader = `
varying vec3 vEye;
varying float vNoise;
varying vec2 vUv2;
varying vec3 vPosition2;
uniform float time;
uniform float uSpeed;
uniform float uScale;

${noiseShader}

float getPos(vec3 pos, vec2 uv) {
    vec3 noisePos = vec3(pos.x * 0., pos.y - uv.y, pos.z + time * uSpeed * 3.) * uScale;
    return cnoise(noisePos);
}

vec3 getCurrentPos(vec3 pos, vec2 uv) {
    vec3 newpos = pos;
    newpos.z += getPos(pos, uv);
    return newpos;
}

vec3 getNormal(vec3 pos, vec2 uv) {
    vec3 curpos = getCurrentPos(pos, uv);
    vec3 nextposX = getCurrentPos(pos + vec3(0.01, 0.0, 0.0), uv);
    vec3 nextposZ = getCurrentPos(pos + vec3(0.0, -0.01, 0.0), uv);
    vec3 tangentX = normalize(nextposX - curpos);
    vec3 tangentZ = normalize(nextposZ - curpos);
    return normalize(cross(tangentZ, tangentX));
}
`;

                let modifiedVert = customVertexHeader + '\n' + baseVert;

                // Replace the vertex transformation
                modifiedVert = modifiedVert.replace(
                    '#include <begin_vertex>',
                    `#include <begin_vertex>
                    vUv2 = uv;
                    vPosition2 = position;
                    transformed.z += getPos(transformed.xyz, uv);`
                );

                // Replace the normal calculation
                modifiedVert = modifiedVert.replace(
                    '#include <beginnormal_vertex>',
                    `#include <beginnormal_vertex>
                    objectNormal = getNormal(position.xyz, uv);`
                );

                // Modify fragment shader to add noise
                const customFragmentHeader = `
varying vec2 vUv2;
varying vec3 vPosition2;
uniform float uNoiseIntensity;

${noiseShader}
`;

                let modifiedFrag = customFragmentHeader + '\n' + baseFrag;

                // Add noise to final color
                modifiedFrag = modifiedFrag.replace(
                    '#include <dithering_fragment>',
                    `#include <dithering_fragment>
                    float randomNoise = noise(gl_FragCoord.xy);
                    gl_FragColor.rgb -= randomNoise / 15. * uNoiseIntensity;`
                );

                const material = new window.THREE.ShaderMaterial({
                    defines: { ...physical.defines },
                    uniforms: baseUniforms,
                    vertexShader: modifiedVert,
                    fragmentShader: modifiedFrag,
                    lights: true,
                    fog: true
                });

                const mesh = new window.THREE.Mesh(geometry, material);

                // Apply rotation
                const rotationRad = rotation * Math.PI / 180;
                const group = new window.THREE.Group();
                group.rotation.z = rotationRad;
                group.add(mesh);
                scene.add(group);

                // Add lighting
                const ambientLight = new window.THREE.AmbientLight(0xffffff, 1);
                scene.add(ambientLight);

                const directionalLight = new window.THREE.DirectionalLight(lightColor, 1);
                directionalLight.position.set(0, 3, 10);
                scene.add(directionalLight);

                let animationFrame;
                let lastTime = 0;
                let accumulatedTime = 0;

                function animate(time) {
                    // Convert time to seconds
                    const currentTime = time / 1000;
                    const delta = lastTime === 0 ? 0 : currentTime - lastTime;
                    lastTime = currentTime;

                    // Increment time by 0.1 * delta to match React Three Fiber's useFrame behavior
                    accumulatedTime += 0.1 * delta;
                    baseUniforms.time.value = accumulatedTime;

                    renderer.render(scene, camera);
                    animationFrame = requestAnimationFrame(animate);
                }

                animate(0);

                return {
                    renderer,
                    camera,
                    animationFrame
                };
            };
        }
    </script>
@endonce
