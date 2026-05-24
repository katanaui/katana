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
        _splitRetries: 0,
        initailizeSplitter(){
            // Split.js loads from CDN via the asset tag at the bottom of this
            // file. On a wire:navigate transition the asset is injected into
            // the DOM but loads async, while Alpine fires this directive
            // synchronously — meaning the first call can run before
            // window.Split exists. Poll briefly until it does (or give up
            // after ~5s), then proceed. Hard reload never hits this path
            // because the asset is cached and parsed before Alpine boots.
            // NB. Do not include the literal characters s-c-r-i-p-t (or
            // s-t-y-l-e) wrapped in angle brackets anywhere in this comment
            // — Livewire's multi-root detector regex-strips those tag pairs
            // before counting roots, and a stray substring inside this
            // attribute value will match the next real closing tag and
            // tear chunks of the rendered DOM out with it.
            if (typeof window.Split === 'undefined') {
                this._splitRetries = (this._splitRetries || 0) + 1;
                if (this._splitRetries < 100) {
                    return setTimeout(() => this.initailizeSplitter(), 50);
                }
                console.warn('Split.js unavailable after retry; splitter init aborted.');
                return;
            }
            this._splitRetries = 0;

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
    {{-- Split.js is now bundled via resources/js/app.js so window.Split is
         available synchronously before Alpine fires. The retry guard in the
         x-data block above stays as a safety net in case the bundle is ever
         removed or fails to evaluate before this directive runs. --}}
    <style>
        .split {
            display: flex;
        }
        .split.flex-col {
            flex-direction: column;
        }
        /*
         * Gutter: a comfortably-wide invisible hit zone with a hairline 1px
         * line painted in the middle. The gutter ELEMENT is transparent and
         * sized to {{ $gutterSize }}px (Split.js relies on that for pane
         * math). The visible line is a centered ::after — 1px wide at rest,
         * 3px on hover (still discreet, but obviously grabbable), 3px in
         * accent blue while dragging. ::before extends the hit area 6px
         * beyond the gutter on each side so even with the visible line
         * down at 1px the click target is ~{{ $gutterSize + 12 }}px wide.
         *
         * The "wider on hover" effect uses transform: scaleX on ::after,
         * not a width change, so the visible line grows from its center
         * without nudging the panes — Split.js would otherwise see a
         * size delta and recompute. transform is paint-only.
         */
        .gutter {
            position: relative;
            z-index: 5;
            background: transparent;
        }
        .gutter::before,
        .gutter::after {
            content: '';
            position: absolute;
        }
        .gutter::before {
            /* Invisible hit-area extender. pointer-events: auto so this
               area registers drag starts even though it has no visible
               fill of its own. */
            pointer-events: auto;
        }
        .gutter::after {
            /* The painted hairline. pointer-events: none so it never
               steals drag events from ::before / the gutter element. */
            pointer-events: none;
            background-color: rgb(228 228 231); /* zinc-200 */
            transform-origin: center center;
            transition:
                background-color 150ms ease,
                transform 150ms ease;
        }

        /* ── Horizontal: vertical seam between side-by-side panes ── */
        .gutter.gutter-horizontal {
            cursor: col-resize;
            width: {{ $gutterSize }}px;
        }
        .gutter.gutter-horizontal::before {
            top: 0;
            bottom: 0;
            left: -6px;
            right: -6px;
        }
        .gutter.gutter-horizontal::after {
            top: 0;
            bottom: 0;
            left: 50%;
            width: 1px;
            transform: translateX(-50%);
        }
        .gutter.gutter-horizontal:hover::after {
            transform: translateX(-50%) scaleX(3);
            background-color: rgb(161 161 170); /* zinc-400 */
        }
        .gutter.gutter-horizontal.gutter-dragging::after {
            transform: translateX(-50%) scaleX(3);
            background-color: rgb(59 130 246); /* blue-500 */
        }

        /* ── Vertical: horizontal seam between stacked panes ── */
        .gutter.gutter-vertical {
            cursor: row-resize;
            height: {{ $gutterSize }}px;
        }
        .gutter.gutter-vertical::before {
            left: 0;
            right: 0;
            top: -6px;
            bottom: -6px;
        }
        .gutter.gutter-vertical::after {
            left: 0;
            right: 0;
            top: 50%;
            height: 1px;
            transform: translateY(-50%);
        }
        .gutter.gutter-vertical:hover::after {
            transform: translateY(-50%) scaleY(3);
            background-color: rgb(161 161 170); /* zinc-400 */
        }
        .gutter.gutter-vertical.gutter-dragging::after {
            transform: translateY(-50%) scaleY(3);
            background-color: rgb(59 130 246); /* blue-500 */
        }

        /* While a gutter is being dragged, suppress text selection and keep
           the resize cursor even when the pointer briefly leaves the gutter. */
        .gutter-dragging-body { user-select: none; cursor: col-resize; }
        .gutter-dragging-body .split.flex-col ~ *,
        .gutter-dragging-body.flex-col .gutter-vertical ~ * { cursor: row-resize; }

        /* Dark mode: lift the hairline to zinc-700 so it sits readably on
           the dark editor canvas; hover/drag colors hold up in both modes. */
        @media (prefers-color-scheme: dark) {
            .gutter::after { background-color: rgb(63 63 70); /* zinc-700 */ }
            .gutter:hover::after { background-color: rgb(113 113 122); /* zinc-500 */ }
            .gutter.gutter-dragging::after { background-color: rgb(96 165 250); /* blue-400 */ }
        }
        .dark .gutter::after { background-color: rgb(63 63 70); }
        .dark .gutter:hover::after { background-color: rgb(113 113 122); }
        .dark .gutter.gutter-dragging::after { background-color: rgb(96 165 250); }
    </style>
@endonce
