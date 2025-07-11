@props([
    'direction' => 'horizontal',
    'gutterSize' => 8,
    'minSize' => 100,
    'dark' => false,
])

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
    class="split {{ $direction === 'vertical' ? 'flex-col' : 'flex' }} w-full h-full"
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
            @if($dark)
                background-color: #222;
            @else
                background-color: #eee;
            @endif
            background-repeat: no-repeat;
            background-position: 50%;
        }
        .gutter:hover{
            background-color:#2c67f6;
        }
        .gutter.gutter-horizontal {
            background-image: url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAUAAAAeCAYAAADkftS9AAAAIklEQVQoU2M4c+bMfxAGAgYYmwGrIIiDjrELjpo5aiZeMwF+yNnOs5KSvgAAAABJRU5ErkJggg==');
            cursor: col-resize;
            width: {{ $gutterSize }}px;
        }
        .gutter.gutter-vertical {
            background-image: url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAB4AAAAFCAYAAABMtWY8AAAALUlEQVQoz2N4c+bMf0YGBgYG+iZkA2QKqIEqsGdoPZkJ6uHj4HkBa6cgFwAANn8IddZTTbYAAAAASUVORK5CYII=');
            cursor: row-resize;
            height: {{ $gutterSize }}px;
        }
    </style>
@endonce