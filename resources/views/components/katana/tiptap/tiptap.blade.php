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
    x-data="{
        editor: null,
        content: @if ($attributes->wire('model')->value()) $wire.entangle('{{ $attributes->wire('model')->value() }}'),
        @else
            '{{ $content }}', @endif
        elementId: '{{ $id }}',
        linkModal: false,
        linkHref: '',
        savedSelection: {
            from: null,
            to: null
        },
        scriptLoaded: false,
        activeStates: {
            bold: false,
            italic: false,
            underline: false,
            code: false,
            heading_1: false,
            heading_2: false,
            heading_3: false,
            blockquote: false,
            bulletList: false,
            orderedList: false,
            codeBlock: false
        },

        async loadScript() {
            if (this.scriptLoaded || window.tipTapEditor) {
                return Promise.resolve();
            }

            return new Promise((resolve, reject) => {
                const script = document.createElement('script');
                script.src = '{{ asset('katana/tiptap.js') }}';
                script.onload = () => {
                    this.scriptLoaded = true;
                    resolve();
                };
                script.onerror = reject;
                document.head.appendChild(script);
            });
        },

        async init(element) {
            await this.loadScript();
            console.log('inited');
            console.log(this.elementId);
            $nextTick(() => {
            window.tiptap[this.elementId] = new tipTapEditor({
                element: element,
                extensions: [
                    tipTapStarterKit,
                    tipTapLink.configure({
                        autolink: true,
                        linkOnPaste: true,
                        openOnClick: false, // prevent default nav on click
                        HTMLAttributes: {
                            rel: 'noopener noreferrer nofollow'
                        },
                    }),
                    tipTapUnderline,
                ],
                content: this.content,
                onUpdate: ({
                    editor
                }) => {
                    this.content = window.tiptap[this.elementId].getHTML()
                },
            })

            // (2) keep Alpine state in sync with TipTap
            const updateMarks = () => {
                this.activeStates.bold = window.tiptap[this.elementId].isActive('bold')
                this.activeStates.italic = window.tiptap[this.elementId].isActive('italic')
                this.activeStates.underline = window.tiptap[this.elementId].isActive('underline')
                this.activeStates.code = window.tiptap[this.elementId].isActive('code')
                this.activeStates.heading_1 = window.tiptap[this.elementId].isActive('heading', {
                    level: 1
                })
                this.activeStates.heading_2 = window.tiptap[this.elementId].isActive('heading', {
                    level: 2
                })
                this.activeStates.heading_3 = window.tiptap[this.elementId].isActive('heading', {
                    level: 3
                })
                this.activeStates.blockquote = window.tiptap[this.elementId].isActive('blockquote')
                this.activeStates.bulletList = window.tiptap[this.elementId].isActive('bulletList')
                this.activeStates.orderedList = window.tiptap[this.elementId].isActive('orderedList')
                this.activeStates.codeBlock = window.tiptap[this.elementId].isActive('codeBlock')

                const href = window.tiptap[this.elementId].getAttributes('link')?.href
                this.linkHref = href || ''
            }
            window.tiptap[this.elementId].on('selectionUpdate', updateMarks)
            window.tiptap[this.elementId].on('transaction', updateMarks)
            window.tiptap[this.elementId].on('update', updateMarks)
            updateMarks() // initialize once

            document.getElementById(this.elementId).tiptap = window.tiptap[this.elementId];
            });
            this.$watch('linkModal', (value) => {
                if (value) {
                    setTimeout(() => {
                        this.$refs.linkInput.focus();
                    }, 100);
                }
            });
            this.$watch('content', (content) => {
                // If the new content matches Tiptap's then we just skip.
                if (content === window.tiptap[this.elementId].getHTML()) return

                /*
                Otherwise, it means that an external source is modifying the data on this Alpine component, which could be Livewire itself.
                In this case, we only need to update Tiptap's content and we're done. For more information on the `setContent()` method, see: https://www.tiptap.dev/api/commands/set-content
                */
                window.tiptap[this.elementId].commands.setContent(content, false)
            })
        },
        updateContent(newContent) {
            window.tiptap[this.elementId].commands.setContent(newContent, false);
        },
        linkToggle() {
            const sel = window.tiptap[this.elementId].state.selection;
            this.savedSelection = {
                from: sel.from,
                to: sel.to
            };

            // pull the latest href from selection just in case
            const href = window.tiptap[this.elementId].getAttributes('link')?.href
            this.linkHref = href || ''
            this.linkModal = !this.linkModal

            if (this.linkModal) {
                this.$nextTick(() => this.$refs.linkInput?.focus())
            }
        },
        insertLink() {
            this.linkHref.trim() ?
                window.tiptap[this.elementId].chain().focus().setTextSelection(this.savedSelection).setLink({
                    href: this.linkHref
                }).run() :
                this.unLink();
            this.linkModal = false;
        },
        unLink() {
            window.tiptap[this.elementId].chain().focus().setTextSelection(this.savedSelection).unsetLink().run();
        },
        get tiptap() {
            if(window.tiptap && window.tiptap[this.elementId]){
                return window.tiptap[this.elementId];
            }
            return {};
        }
    }" x-init="init($refs.editor)" id="{{ $id }}" class="relative min-h-[200px] w-full overflow-hidden rounded-lg border border-accent" @update-content="updateContent($event.detail.content)" wire:ignore {{ $attributes->whereDoesntStartWith('wire:model') }}>
    <div class="relative z-50 flex space-x-1 border-b border-accent bg-background p-1">
        @foreach ($toolbar_items as $item)
            @if ($item == 'divider')
                <div class="flex w-auto items-center justify-center">
                    <div class="mx-1.5 h-5 w-px bg-accent"></div>
                </div>
            @else
                <x-katana.tiptap.menu-item
                    :name="$item" :click="$toolbarItems[$item]['click']" tooltip="{{ $toolbarItems[$item]['name'] }}" :tooltipLeft="$loop->first" />
            @endif
        @endforeach
    </div>
    <div x-ref="editor" class="prose prose-sm sm:prose-base lg:prose-md dark:prose-invert lg:max-w-full sm:max-w-full max-w-full h-[200px] min-h-[200px] w-full overflow-scroll"></div>
    <x-katana.tiptap.modals.link :elementId="$id" />
</div>
