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
         * Gutter: the gutter element ITSELF is the visible divider — a thin
         * black line that doubles as the drag handle. Hover / drag turns it
         * blue. A transparent ::before extends the pointer hit area a few
         * pixels each side so the handle is comfortable to grab even when
         * the visible line is only 2px thin.
         */
        .gutter {
            position: relative;
            z-index: 5;
            background-color: rgb(10 10 10); /* near-black ink */
            transition: background-color 120ms ease;
        }
        .gutter::before {
            content: '';
            position: absolute;
            pointer-events: auto;
        }
        .gutter:hover,
        .gutter.gutter-dragging {
            background-color: rgb(59 130 246); /* blue-500 */
        }

        /* Horizontal (vertical seam between side-by-side panes). */
        .gutter.gutter-horizontal {
            cursor: col-resize;
            width: {{ $gutterSize }}px;
        }
        .gutter.gutter-horizontal::before {
            top: 0;
            bottom: 0;
            left: -3px;
            right: -3px;
        }

        /* Vertical (horizontal seam between stacked panes). */
        .gutter.gutter-vertical {
            cursor: row-resize;
            height: {{ $gutterSize }}px;
        }
        .gutter.gutter-vertical::before {
            left: 0;
            right: 0;
            top: -3px;
            bottom: -3px;
        }

        /* While a gutter is being dragged, suppress text selection and keep
           the resize cursor even when the pointer briefly leaves the gutter. */
        .gutter-dragging-body { user-select: none; cursor: col-resize; }
        .gutter-dragging-body .split.flex-col ~ *,
        .gutter-dragging-body.flex-col .gutter-vertical ~ * { cursor: row-resize; }

        /* Dark mode: flip the ink so the divider stays visible against the
           dark zinc backdrop. Hover stays blue in both modes. */
        @media (prefers-color-scheme: dark) {
            .gutter { background-color: rgb(244 244 245); /* zinc-100 */ }
            .gutter:hover, .gutter.gutter-dragging { background-color: rgb(96 165 250); /* blue-400 */ }
        }
        .dark .gutter { background-color: rgb(244 244 245); /* zinc-100 */ }
        .dark .gutter:hover,
        .dark .gutter.gutter-dragging { background-color: rgb(96 165 250); /* blue-400 */ }
    </style>
@endonce
