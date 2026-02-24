@props([
    'content' => '',
    'placeholder' => 'Start typing here',
    'theme' => 'dark',
    'language' => 'markdown',
    'fontSize' => '15px',
    'lineNumbers' => true,
    'wordWrap' => true,
    'minimap' => false,
    'paddingTopClass' => 'pt-3',
    'paddingTop' => 0,
    'tabindex' => null,
])

@php
    $monacoId = 'monaco-' . uniqid();
@endphp

<div
    x-data="{
        monacoContent: @if($attributes->wire('model')->value()) $wire.entangle('{{ $attributes->wire('model')->value() }}') @else @js($content) @endif,
        monacoLanguage: @js($language),
        monacoPlaceholder: false,
        monacoPlaceholderText: @js($placeholder),
        monacoLoader: true,
        monacoFontSize: @js($fontSize),
        monacoId: '{{ $monacoId }}',
        editor: null,
        _initializing: false,

        decodeHTMLEntities(html) {
            const textarea = document.createElement('textarea');
            textarea.innerHTML = html;
            return textarea.value;
        },

        loadStyles() {
            if (document.querySelector('[data-katana-monaco-css]')) {
                return Promise.resolve();
            }
            return new Promise((resolve, reject) => {
                const link = document.createElement('link');
                link.rel = 'stylesheet';
                link.href = '{{ asset('katana/monaco-editor.css') }}';
                link.setAttribute('data-katana-monaco-css', 'true');
                link.onload = resolve;
                link.onerror = reject;
                document.head.appendChild(link);
            });
        },

        loadScript() {
            if (window.KatanaMonaco) {
                return Promise.resolve();
            }
            return new Promise((resolve, reject) => {
                const script = document.createElement('script');
                script.src = '{{ asset('katana/monaco-editor.js') }}';
                script.onload = () => resolve();
                script.onerror = reject;
                document.head.appendChild(script);
            });
        },

        updatePlaceholder(value) {
            this.monacoPlaceholder = !value || value === '';
        },

        monacoEditorFocus() {
            if (this.editor) {
                this.editor.focus();
            }
        },

        async init() {
            if (this.editor || this._initializing) return;
            this._initializing = true;

            try {
                await Promise.all([this.loadStyles(), this.loadScript()]);
            } catch (e) {
                console.error('[Monaco] Failed to load assets:', e);
                this._initializing = false;
                return;
            }

            this.$nextTick(() => {
                const element = this.$refs.monacoEditorElement;
                if (!element) { this._initializing = false; return; }

                element.innerHTML = '';

                const decodedContent = this.decodeHTMLEntities(this.monacoContent || '');

                this.editor = window.KatanaMonaco.createEditor(this.monacoId, element, {
                    value: decodedContent,
                    language: this.monacoLanguage,
                    theme: @js($theme),
                    fontSize: this.monacoFontSize,
                    wordWrap: {{ $wordWrap ? 'true' : 'false' }} ? 'on' : 'off',
                    minimap: {{ $minimap ? 'true' : 'false' }},
                    lineNumbers: {{ $lineNumbers ? 'true' : 'false' }},
                    tabIndex: {{ $tabindex ?? 0 }},
                    paddingTop: parseInt(@js($paddingTop)),
                });

                this.editor.onDidChangeModelContent(() => {
                    const content = this.editor.getValue();
                    this.monacoContent = content;
                    this.updatePlaceholder(content);
                    window.dispatchEvent(new CustomEvent('monaco-content-changed', {
                        detail: { id: this.monacoId, content },
                    }));
                });

                this.editor.onDidBlurEditorWidget(() => this.updatePlaceholder(this.editor.getValue()));
                this.editor.onDidFocusEditorWidget(() => this.updatePlaceholder(this.editor.getValue()));

                this.updatePlaceholder(decodedContent);
                this.monacoLoader = false;
            });
        },
    }"
    x-init="init()"
    x-destroy="if (editor) { editor.dispose(); editor = null; }"
    x-on:set-code.window="if (!$event.detail.id || $event.detail.id === monacoId) { editor?.setValue($event.detail.code); }"
    x-on:focus-editor.window="monacoEditorFocus()"
    :id="monacoId"
    class="relative w-full min-h-[250px] h-full {{ $paddingTopClass }}"
    wire:ignore
    {{ $attributes->whereDoesntStartWith('wire:model') }}
>
    <div x-show="monacoLoader" class="flex absolute inset-0 z-20 justify-center items-center w-full h-full">
        <svg class="w-4 h-4 animate-spin text-stone-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
    </div>

    <div class="relative z-10 w-full h-full min-h-[400px]">
        <div x-ref="monacoEditorElement" class="w-full h-full"></div>
        <div
            x-ref="monacoPlaceholderElement"
            x-show="monacoPlaceholder"
            @click="monacoEditorFocus()"
            :style="'font-size: ' + monacoFontSize + '; margin-top: {{ $paddingTop }}px'"
            class="absolute pointer-events-none top-0 left-0 z-50 mt-0.5 @if($lineNumbers) ml-16 @else ml-8 @endif w-full font-mono text-sm -translate-x-0.5 text-stone-500"
            x-text="monacoPlaceholderText"
        ></div>
    </div>
</div>
