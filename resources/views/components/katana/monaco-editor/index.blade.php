@props([
    'content' => '',
    'placeholder' => 'Start typing here',
    'theme' => 'dark',
    'paddingTopClass' => 'pt-3',
    'paddingTop' => 0,
    'tabindex' => null,
    'lineNumbers' => true
])

<div x-data="{
        monacoContent: @js($content),
        decodeHTMLEntities(html) {
            const textarea = document.createElement('textarea');
            textarea.innerHTML = html;
            return textarea.value;
        },
        monacoLanguage: 'html',
        monacoPlaceholder: false,
        monacoPlaceholderText: '{{ $placeholder }}',
        monacoLoader: true,
        monacoFontSize: '15px',
        monacoId: $id('monaco-editor'),
        editor: null,
         monacoEditor(editor){
            editor.onDidChangeModelContent((e) => {
                this.monacoContent = editor.getValue();
                this.updatePlaceholder(editor.getValue());
            });

            editor.onDidBlurEditorWidget(() => {
                this.updatePlaceholder(editor.getValue());
            });

            editor.onDidFocusEditorWidget(() => {
                this.updatePlaceholder(editor.getValue());
            });

            editor.onDropIntoEditor((drop) => {
                console.log('Image drop detected');
                let { event: e, position } = drop;
                e.preventDefault();
                
                // Get the file from the drop event
                const file = e.dataTransfer.files[0];
                if (file && file.type.startsWith('image/')) {
                    const fileName = file.name;
                    
                    // Create a placeholder text for the uploading image
                    const placeholderText = '<!-- Uploading ' + fileName + ' -->';
                    
                    // Insert the placeholder at the drop position
                    const range = new monaco.Range(
                        position.lineNumber,
                        position.column,
                        position.lineNumber,
                        position.column
                    );
                    
                    // Insert the placeholder text
                    editor.executeEdits('', [{
                        range: range,
                        text: placeholderText,
                        forceMoveMarkers: true
                    }]);
                    
                    // Create FormData for the file upload
                    const formData = new FormData();
                    formData.append('image', file);
                    
                    // Get CSRF token from meta tag
                    const csrfToken = document.querySelector('meta[name=csrf-token]').getAttribute('content');
                    
                    // Upload the image to the server
                    fetch('/api/image/upload', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 1) {
                            // Find the placeholder in the editor content
                            const model = editor.getModel();
                            const content = model.getValue();
                            const placeholderIndex = content.indexOf(placeholderText);
                            
                            if (placeholderIndex !== -1) {
                                // Create a range that encompasses the placeholder
                                const startPos = model.getPositionAt(placeholderIndex);
                                const endPos = model.getPositionAt(placeholderIndex + placeholderText.length);
                                const replaceRange = new monaco.Range(
                                    startPos.lineNumber,
                                    startPos.column,
                                    endPos.lineNumber,
                                    endPos.column
                                );
                                
                                // Get alt text from filename (without extension)
                                const altText = data.imgAlt || fileName.split('.').slice(0, -1).join('.');
                                
                                // Replace the placeholder with the actual image markdown
                                const imageMarkdown = '![' + altText + '](' + data.path + ')';
                                editor.executeEdits('', [{
                                    range: replaceRange,
                                    text: imageMarkdown,
                                    forceMoveMarkers: true
                                }]);
                                
                                // Alert when the image is successfully uploaded
                                console.log('Image uploaded successfully:', data.path);
                            }
                        } else {
                            // Handle upload error
                            console.error('Image upload failed:', data.message);
                            alert('Image upload failed: ' + data.message);
                            
                            // Remove the placeholder if upload fails
                            const model = editor.getModel();
                            const content = model.getValue();
                            const placeholderIndex = content.indexOf(placeholderText);
                            
                            if (placeholderIndex !== -1) {
                                const startPos = model.getPositionAt(placeholderIndex);
                                const endPos = model.getPositionAt(placeholderIndex + placeholderText.length);
                                const replaceRange = new monaco.Range(
                                    startPos.lineNumber,
                                    startPos.column,
                                    endPos.lineNumber,
                                    endPos.column
                                );
                                
                                editor.executeEdits('', [{
                                    range: replaceRange,
                                    text: '',
                                    forceMoveMarkers: true
                                }]);
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error uploading image:', error);
                        alert('Error uploading image: ' + error.message);
                        
                        // Remove the placeholder if upload fails
                        const model = editor.getModel();
                        const content = model.getValue();
                        const placeholderIndex = content.indexOf(placeholderText);
                        
                        if (placeholderIndex !== -1) {
                            const startPos = model.getPositionAt(placeholderIndex);
                            const endPos = model.getPositionAt(placeholderIndex + placeholderText.length);
                            const replaceRange = new monaco.Range(
                                startPos.lineNumber,
                                startPos.column,
                                endPos.lineNumber,
                                endPos.column
                            );
                            
                            editor.executeEdits('', [{
                                range: replaceRange,
                                text: '',
                                forceMoveMarkers: true
                            }]);
                        }
                    });
                }
            });


        },
        updatePlaceholder: function(value) {
            if (value == '') {
                this.monacoPlaceholder = true;
                return;
            }
            this.monacoPlaceholder = false;
        },
        monacoEditorFocus(){
            document.getElementById(this.monacoId).dispatchEvent(new CustomEvent('monaco-editor-focused', { monacoId: this.monacoId }));
        },
        monacoEditorAddLoaderScriptToHead() {
            script = document.createElement('script');
            script.src = 'https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.39.0/min/vs/loader.min.js';
            document.head.appendChild(script);
        }
    }"
    x-init="
            
        if(typeof _amdLoaderGlobal == 'undefined'){
            monacoEditorAddLoaderScriptToHead();
        }

        window.addEventListener('set-code', function(event) {
            document.getElementById(monacoId).editor.setValue(event.detail.code);
        });

        {{-- window.addEventListener('file-selected', function(event) {
            monacoContent =  event.detail[0].content;
            document.getElementById(monacoId).editor.setValue(monacoContent);
        }); --}}

        monacoLoaderInterval = setInterval(function(){
            if(typeof _amdLoaderGlobal !== 'undefined'){

            if (document.getElementById(monacoId)) {
                // Create global registry for Monaco instances if it doesn't exist
                if (!window.monacoInstances) {
                    window.monacoInstances = {};
                }
            }

            window.monacoInstances[monacoId] = {
                editor: document.getElementById(monacoId).editor,
                element: document.getElementById(monacoId)
            };

                // Based on https://jsfiddle.net/developit/bwgkr6uq/ which works without needing service worker. Provided by loader.min.js.
                require.config({ paths: { 'vs': 'https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.39.0/min/vs' }});
                let proxy = URL.createObjectURL(new Blob([` self.MonacoEnvironment = { baseUrl: 'https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.39.0/min' }; importScripts('https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.39.0/min/vs/base/worker/workerMain.min.js');`], { type: 'text/javascript' }));
                window.MonacoEnvironment = { getWorkerUrl: () => proxy };
                let lineNumberAttributes = {
                    lineNumbers: false,
                    lineNumberMinChars: 0,
                    glyphMargin: false,  
                };
                @if($lineNumbers)
                    lineNumberAttributes = {
                        lineNumbersMinChars: 3,
                        lineDecorationsWidth: '12px',
                    }
                @endif

                require(['vs/editor/editor.main'], function() {
                    
                    monacoTheme = {'base':'vs-dark','inherit':true,'rules':[{'background':'000000','token':''},{'foreground':'aeaeae','token':'comment'},{'foreground':'d8fa3c','token':'constant'},{'foreground':'ff6400','token':'entity'},{'foreground':'fbde2d','token':'keyword'},{'foreground':'fbde2d','token':'storage'},{'foreground':'61ce3c','token':'string'},{'foreground':'61ce3c','token':'meta.verbatim'},{'foreground':'8da6ce','token':'support'},{'foreground':'ab2a1d','fontStyle':'italic','token':'invalid.deprecated'},{'foreground':'f8f8f8','background':'9d1e15','token':'invalid.illegal'},{'foreground':'ff6400','fontStyle':'italic','token':'entity.other.inherited-class'},{'foreground':'ff6400','token':'string constant.other.placeholder'},{'foreground':'becde6','token':'meta.function-call.py'},{'foreground':'7f90aa','token':'meta.tag'},{'foreground':'7f90aa','token':'meta.tag entity'},{'foreground':'ffffff','token':'entity.name.section'},{'foreground':'d5e0f3','token':'keyword.type.variant'},{'foreground':'f8f8f8','token':'source.ocaml keyword.operator.symbol'},{'foreground':'8da6ce','token':'source.ocaml keyword.operator.symbol.infix'},{'foreground':'8da6ce','token':'source.ocaml keyword.operator.symbol.prefix'},{'fontStyle':'underline','token':'source.ocaml keyword.operator.symbol.infix.floating-point'},{'fontStyle':'underline','token':'source.ocaml keyword.operator.symbol.prefix.floating-point'},{'fontStyle':'underline','token':'source.ocaml constant.numeric.floating-point'},{'background':'ffffff08','token':'text.tex.latex meta.function.environment'},{'background':'7a96fa08','token':'text.tex.latex meta.function.environment meta.function.environment'},{'foreground':'fbde2d','token':'text.tex.latex support.function'},{'foreground':'ffffff','token':'source.plist string.unquoted'},{'foreground':'ffffff','token':'source.plist keyword.operator'}],'colors':{'editor.foreground':'#F8F8F8','editor.background':'#000000','editor.selectionBackground':'#253B76','editor.lineHighlightBackground':'#FFFFFF0F','editorCursor.foreground':'#FFFFFFA6','editorWhitespace.foreground':'#FFFFFF40'}};
                    monacoThemeLight = {'base':'vs','inherit':true,'rules':[{'background':'ffffff','token':''},{'foreground':'6a737d','token':'comment'},{'foreground':'005cc5','token':'constant'},{'foreground':'e36209','token':'entity'},{'foreground':'d73a49','token':'keyword'},{'foreground':'d73a49','token':'storage'},{'foreground':'032f62','token':'string'},{'foreground':'032f62','token':'meta.verbatim'},{'foreground':'005cc5','token':'support'},{'foreground':'b31d28','fontStyle':'italic','token':'invalid.deprecated'},{'foreground':'b31d28','background':'ffeef0','token':'invalid.illegal'},{'foreground':'6f42c1','fontStyle':'italic','token':'entity.other.inherited-class'},{'foreground':'22863a','token':'string constant.other.placeholder'},{'foreground':'005cc5','token':'meta.function-call.py'},{'foreground':'22863a','token':'meta.tag'},{'foreground':'22863a','token':'meta.tag entity'},{'foreground':'24292e','token':'entity.name.section'},{'foreground':'24292e','token':'keyword.type.variant'},{'foreground':'24292e','token':'source.ocaml keyword.operator.symbol'},{'foreground':'005cc5','token':'source.ocaml keyword.operator.symbol.infix'},{'foreground':'005cc5','token':'source.ocaml keyword.operator.symbol.prefix'},{'fontStyle':'underline','token':'source.ocaml keyword.operator.symbol.infix.floating-point'},{'fontStyle':'underline','token':'source.ocaml keyword.operator.symbol.prefix.floating-point'},{'fontStyle':'underline','token':'source.ocaml constant.numeric.floating-point'},{'background':'f6f8f8','token':'text.tex.latex meta.function.environment'},{'background':'f6f8f8','token':'text.tex.latex meta.function.environment meta.function.environment'},{'foreground':'d73a49','token':'text.tex.latex support.function'},{'foreground':'24292e','token':'source.plist string.unquoted'},{'foreground':'24292e','token':'source.plist keyword.operator'}],'colors':{'editor.foreground':'#24292e','editor.background':'#ffffff','editor.selectionBackground':'#c8c8fa','editor.lineHighlightBackground':'#f5f5f6','editorCursor.foreground':'#24292e','editorWhitespace.foreground':'#e1e4e8'}};
                    monaco.editor.defineTheme('light', monacoThemeLight);
                    monaco.editor.defineTheme('dark', monacoTheme);
                    console.log('maker');
                    document.getElementById(monacoId).editor = monaco.editor.create($refs.monacoEditorElement, {
                        value: decodeHTMLEntities(monacoContent),
                        padding: {
                            top: parseInt('{{ $paddingTop ?? 0 }}')
                        },
                        theme: '{{ $theme }}',
                        fontSize: monacoFontSize,
                        automaticLayout: true,
                        language: 'markdown',
                        minimap: { enabled: false },
                        tabIndex: {{ $tabindex ?? 0 }},
                        ...lineNumberAttributes
                    });
                    
                    // Monaco's built-in tabIndex option will handle the tab order
                    monacoEditor(document.getElementById(monacoId).editor);
                    document.getElementById(monacoId).addEventListener('monaco-editor-focused', function(event){
                        document.getElementById(monacoId).editor.focus();
                    });
                    updatePlaceholder(document.getElementById(monacoId).editor.getValue());
                    {{-- monaco.editor.getModel().onDidChangeContent((event) => {
                        console.log('we here');
                    }); --}}
                    document.getElementById(monacoId).editor.getModel().onDidChangeContent(function(event){ 
                        content = document.getElementById(monacoId).editor.getValue();
                        window.dispatchEvent(new CustomEvent('monaco-content-changed', {
                            detail: {
                                id: monacoId,
                                content: content
                            }
                        }));
                    });

                    window.addEventListener('monaco-editor-height-update', function(event){
                        $refs.monacoEditorElement.style.height=event.detail.height;
                    })
                    

                });

                clearInterval(monacoLoaderInterval);
                monacoLoader = false;
            }
        }, 5);
    " :id="monacoId" class="flex flex-col items-center relative justify-start w-full min-h-[250px] h-full {{ $paddingTopClass }}"
    @update-placeholder-text.window="monacoPlaceholderText=$event.detail.placeholderText; console.log($event.detail)"
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
        <div x-ref="monacoPlaceholderElement" x-show="monacoPlaceholder" @click="monacoEditorFocus()" :style="'font-size: ' + monacoFontSize + '; margin-top: {{ $paddingTop }}px'" class="absolute pointer-events-none top-0 left-0 z-50 mt-0.5 @if($lineNumbers) ml-16 @else ml-8 @endif w-full font-mono text-sm -translate-x-0.5 text-stone-500" x-text="monacoPlaceholderText"></div>
    </div>
    

</div>