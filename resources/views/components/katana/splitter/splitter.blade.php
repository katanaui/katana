@props([
    'direction' => 'horizontal',
    'gutterSize' => 8,
    'minSize' => 100,
])

@php
    $baseClasses = 'flex w-full h-full items-stretch justify-stretch';
@endphp

<div
    x-data="{
        splitInstance: null,
        direction: '{{ $direction }}',
        initailizeSplitter(){
            const panes = [...$el.querySelectorAll(':scope > .katana-split-pane')]
            if (panes.length < 2) return

            const sizes = panes.map(p => parseFloat(p.dataset.size) || (100 / panes.length))

            // Tear down any prior instance so re-init swaps orientations cleanly.
            if (this.splitInstance) {
                try { this.splitInstance.destroy(); } catch (e) {}
                this.splitInstance = null;
            }
            // Strip inline sizes Split.js may have left on the panes.
            panes.forEach(p => { p.style.removeProperty('width'); p.style.removeProperty('height'); });
            // Drop any stray gutters Split.js missed.
            $el.querySelectorAll(':scope > .gutter').forEach(g => g.remove());

            this.splitInstance = Split(panes, {
                direction: this.direction,
                sizes: sizes,
                gutterSize: {{ $gutterSize }},
                minSize: {{ $minSize }},
                gutter: (index, direction) => {
                    const gutter = document.createElement('div')
                    // add wire:ignore to gutter element
                    gutter.setAttribute('wire:ignore', '')
                    gutter.className = `gutter gutter-${direction}`
                    return gutter
                },
                onDragStart: (sizes, gutter) => {
                    if (gutter && gutter.classList) gutter.classList.add('gutter-dragging')
                    document.body.classList.add('gutter-dragging-body')
                },
                onDragEnd: (sizes, gutter) => {
                    if (gutter && gutter.classList) gutter.classList.remove('gutter-dragging')
                    document.body.classList.remove('gutter-dragging-body')
                }
            })
        }
    }"
    x-init="
        initailizeSplitter()
    "
    @splitter-init.window="initailizeSplitter()"
    @splitter-set-direction.window="
        const d = $event.detail?.direction;
        if ((d === 'horizontal' || d === 'vertical') && d !== direction) {
            direction = d;
            $nextTick(() => initailizeSplitter());
        }
    "
    :class="direction === 'vertical' ? 'flex-col' : ''"
    {{ $attributes->twMerge($baseClasses) }}
    wire:ignore.self
>
    {{ $slot }}

</div>

@once
    <script src="https://cdn.jsdelivr.net/npm/split.js/dist/split.min.js"></script>
    <style>
        .split {
            display: flex;
        }
        .split.flex-col {
            flex-direction: column;
        }
        /*
         * Gutter: generous hit area (full gutterSize width/height) with a
         * restrained 1px hairline in the center. On hover / while dragging,
         * the hairline thickens to 2px and an accent pill appears — a clear
         * affordance that this seam is draggable, CodePen-style.
         */
        .gutter {
            position: relative;
            background-color: transparent;
            background-repeat: no-repeat;
            background-position: 50%;
            transition: background-color 150ms ease;
        }
        .gutter::before,
        .gutter::after {
            content: '';
            position: absolute;
            pointer-events: none;
            transition: background-color 150ms ease, opacity 150ms ease, width 150ms ease, height 150ms ease;
        }
        /* The hairline divider in the middle of the gutter. */
        .gutter::before {
            background-color: rgb(228 228 231); /* zinc-200 */
        }
        /* The grip pill — only visible on hover / drag. */
        .gutter::after {
            background-color: rgb(113 113 122); /* zinc-500 */
            border-radius: 9999px;
            opacity: 0;
        }
        .gutter:hover,
        .gutter.gutter-dragging {
            background-color: rgba(99, 102, 241, 0.04);
        }
        .gutter:hover::before,
        .gutter.gutter-dragging::before {
            background-color: rgb(99 102 241); /* indigo-500 */
        }
        .gutter:hover::after,
        .gutter.gutter-dragging::after {
            opacity: 1;
        }

        /* Horizontal (vertical seam between side-by-side panes). */
        .gutter.gutter-horizontal {
            cursor: col-resize;
            width: {{ $gutterSize }}px;
        }
        .gutter.gutter-horizontal::before {
            top: 0;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 1px;
        }
        .gutter.gutter-horizontal:hover::before,
        .gutter.gutter-horizontal.gutter-dragging::before {
            width: 2px;
        }
        .gutter.gutter-horizontal::after {
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 3px;
            height: 40px;
        }

        /* Vertical (horizontal seam between stacked panes). */
        .gutter.gutter-vertical {
            cursor: row-resize;
            height: {{ $gutterSize }}px;
        }
        .gutter.gutter-vertical::before {
            left: 0;
            right: 0;
            top: 50%;
            transform: translateY(-50%);
            height: 1px;
        }
        .gutter.gutter-vertical:hover::before,
        .gutter.gutter-vertical.gutter-dragging::before {
            height: 2px;
        }
        .gutter.gutter-vertical::after {
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            height: 3px;
            width: 40px;
        }

        /* While a gutter is being dragged, suppress text selection and keep
           the resize cursor even when the pointer briefly leaves the gutter. */
        .gutter-dragging-body { user-select: none; cursor: col-resize; }
        .gutter-dragging-body .split.flex-col ~ *,
        .gutter-dragging-body.flex-col .gutter-vertical ~ * { cursor: row-resize; }

        @media (prefers-color-scheme: dark) {
            .gutter::before { background-color: rgb(63 63 70); /* zinc-700 */ }
            .gutter:hover, .gutter.gutter-dragging { background-color: rgba(129, 140, 248, 0.06); }
            .gutter:hover::before, .gutter.gutter-dragging::before { background-color: rgb(129 140 248); /* indigo-400 */ }
            .gutter::after { background-color: rgb(161 161 170); /* zinc-400 */ }
        }
    </style>
@endonce
