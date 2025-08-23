@props([
    'id' => '',
    'toolbar' => 'bold | italic | underline | code | divider | heading_1 | heading_2 | heading_3 | divider | link | bulletList | orderedList | blockquote | codeBlock'
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
    'orderedList' => ['name' => 'Numbered List', 'click' => "window.tiptap['$id'].chain().focus().toggleOrderedList().run()"]
];
@endphp

@once
    <style>
        .tiptap {
            min-height: 200px;
            outline: none;
            overflow: hidden;
            padding:10px;
            padding-top:0px;
        }
        .prose .tiptap p,
        .prose .tiptap h1,
        .prose .tiptap h2,
        .prose .tiptap h3
        {
            margin-top:0.5em;
            margin-bottom:0.5em;
        }
    </style>
@endonce
<div
    id="{{ $id }}"
    x-data="setupEditor(
        @if($attributes->wire('model')->value())
            $wire.entangle('{{ $attributes->wire('model')->value() }}'),
        @else
            '',
        @endif
        '{{ $id }}'
    )" x-init="() => init($refs.editor)" wire:ignore class="min-h-[200px] w-full rounded-lg overflow-hidden border relative border-stone-200" {{ $attributes->whereDoesntStartWith('wire:model') }}>
    <div class="flex relative z-50 p-1 space-x-1 border-b bg-stone-50 border-stone-200/70">
        @foreach($toolbar_items as $item)
            @if($item == 'divider')
                <div class="flex justify-center items-center w-auto">
                    <div class="mx-1.5 w-px h-5 bg-gray-300/70"></div>
                </div>
            @else
                <x-katana.tiptap.menu-item
                    :name="$item"
                    :click="$toolbarItems[$item]['click']"
                    tooltip="{{ $toolbarItems[$item]['name'] }}"
                    :tooltipLeft="$loop->first"
                />
            @endif
        @endforeach
    </div>
    <div x-ref="editor" class="h-[200px] min-h-[200px] w-full overflow-scroll prose prose-sm sm:prose-base lg:prose-md"></div>
    <x-katana.tiptap.modals.link :elementId="$id" />
</div>

@once
    <script>
        window.tiptap = [];
        window.setupEditor = function(content, elementId) {

            return {
                editor: null,
                content: content,
                elementId: elementId,
                linkModal: false,
                linkHref: '',
                savedSelection: { from: null, to: null },

                init(element) {
                    if(typeof(Editor) == 'undefined'){
                        console.error(`Please be sure to install TipTap:

    run the following:
    npm install @tiptap/core @tiptap/pm @tiptap/starter-kit @tiptap/extension-link --save-dev
    
    then, include the following in your app.js:
    import { Editor } from '@tiptap/core'
    import StarterKit from '@tiptap/starter-kit'
    import Link from '@tiptap/extension-link'
    window.Editor = Editor;
    window.StarterKit = StarterKit;
    window.TipTapLink = Link;
                        `)
                        return;
                    }
                    window.tiptap[this.elementId] = new Editor({
                        element: element,
                        extensions: [
                            StarterKit,
                            TipTapLink.configure({
                                autolink: true,
                                linkOnPaste: true,
                                openOnClick: false, // prevent default nav on click
                                HTMLAttributes: { rel: 'noopener noreferrer nofollow' },
                            }),    
                        ],
                        content: this.content,
                        onUpdate: ({
                            editor
                        }) => {
                            this.content = this.tiptap.getHTML()
                        },
                    })

                    // (2) keep Alpine state in sync with TipTap
                    const updateMarks = () => {
                        this.tiptap.isActive('bold')
                        this.tiptap.isActive('italic')
                        this.tiptap.isActive('code')
                        this.tiptap.isActive('heading', { level: 1 })
                        this.tiptap.isActive('heading', { level: 2 })
                        this.tiptap.isActive('heading', { level: 3 })

                        const href = this.tiptap.getAttributes('link')?.href
                        this.linkHref = href || ''
                    }
                    this.tiptap.on('selectionUpdate', updateMarks)
                    this.tiptap.on('transaction', updateMarks)
                    this.tiptap.on('update', updateMarks)
                    updateMarks() // initialize once

                    document.getElementById(elementId).tiptap = window.tiptap[this.elementId];

                    this.$watch('linkModal', (value) => {
                        if(value){
                            setTimeout(() => { this.$refs.linkInput.focus(); }, 100);
                        }
                    });
                    this.$watch('content', (content) => {
                        // If the new content matches Tiptap's then we just skip.
                        if (content === this.tiptap.getHTML()) return

                        /*
                        Otherwise, it means that an external source
                        is modifying the data on this Alpine component,
                        which could be Livewire itself.
                        In this case, we only need to update Tiptap's
                        content and we're done.
                        For more information on the `setContent()` method, see:
                            https://www.tiptap.dev/api/commands/set-content
                        */
                        this.tiptap.commands.setContent(content, false)
                    })
                },
                linkToggle(){
                    const sel = this.tiptap.state.selection;
                    this.savedSelection = { from: sel.from, to: sel.to };

                    // pull the latest href from selection just in case
                    const href = this.tiptap.getAttributes('link')?.href
                    this.linkHref = href || ''
                    this.linkModal = !this.linkModal

                    if (this.linkModal) {
                        this.$nextTick(() => this.$refs.linkInput?.focus())
                    }
                },
                insertLink(){
                    this.linkHref.trim()
                        ? window.tiptap[this.elementId].chain().focus().setTextSelection(this.savedSelection).setLink({ href: this.linkHref }).run()
                        : this.unLink();
                    this.linkModal=false;
                },
                unLink(){
                    window.tiptap[this.elementId].chain().focus().setTextSelection(this.savedSelection).unsetLink().run();
                },
                get tiptap(){
                    return window.tiptap[this.elementId];
                }
            }
        }
    </script>
@endonce
