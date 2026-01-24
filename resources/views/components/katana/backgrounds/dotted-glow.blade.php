@props([
    'gap' => 12,
    'radius' => 2,
    'color' => '#000000',
    'darkColor' => '#9ca3af',
    'glowColor' => 'rgba(0, 170, 255, 0.85)',
    'darkGlowColor' => 'rgba(148, 163, 184, 0.9)',
    'colorLightVar' => null,
    'colorDarkVar' => null,
    'glowColorLightVar' => null,
    'glowColorDarkVar' => null,
    'opacity' => 1,
    'backgroundOpacity' => 0,
    'speedMin' => 0.4,
    'speedMax' => 1.3,
    'speedScale' => 1,
    'mask' => 0,
    'maskColor' => '#ffffff',
    'darkMaskColor' => '#000000',
])

@php
$config = [
    'gap' => $gap,
    'radius' => $radius,
    'color' => $color,
    'darkColor' => $darkColor,
    'glowColor' => $glowColor,
    'darkGlowColor' => $darkGlowColor,
    'colorLightVar' => $colorLightVar,
    'colorDarkVar' => $colorDarkVar,
    'glowColorLightVar' => $glowColorLightVar,
    'glowColorDarkVar' => $glowColorDarkVar,
    'opacity' => $opacity,
    'backgroundOpacity' => $backgroundOpacity,
    'speedMin' => $speedMin,
    'speedMax' => $speedMax,
    'speedScale' => $speedScale,
    'mask' => $mask,
    'maskColor' => $maskColor,
    'darkMaskColor' => $darkMaskColor,
];
@endphp
<div
    x-data="dottedGlowComponent()"
    x-init="initDottedGlow($el, $refs.canvas, {{ Js::from($config) }})"
    {{ $attributes->twMerge('absolute inset-0') }}>
    <div class="w-full h-full absolute inset-0" style="background: radial-gradient(circle at center, transparent 0%, white 70%);"></div>
    
    <canvas x-ref="canvas" class="block w-full h-full"></canvas>
</div>

@once
<script>
window.dottedGlowComponent = function() {
    return {
        instance: null,
        initDottedGlow: function(el, canvas, config) {
            var self = this;
            var tryInit = function(retries) {
                if (window.initDottedGlow) {
                    self.instance = window.initDottedGlow(el, canvas, config);
                } else if (retries < 20) {
                    setTimeout(function() { tryInit(retries + 1); }, 50);
                }
            };
            this.$nextTick(function() { tryInit(0); });
        },
        destroy: function() {
            if (this.instance && this.instance.destroy) {
                this.instance.destroy();
            }
        }
    };
};
if (!window.initDottedGlow) {
    window.initDottedGlow = function(container, canvas, options) {
        var config = Object.assign({
            gap: 12,
            radius: 2,
            color: '#000000',
            darkColor: '#9ca3af',
            glowColor: 'rgba(0, 170, 255, 0.85)',
            darkGlowColor: 'rgba(148, 163, 184, 0.9)',
            colorLightVar: null,
            colorDarkVar: null,
            glowColorLightVar: null,
            glowColorDarkVar: null,
            opacity: 1,
            backgroundOpacity: 0,
            speedMin: 0.4,
            speedMax: 1.3,
            speedScale: 1,
            mask: 0,
            maskColor: '#ffffff',
            darkMaskColor: '#000000'
        }, options);

        var ctx = canvas.getContext('2d');
        var raf = 0;
        var stopped = false;
        var dots = [];
        var resizeObserver = null;
        var colorObserver = null;
        var mediaQuery = null;
        var mediaQueryHandler = null;
        var resolvedColor = config.color;
        var resolvedGlowColor = config.glowColor;
        var resolvedMaskColor = config.maskColor;

        function detectDarkMode() {
            var root = document.documentElement;
            if (root.classList.contains('dark')) return true;
            if (root.classList.contains('light')) return false;
            return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
        }

        function resolveCssVariable(variableName) {
            if (!variableName) return null;
            var normalized = variableName.startsWith('--') ? variableName : ('--' + variableName);
            var fromEl = getComputedStyle(container).getPropertyValue(normalized).trim();
            if (fromEl) return fromEl;
            var root = document.documentElement;
            var fromRoot = getComputedStyle(root).getPropertyValue(normalized).trim();
            return fromRoot || null;
        }

        function computeColors() {
            var isDark = detectDarkMode();
            var nextColor = config.color;
            var nextGlow = config.glowColor;
            var nextMask = config.maskColor;

            if (isDark) {
                var varDot = resolveCssVariable(config.colorDarkVar);
                var varGlow = resolveCssVariable(config.glowColorDarkVar);
                nextColor = varDot || config.darkColor || nextColor;
                nextGlow = varGlow || config.darkGlowColor || nextGlow;
                nextMask = config.darkMaskColor || nextMask;
            } else {
                var varDot = resolveCssVariable(config.colorLightVar);
                var varGlow = resolveCssVariable(config.glowColorLightVar);
                nextColor = varDot || nextColor;
                nextGlow = varGlow || nextGlow;
            }

            resolvedColor = nextColor;
            resolvedGlowColor = nextGlow;
            resolvedMaskColor = nextMask;
        }

        function setupColorObservers() {
            mediaQuery = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;
            mediaQueryHandler = function() { computeColors(); };
            if (mediaQuery && mediaQuery.addEventListener) {
                mediaQuery.addEventListener('change', mediaQueryHandler);
            }
            colorObserver = new MutationObserver(function() { computeColors(); });
            colorObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['class', 'style'] });
        }

        function resize() {
            var dpr = Math.max(1, window.devicePixelRatio || 1);
            var rect = container.getBoundingClientRect();
            canvas.width = Math.max(1, Math.floor(rect.width * dpr));
            canvas.height = Math.max(1, Math.floor(rect.height * dpr));
            canvas.style.width = Math.floor(rect.width) + 'px';
            canvas.style.height = Math.floor(rect.height) + 'px';
            ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
        }

        function setupResizeObserver() {
            resizeObserver = new ResizeObserver(function() {
                resize();
                regenDots();
            });
            resizeObserver.observe(container);
        }

        function regenDots() {
            dots = [];
            var rect = container.getBoundingClientRect();
            var cols = Math.ceil(rect.width / config.gap) + 2;
            var rows = Math.ceil(rect.height / config.gap) + 2;
            var min = Math.min(config.speedMin, config.speedMax);
            var max = Math.max(config.speedMin, config.speedMax);
            var span = Math.max(max - min, 0);

            for (var i = -1; i < cols; i++) {
                for (var j = -1; j < rows; j++) {
                    var x = i * config.gap + (j % 2 === 0 ? 0 : config.gap * 0.5);
                    var y = j * config.gap;
                    var phase = Math.random() * Math.PI * 2;
                    var speed = min + Math.random() * span;
                    dots.push({ x: x, y: y, phase: phase, speed: speed });
                }
            }
        }

        function draw(now) {
            if (stopped) return;

            var rect = container.getBoundingClientRect();
            var width = rect.width;
            var height = rect.height;

            ctx.clearRect(0, 0, canvas.width, canvas.height);
            ctx.globalAlpha = config.opacity;

            if (config.backgroundOpacity > 0) {
                var grad = ctx.createRadialGradient(
                    width * 0.5, height * 0.4, Math.min(width, height) * 0.1,
                    width * 0.5, height * 0.5, Math.max(width, height) * 0.7
                );
                grad.addColorStop(0, 'rgba(0,0,0,0)');
                grad.addColorStop(1, 'rgba(0,0,0,' + Math.min(Math.max(config.backgroundOpacity, 0), 1) + ')');
                ctx.fillStyle = grad;
                ctx.fillRect(0, 0, width, height);
            }

            ctx.save();
            ctx.fillStyle = resolvedColor;

            var time = (now / 1000) * Math.max(config.speedScale, 0);

            for (var i = 0; i < dots.length; i++) {
                var d = dots[i];
                var mod = (time * d.speed + d.phase) % 2;
                var lin = mod < 1 ? mod : 2 - mod;
                var a = 0.25 + 0.55 * lin;

                if (a > 0.6) {
                    var glow = (a - 0.6) / 0.4;
                    ctx.shadowColor = resolvedGlowColor;
                    ctx.shadowBlur = 6 * glow;
                } else {
                    ctx.shadowColor = 'transparent';
                    ctx.shadowBlur = 0;
                }

                ctx.globalAlpha = a * config.opacity;
                ctx.beginPath();
                ctx.arc(d.x, d.y, config.radius, 0, Math.PI * 2);
                ctx.fill();
            }

            ctx.restore();

            // Draw radial mask/vignette (transparent center, solid edges)
            if (config.mask > 0) {
                var maskGrad = ctx.createRadialGradient(
                    width * 0.5, height * 0.5, 0,
                    width * 0.5, height * 0.5, Math.max(width, height) * 0.7
                );
                maskGrad.addColorStop(0, 'transparent');
                maskGrad.addColorStop(0.4, 'transparent');
                maskGrad.addColorStop(1, resolvedMaskColor);
                ctx.globalAlpha = Math.min(Math.max(config.mask, 0), 1);
                ctx.fillStyle = maskGrad;
                ctx.fillRect(0, 0, width, height);
            }

            raf = requestAnimationFrame(draw);
        }

        function destroy() {
            stopped = true;
            if (raf) cancelAnimationFrame(raf);
            if (resizeObserver) resizeObserver.disconnect();
            if (colorObserver) colorObserver.disconnect();
            if (mediaQuery && mediaQueryHandler && mediaQuery.removeEventListener) {
                mediaQuery.removeEventListener('change', mediaQueryHandler);
            }
        }

        function init() {
            var rect = container.getBoundingClientRect();
            // If container has no dimensions yet, wait for next frame
            if (rect.width === 0 || rect.height === 0) {
                requestAnimationFrame(init);
                return;
            }
            computeColors();
            setupColorObservers();
            resize();
            regenDots();
            setupResizeObserver();
            raf = requestAnimationFrame(draw);
        }

        // Start initialization
        init();

        return { destroy: destroy };
    };
}
</script>
@endonce
