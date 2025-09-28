@props([
    'direction' => 'horizontal',
    'gutterSize' => 8,
    'minSize' => 100,
])

@php
    $classes = \Illuminate\Support\Arr::toCssClasses([
        'flex w-full h-full items-stretch justify-stretch',
        'flex-col' => $direction == 'vertical'
    ]);
@endphp

<div
    x-init="() => {
        const panes = [...$el.querySelectorAll('.katana-split-pane')]
        if (panes.length < 2) return

        const sizes = panes.map(p => parseFloat(p.dataset.size) || (100 / panes.length))

        Split(panes, {
            direction: '{{ $direction }}',
            sizes: sizes,
            gutterSize: {{ $gutterSize }},
            minSize: {{ $minSize }},
            gutter: (index, direction) => {
                const gutter = document.createElement('div')
                gutter.className = `gutter gutter-${direction}`
                return gutter
            }
        })
    }"
    {{ $attributes->twMerge($classes) }}
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
        .gutter {
            background-color: #ffffff;
            background-repeat: no-repeat;
            background-position: 50%;
        }
        /*.gutter:hover{
            background-color:#2c67f6;
        }*/
        .gutter.gutter-horizontal {
           /* background-image: url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAUAAAAeCAYAAADkftS9AAAAIklEQVQoU2M4c+bMfxAGAgYYmwGrIIiDjrELjpo5aiZeMwF+yNnOs5KSvgAAAABJRU5ErkJggg==');*/
            cursor: col-resize;
            width: {{ $gutterSize }}px;
        }
        .gutter.gutter-vertical {
            background-image: url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAB4AAAAFCAYAAABMtWY8AAAALUlEQVQoz2N4c+bMf0YGBgYG+iZkA2QKqIEqsGdoPZkJ6uHj4HkBa6cgFwAANn8IddZTTbYAAAAASUVORK5CYII=');
            cursor: row-resize;
            background-color:#efeff1;
            height: {{ $gutterSize }}px;
        }
        .gutter.gutter-vertical:hover{
            background-color:#e5e5ec;
        }
    </style>
@endonce