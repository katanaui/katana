import { Editor } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import TipTapLink from '@tiptap/extension-link';

window.tiptap = [];

document.addEventListener('alpine:init', () => {
    Alpine.data('setupEditor', (content, elementId) => ({
        editor: null,
        content: content,
        elementId: elementId,
        linkModal: false,
        linkHref: '',
        savedSelection: {
            from: null,
            to: null
        },

        init(element) {
            window.tiptap[this.elementId] = new Editor({
                element: element,
                extensions: [
                    StarterKit,
                    TipTapLink.configure({
                        autolink: true,
                        linkOnPaste: true,
                        openOnClick: false, // prevent default nav on click
                        HTMLAttributes: {
                            rel: 'noopener noreferrer nofollow'
                        },
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
                this.tiptap.isActive('heading', {
                    level: 1
                })
                this.tiptap.isActive('heading', {
                    level: 2
                })
                this.tiptap.isActive('heading', {
                    level: 3
                })

                const href = this.tiptap.getAttributes('link')?.href
                this.linkHref = href || ''
            }
            this.tiptap.on('selectionUpdate', updateMarks)
            this.tiptap.on('transaction', updateMarks)
            this.tiptap.on('update', updateMarks)
            updateMarks() // initialize once

            document.getElementById(elementId).tiptap = window.tiptap[this.elementId];

            this.$watch('linkModal', (value) => {
                if (value) {
                    setTimeout(() => {
                        this.$refs.linkInput.focus();
                    }, 100);
                }
            });
            this.$watch('content', (content) => {
                // If the new content matches Tiptap's then we just skip.
                if (content === this.tiptap.getHTML()) return

                /*
                Otherwise, it means that an external source is modifying the data on this Alpine component, which could be Livewire itself.
                In this case, we only need to update Tiptap's content and we're done. For more information on the `setContent()` method, see: https://www.tiptap.dev/api/commands/set-content
                */
                this.tiptap.commands.setContent(content, false)
            })
        },
        updateContent(newContent) {
            window.tiptap[this.elementId].commands.setContent(newContent, false);
        },
        linkToggle() {
            const sel = this.tiptap.state.selection;
            this.savedSelection = {
                from: sel.from,
                to: sel.to
            };

            // pull the latest href from selection just in case
            const href = this.tiptap.getAttributes('link')?.href
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
            return window.tiptap[this.elementId];
        }
    }))
});