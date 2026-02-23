@props([
    'content' => '',
    'language' => 'html',
    'placeholder' => 'Start typing here',
    'theme' => 'auto',
    'readOnly' => false,
    'autoIndent' => true,
    'wordWrap' => 'off',
    'minLines' => null,
    'maxLines' => null,
    'fontSize' => 14,
    'showWhitespace' => 'none',
    'formatOnPaste' => true,
    'suggestOnTriggerCharacters' => true,
    'lineNumbersStyle' => 'on',
    'paddingTop' => 12,
    'height' => 250,
    'tabindex' => null,
    'lineNumbers' => true,
    'minimap' => false
])

<script>
if (!window.KatanaMonacoEditor) {
    window._katanaMonacoLoadPromise = null;
    window.KatanaMonacoEditor = function(config) {
        return {
            monacoLoader: true,
            monacoPlaceholder: false,
            monacoPlaceholderText: config.placeholder || 'Start typing here',
            monacoFontSize: (config.fontSize || 14) + 'px',
            monacoContent: config.content || '',
            monacoId: null,
            editor: null,
            monacoEditorFocus() {
                this.$el.dispatchEvent(
                    new CustomEvent('monaco-editor-focused', { monacoId: this.monacoId })
                );
            },
            init() {
                this.monacoId = this.$id('monaco-editor');
                this.$el.id = this.monacoId;
                var self = this;
                if (!window._katanaMonacoLoadPromise) {
                    window._katanaMonacoLoadPromise = new Promise(function(resolve, reject) {
                        var s = document.createElement('script');
                        s.src = config.scriptUrl;
                        s.onload = resolve;
                        s.onerror = reject;
                        document.head.appendChild(s);
                    });
                }
                window._katanaMonacoLoadPromise.then(function() {
                    var full = window.KatanaMonacoEditor(config);
                    var keys = Object.keys(full);
                    for (var i = 0; i < keys.length; i++) {
                        if (keys[i] !== 'init') {
                            self[keys[i]] = full[keys[i]];
                        }
                    }
                    full.init.call(self);
                });
            }
        };
    };
}
</script>

<div x-data="KatanaMonacoEditor({
        content: @js($content),
        language: @js($language),
        placeholder: @js($placeholder),
        theme: @js($theme),
        readOnly: {{ $readOnly ? 'true' : 'false' }},
        autoIndent: {{ $autoIndent ? 'true' : 'false' }},
        wordWrap: @js($wordWrap),
        minLines: {{ $minLines ? $minLines : 'null' }},
        maxLines: {{ $maxLines ? $maxLines : 'null' }},
        fontSize: {{ $fontSize }},
        showWhitespace: @js($showWhitespace),
        formatOnPaste: {{ $formatOnPaste ? 'true' : 'false' }},
        suggestOnTriggerCharacters: {{ $suggestOnTriggerCharacters ? 'true' : 'false' }},
        lineNumbersStyle: @js($lineNumbersStyle),
        paddingTop: {{ $paddingTop ?? 0 }},
        tabIndex: {{ $tabindex ?? 0 }},
        lineNumbers: {{ $lineNumbers ? 'true' : 'false' }},
        minimap: {{ $minimap ? 'true' : 'false' }},
        cssUrl: '{{ asset('katana/monaco-editor.css') }}',
        workerUrl: '{{ asset('katana/monaco-editor-worker.js') }}',
        scriptUrl: '{{ asset('katana/monaco-editor.js') }}'
    })"
    class="flex flex-col items-center relative justify-start @if($theme == 'auto') bg-white dark:bg-stone-900 @elseif($theme == 'light') bg-white @else bg-stone-900 @endif overflow-hidden w-full h-full"
    style="height:{{ $height }}px"
    @update-placeholder-text.window="monacoPlaceholderText=$event.detail.placeholderText"
    @focus-editor.window="monacoEditorFocus()"
    >
    <style type="text/css">
    .monaco-editor .margin {
        margin-left:0px !important;
    }
    @if(!$lineNumbers)
    .monaco-scrollable-element {
        left:16px !important;
    }
    @endif
    </style>
    <div x-show="monacoLoader" class="flex absolute inset-0 z-20 justify-center items-center w-full h-full duration-1000 ease-out">
        <svg class="w-4 h-4 animate-spin text-stone-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
    </div>

    <div x-show="!monacoLoader" class="relative z-10 w-full h-full">
        <div x-ref="monacoEditorElement" class="w-full h-full text-lg"></div>
        <div x-ref="monacoPlaceholderElement" x-show="monacoPlaceholder" @click="monacoEditorFocus()" :style="'font-size: ' + monacoFontSize + '; margin-top: {{ $paddingTop }}px'" class="absolute pointer-events-none top-0 left-0 z-50 mt-0.5 @if($lineNumbers) ml-12 @else ml-6 @endif w-full font-mono text-sm -translate-x-0.5 text-stone-500" x-text="monacoPlaceholderText"></div>
    </div>

</div>
