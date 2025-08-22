@props([
    'id' => ''
])

@php
$id = $id ?: 'tiptap-' . uniqid();
@endphp

@once
    <style>
        .tiptap {
            min-height: 200px;
            outline: none;
            overflow: hidden;
            padding:10px;
        }
    </style>
@endonce
<div
    id="{{ $id }}"
    x-data="setupEditor(
        $wire.entangle('{{ $attributes->wire('model')->value() }}'),
        '{{ $id }}'
    )" x-init="() => init($refs.editor)" wire:ignore class="min-h-[200px] w-full rounded-lg overflow-hidden border border-stone-200" {{ $attributes->whereDoesntStartWith('wire:model') }}>
    <div class="flex relative z-50 p-1 space-x-1 border-b bg-stone-50 border-stone-200/70">
        <button 
        @click="window.editor['{{ $id }}'].commands.toggleBold()"
        :class="{ 'text-black bg-black/5' : tiptap.isActive('bold'), 'text-gray-700 hover:bg-black/5 hover:text-black' : !tiptap.isActive('bold') }"
        class="flex relative justify-center items-center w-7 h-7 rounded-md group">
        
            <span class="w-4 h-4"><svg class="w-full h-full fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="none" d="M0 0h24v24H0z"></path><path d="M8 11h4.5a2.5 2.5 0 1 0 0-5H8v5zm10 4.5a4.5 4.5 0 0 1-4.5 4.5H6V4h6.5a4.5 4.5 0 0 1 3.256 7.606A4.498 4.498 0 0 1 18 15.5zM8 13v5h5.5a2.5 2.5 0 1 0 0-5H8z"></path></svg></span>
            <span class="pointer-events-none invisible absolute bottom-0 left-1/2 mb-0 -translate-x-1/2 translate-y-full whitespace-nowrap rounded bg-black/70 px-2 py-1 text-[0.6rem] text-white shadow-lg duration-0 ease-linear group-hover:visible group-hover:-mb-1 group-hover:duration-300 group-hover:ease-out">Bold</span>
        </button>
        <button data-tooltip="Italic" class="flex relative justify-center items-center w-7 h-7 text-gray-700 rounded-md group hover:bg-black/5 hover:text-black">
            <span class="w-4 h-4"><svg class="w-full h-full fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="none" d="M0 0h24v24H0z"></path><path d="M15 20H7v-2h2.927l2.116-12H9V4h8v2h-2.927l-2.116 12H15z"></path></svg></span>
            <span class="pointer-events-none invisible absolute bottom-0 left-1/2 mb-0 -translate-x-1/2 translate-y-full whitespace-nowrap rounded bg-black/70 px-2 py-1 text-[0.6rem] text-white shadow-lg duration-0 ease-linear group-hover:visible group-hover:-mb-1 group-hover:duration-300 group-hover:ease-out">Italic</span>
        </button>
        <button data-tooltip="Inline Code" class="flex relative justify-center items-center w-7 h-7 text-gray-700 rounded-md group hover:bg-black/5 hover:text-black">
            <span class="w-4 h-4"><svg class="w-full h-full fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M9.95263 16.9123L8.59323 18.3608L2.03082 12.2016L8.18994 5.63922L9.64826 7.00791L4.85783 12.112L9.95212 16.8932L9.95263 16.9123Z" fill="currentColor"></path><path d="M14.0474 16.9123L15.4068 18.3608L21.9692 12.2016L15.8101 5.63922L14.3517 7.00791L19.1422 12.112L14.0479 16.8932L14.0474 16.9123Z" fill="currentColor"></path></svg></span>
            <span class="pointer-events-none invisible absolute bottom-0 left-1/2 mb-0 -translate-x-1/2 translate-y-full whitespace-nowrap rounded bg-black/70 px-2 py-1 text-[0.6rem] text-white shadow-lg duration-0 ease-linear group-hover:visible group-hover:-mb-1 group-hover:duration-300 group-hover:ease-out">Inline Code</span>
        </button>
        <div class="flex justify-center items-center w-auto">
            <div class="mx-1.5 w-px h-5 bg-gray-300/70"></div>
        </div>
        <button data-tooltip="Heading" class="flex relative justify-center items-center w-7 h-7 text-gray-700 rounded-md group hover:bg-black/5 hover:text-black">
            <span class="w-4 h-4"><svg class="w-full h-full fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"></rect><polyline points="224 208 224 112 200 128" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="24"></polyline><line x1="40" y1="56" x2="40" y2="176" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="24"></line><line x1="144" y1="116" x2="40" y2="116" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="24"></line><line x1="144" y1="56" x2="144" y2="176" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="24"></line></svg></span>
            <span class="pointer-events-none invisible absolute bottom-0 left-1/2 mb-0 -translate-x-1/2 translate-y-full whitespace-nowrap rounded bg-black/70 px-2 py-1 text-[0.6rem] text-white shadow-lg duration-0 ease-linear group-hover:visible group-hover:-mb-1 group-hover:duration-300 group-hover:ease-out">Heading</span>
        </button>
        <button data-tooltip="Heading 2" class="flex relative justify-center items-center w-7 h-7 text-gray-700 rounded-md group hover:bg-black/5 hover:text-black">
            <span class="w-4 h-4"><svg class="w-full h-full fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"></rect><line x1="40" y1="56" x2="40" y2="176" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="24"></line><line x1="144" y1="116" x2="40" y2="116" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="24"></line><line x1="144" y1="56" x2="144" y2="176" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="24"></line><path d="M240,208H192l43.17-57.56A24,24,0,1,0,193.37,128" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="24"></path></svg></span>
            <span class="pointer-events-none invisible absolute bottom-0 left-1/2 mb-0 -translate-x-1/2 translate-y-full whitespace-nowrap rounded bg-black/70 px-2 py-1 text-[0.6rem] text-white shadow-lg duration-0 ease-linear group-hover:visible group-hover:-mb-1 group-hover:duration-300 group-hover:ease-out">Heading 2</span>
        </button>
        <button data-tooltip="Heading 3" class="flex relative justify-center items-center w-7 h-7 text-gray-700 rounded-md group hover:bg-black/5 hover:text-black">
            <span class="w-4 h-4"><svg class="w-full h-full fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"></rect><path d="M192,112h48l-28,40a28,28,0,1,1-20,47.6" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="24"></path><line x1="40" y1="56" x2="40" y2="176" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="24"></line><line x1="144" y1="116" x2="40" y2="116" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="24"></line><line x1="144" y1="56" x2="144" y2="176" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="24"></line></svg></span>
            <span class="pointer-events-none invisible absolute bottom-0 left-1/2 mb-0 -translate-x-1/2 translate-y-full whitespace-nowrap rounded bg-black/70 px-2 py-1 text-[0.6rem] text-white shadow-lg duration-0 ease-linear group-hover:visible group-hover:-mb-1 group-hover:duration-300 group-hover:ease-out">Heading 3</span>
        </button>
        <div class="flex justify-center items-center w-auto">
            <div class="mx-1.5 w-px h-5 bg-gray-300/70"></div>
        </div>
        <button data-tooltip="Code" class="flex relative justify-center items-center w-7 h-7 text-gray-700 rounded-md group hover:bg-black/5 hover:text-black">
            <span class="w-4 h-4"><svg class="w-full h-full fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"></rect><rect x="48" y="48" width="160" height="160" rx="8" opacity="0.2"></rect><polyline points="64 32 32 64 64 96" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"></polyline><polyline points="104 32 136 64 104 96" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"></polyline><path d="M176,48h24a8,8,0,0,1,8,8V200a8,8,0,0,1-8,8H56a8,8,0,0,1-8-8V136" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"></path></svg></span>
            <span class="pointer-events-none invisible absolute bottom-0 left-1/2 mb-0 -translate-x-1/2 translate-y-full whitespace-nowrap rounded bg-black/70 px-2 py-1 text-[0.6rem] text-white shadow-lg duration-0 ease-linear group-hover:visible group-hover:-mb-1 group-hover:duration-300 group-hover:ease-out">Code</span>
        </button>
        <button data-tooltip="Quote" class="flex relative justify-center items-center w-7 h-7 text-gray-700 rounded-md group hover:bg-black/5 hover:text-black">
            <span class="w-4 h-4"><svg class="w-full h-full fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"></rect><path d="M116,72v88a48.05,48.05,0,0,1-48,48,8,8,0,0,1,0-16,32,32,0,0,0,32-32v-8H40a16,16,0,0,1-16-16V72A16,16,0,0,1,40,56h60A16,16,0,0,1,116,72ZM216,56H156a16,16,0,0,0-16,16v64a16,16,0,0,0,16,16h60v8a32,32,0,0,1-32,32,8,8,0,0,0,0,16,48.05,48.05,0,0,0,48-48V72A16,16,0,0,0,216,56Z"></path></svg></span>
            <span class="pointer-events-none invisible absolute bottom-0 left-1/2 mb-0 -translate-x-1/2 translate-y-full whitespace-nowrap rounded bg-black/70 px-2 py-1 text-[0.6rem] text-white shadow-lg duration-0 ease-linear group-hover:visible group-hover:-mb-1 group-hover:duration-300 group-hover:ease-out">Quote</span>
        </button>
        <button data-tooltip="Bulleted List" class="flex relative justify-center items-center w-7 h-7 text-gray-700 rounded-md group hover:bg-black/5 hover:text-black">
            <span class="w-4 h-4"><svg class="w-full h-full fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"></rect><line x1="88" y1="64" x2="216" y2="64" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="24"></line><line x1="88" y1="128" x2="216" y2="128" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="24"></line><line x1="88" y1="192" x2="216" y2="192" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="24"></line><circle cx="44" cy="128" r="16"></circle><circle cx="44" cy="64" r="16"></circle><circle cx="44" cy="192" r="16"></circle></svg></span>
            <span class="pointer-events-none invisible absolute bottom-0 left-1/2 mb-0 -translate-x-1/2 translate-y-full whitespace-nowrap rounded bg-black/70 px-2 py-1 text-[0.6rem] text-white shadow-lg duration-0 ease-linear group-hover:visible group-hover:-mb-1 group-hover:duration-300 group-hover:ease-out">Bulleted List</span>
        </button>
        <button data-tooltip="Numbered List" class="flex relative justify-center items-center w-7 h-7 text-gray-700 rounded-md group hover:bg-black/5 hover:text-black">
            <span class="w-4 h-4"><svg class="w-full h-full fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"></rect><line x1="116" y1="128" x2="216" y2="128" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="24"></line><line x1="116" y1="64" x2="216" y2="64" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="24"></line><line x1="116" y1="192" x2="216" y2="192" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="24"></line><polyline points="56 104 56 40 40 48" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="24"></polyline><path d="M72,208H40l28.68-38.37a15.69,15.69,0,0,0-3.24-22.41,16.78,16.78,0,0,0-23.06,3.15,15.85,15.85,0,0,0-2.38,4.3" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="24"></path></svg></span>
            <span class="pointer-events-none invisible absolute bottom-0 left-1/2 mb-0 -translate-x-1/2 translate-y-full whitespace-nowrap rounded bg-black/70 px-2 py-1 text-[0.6rem] text-white shadow-lg duration-0 ease-linear group-hover:visible group-hover:-mb-1 group-hover:duration-300 group-hover:ease-out">Numbered List</span>
        </button>
    </div>
    <div x-ref="editor" class="h-[200px] min-h-[200px] w-full overflow-scroll"></div>
</div>

@once
    <script>
        window.editor = [];
        window.setupEditor = function(content, elementId) {

            return {
                editor: null,
                content: content,
                elementId: elementId,

                init(element) {
                    if(typeof(Editor) == 'undefined'){
                        console.error(`Please be sure to install TipTap:

    run the following:
    npm install @tiptap/core @tiptap/pm @tiptap/starter-kit --save-dev
    
    then, include the following in your app.js:
    import { Editor } from '@tiptap/core'
    import StarterKit from '@tiptap/starter-kit'
    window.Editor = Editor;
    window.StarterKit = StarterKit;
                        `)
                        return;
                    }
                    window.editor[this.elementId] = new Editor({
                        element: element,
                        extensions: [StarterKit],
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
                    }
                    this.tiptap.on('selectionUpdate', updateMarks)
                    this.tiptap.on('transaction', updateMarks)
                    this.tiptap.on('update', updateMarks)
                    updateMarks() // initialize once

                    console.log(window.editor[this.elementId]);

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
                get tiptap(){
                    return window.editor[this.elementId];
                },
                toggleBold() {
                    this.editor.chain().focus().toggleBold().run()
                    //this.editor.commands.toggleBold();
                },
            }
        }
    </script>
@endonce
