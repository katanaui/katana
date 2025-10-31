@props([
    'id' => '',
    'toolbar' => 'bold | italic | underline | code | divider | heading_1 | heading_2 | heading_3 | divider | link | bulletList | orderedList | blockquote | codeBlock',
    'content' => '',
])

@php
    $id = $id ?: 'tiptap-' . uniqid();
    $toolbar_items = array_map('trim', explode('|', $toolbar));

    $toolbarItems = [
        'bold' => ['name' => 'Bold', 'click' => "window.tiptap['$id'].chain().focus().toggleBold().run()"],
        'italic' => ['name' => 'Italic', 'click' => "window.tiptap['$id'].chain().focus().toggleItalic().run()"],
        'underline' => ['name' => 'Underline', 'click' => "window.tiptap['$id'].chain().focus().toggleUnderline().run()"],
        'link' => ['name' => 'Link', 'click' => 'linkToggle()'],
        'code' => ['name' => 'Inline Code', 'click' => "window.tiptap['$id'].chain().focus().toggleCode().run()"],
        'codeBlock' => ['name' => 'Code Block', 'click' => "window.tiptap['$id'].chain().focus().toggleCodeBlock().run()"],
        'heading_1' => ['name' => 'Heading 1', 'click' => "window.tiptap['$id'].chain().focus().toggleHeading({ level: 1 }).run()"],
        'heading_2' => ['name' => 'Heading 2', 'click' => "window.tiptap['$id'].chain().focus().toggleHeading({ level: 2 }).run()"],
        'heading_3' => ['name' => 'Heading 3', 'click' => "window.tiptap['$id'].chain().focus().toggleHeading({ level: 3 }).run()"],
        'blockquote' => ['name' => 'Blockquote', 'click' => "window.tiptap['$id'].chain().focus().toggleBlockquote().run()"],
        'bulletList' => ['name' => 'Bullet List', 'click' => "window.tiptap['$id'].chain().focus().toggleBulletList().run()"],
        'orderedList' => ['name' => 'Numbered List', 'click' => "window.tiptap['$id'].chain().focus().toggleOrderedList().run()"],
    ];
@endphp

@once
    <script src="{{ asset('katana/tiptap.js') }}"></script>
    <style>
        .tiptap {
            min-height: 200px;
            outline: none;
            overflow: hidden;
            padding: 10px;
            padding-top: 0px;
        }

        .prose .tiptap p,
        .prose .tiptap h1,
        .prose .tiptap h2,
        .prose .tiptap h3 {
            margin-top: 0.5em;
            margin-bottom: 0.5em;
        }
    </style>
@endonce

<div
    x-data="setupEditor('{{ $content }}', '{{ $id }}')" x-init="setTimeout(function() {
        init($refs.editor);
    }, 1000);" id="{{ $id }}" class="relative min-h-[200px] w-full overflow-hidden rounded-lg border border-stone-200" @update-content="updateContent($event.detail.content)" wire:ignore {{ $attributes->whereDoesntStartWith('wire:model') }}>
    <div class="relative z-50 flex space-x-1 border-b border-stone-200/70 bg-stone-50 p-1">
        @foreach ($toolbar_items as $item)
            @if ($item == 'divider')
                <div class="flex w-auto items-center justify-center">
                    <div class="mx-1.5 h-5 w-px bg-gray-300/70"></div>
                </div>
            @else
                <x-katana.tiptap.menu-item
                    :name="$item" :click="$toolbarItems[$item]['click']" tooltip="{{ $toolbarItems[$item]['name'] }}" :tooltipLeft="$loop->first" />
            @endif
        @endforeach
    </div>
    <div x-ref="editor" class="prose prose-sm sm:prose-base lg:prose-md h-[200px] min-h-[200px] w-full overflow-scroll"></div>
    <x-katana.tiptap.modals.link :elementId="$id" />
</div>
