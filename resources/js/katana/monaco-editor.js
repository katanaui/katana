import * as monaco from 'monaco-editor';

window.monaco = monaco;

const monacoThemeDark = {
    base: 'vs-dark',
    inherit: true,
    rules: [
        { background: '000000', token: '' },
        { foreground: 'aeaeae', token: 'comment' },
        { foreground: 'd8fa3c', token: 'constant' },
        { foreground: 'ff6400', token: 'entity' },
        { foreground: 'fbde2d', token: 'keyword' },
        { foreground: 'fbde2d', token: 'storage' },
        { foreground: '61ce3c', token: 'string' },
        { foreground: '61ce3c', token: 'meta.verbatim' },
        { foreground: '8da6ce', token: 'support' },
        { foreground: 'ab2a1d', fontStyle: 'italic', token: 'invalid.deprecated' },
        { foreground: 'f8f8f8', background: '9d1e15', token: 'invalid.illegal' },
        { foreground: 'ff6400', fontStyle: 'italic', token: 'entity.other.inherited-class' },
        { foreground: 'ff6400', token: 'string constant.other.placeholder' },
        { foreground: 'becde6', token: 'meta.function-call.py' },
        { foreground: '7f90aa', token: 'meta.tag' },
        { foreground: '7f90aa', token: 'meta.tag entity' },
        { foreground: 'ffffff', token: 'entity.name.section' },
        { foreground: 'd5e0f3', token: 'keyword.type.variant' },
        { foreground: 'f8f8f8', token: 'source.ocaml keyword.operator.symbol' },
        { foreground: '8da6ce', token: 'source.ocaml keyword.operator.symbol.infix' },
        { foreground: '8da6ce', token: 'source.ocaml keyword.operator.symbol.prefix' },
        { fontStyle: 'underline', token: 'source.ocaml keyword.operator.symbol.infix.floating-point' },
        { fontStyle: 'underline', token: 'source.ocaml keyword.operator.symbol.prefix.floating-point' },
        { fontStyle: 'underline', token: 'source.ocaml constant.numeric.floating-point' },
        { background: 'ffffff08', token: 'text.tex.latex meta.function.environment' },
        { background: '7a96fa08', token: 'text.tex.latex meta.function.environment meta.function.environment' },
        { foreground: 'fbde2d', token: 'text.tex.latex support.function' },
        { foreground: 'ffffff', token: 'source.plist string.unquoted' },
        { foreground: 'ffffff', token: 'source.plist keyword.operator' },
    ],
    colors: {
        'editor.foreground': '#F8F8F8',
        'editor.background': '#000000',
        'editor.selectionBackground': '#253B76',
        'editor.lineHighlightBackground': '#FFFFFF0F',
        'editorCursor.foreground': '#FFFFFFA6',
        'editorWhitespace.foreground': '#FFFFFF40',
    },
};

const monacoThemeLight = {
    base: 'vs',
    inherit: true,
    rules: [
        { background: 'ffffff', token: '' },
        { foreground: '6a737d', token: 'comment' },
        { foreground: '005cc5', token: 'constant' },
        { foreground: 'e36209', token: 'entity' },
        { foreground: 'd73a49', token: 'keyword' },
        { foreground: 'd73a49', token: 'storage' },
        { foreground: '032f62', token: 'string' },
        { foreground: '032f62', token: 'meta.verbatim' },
        { foreground: '005cc5', token: 'support' },
        { foreground: 'b31d28', fontStyle: 'italic', token: 'invalid.deprecated' },
        { foreground: 'b31d28', background: 'ffeef0', token: 'invalid.illegal' },
        { foreground: '6f42c1', fontStyle: 'italic', token: 'entity.other.inherited-class' },
        { foreground: '22863a', token: 'string constant.other.placeholder' },
        { foreground: '005cc5', token: 'meta.function-call.py' },
        { foreground: '22863a', token: 'meta.tag' },
        { foreground: '22863a', token: 'meta.tag entity' },
        { foreground: '24292e', token: 'entity.name.section' },
        { foreground: '24292e', token: 'keyword.type.variant' },
        { foreground: '24292e', token: 'source.ocaml keyword.operator.symbol' },
        { foreground: '005cc5', token: 'source.ocaml keyword.operator.symbol.infix' },
        { foreground: '005cc5', token: 'source.ocaml keyword.operator.symbol.prefix' },
        { fontStyle: 'underline', token: 'source.ocaml keyword.operator.symbol.infix.floating-point' },
        { fontStyle: 'underline', token: 'source.ocaml keyword.operator.symbol.prefix.floating-point' },
        { fontStyle: 'underline', token: 'source.ocaml constant.numeric.floating-point' },
        { background: 'f6f8f8', token: 'text.tex.latex meta.function.environment' },
        { background: 'f6f8f8', token: 'text.tex.latex meta.function.environment meta.function.environment' },
        { foreground: 'd73a49', token: 'text.tex.latex support.function' },
        { foreground: '24292e', token: 'source.plist string.unquoted' },
        { foreground: '24292e', token: 'source.plist keyword.operator' },
    ],
    colors: {
        'editor.foreground': '#24292e',
        'editor.background': '#ffffff',
        'editor.selectionBackground': '#c8c8fa',
        'editor.lineHighlightBackground': '#f5f5f6',
        'editorCursor.foreground': '#24292e',
        'editorWhitespace.foreground': '#e1e4e8',
    },
};

window.KatanaMonacoEditor = function (config) {
    return {
        minimap: config.minimap || false,
        monacoContent: config.content || '',
        monacoLanguage: config.language || 'html',
        monacoPlaceholder: false,
        monacoPlaceholderText: config.placeholder || 'Start typing here',
        monacoLoader: true,
        monacoFontSize: (config.fontSize || 14) + 'px',
        monacoId: null,
        editor: null,

        decodeHTMLEntities(html) {
            const textarea = document.createElement('textarea');
            textarea.innerHTML = html;
            return textarea.value;
        },

        stripLivewireAttributes(html) {
            return html.replace(/\s+data-source="[^"]*"/g, '');
        },

        updatePlaceholder(value) {
            if (value == '') {
                this.monacoPlaceholder = true;
                return;
            }
            this.monacoPlaceholder = false;
        },

        monacoEditorFocus() {
            this.$el.dispatchEvent(
                new CustomEvent('monaco-editor-focused', { monacoId: this.monacoId })
            );
        },

        loadCss() {
            if (!window._monacoEditorCssLoaded) {
                window._monacoEditorCssLoaded = true;
                const link = document.createElement('link');
                link.rel = 'stylesheet';
                link.href = config.cssUrl;
                document.head.appendChild(link);
            }
        },

        setupEditorEvents(editor) {
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
                let { event: e, position } = drop;
                e.preventDefault();

                const file = e.dataTransfer.files[0];
                if (file && file.type.startsWith('image/')) {
                    const fileName = file.name;
                    const placeholderText = '<!-- Uploading ' + fileName + ' -->';

                    const range = new monaco.Range(
                        position.lineNumber,
                        position.column,
                        position.lineNumber,
                        position.column
                    );

                    editor.executeEdits('', [{
                        range: range,
                        text: placeholderText,
                        forceMoveMarkers: true,
                    }]);

                    const formData = new FormData();
                    formData.append('image', file);

                    const csrfToken = document.querySelector('meta[name=csrf-token]').getAttribute('content');

                    fetch('/api/image/upload', {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrfToken },
                        body: formData,
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 1) {
                            const model = editor.getModel();
                            const content = model.getValue();
                            const placeholderIndex = content.indexOf(placeholderText);

                            if (placeholderIndex !== -1) {
                                const startPos = model.getPositionAt(placeholderIndex);
                                const endPos = model.getPositionAt(placeholderIndex + placeholderText.length);
                                const replaceRange = new monaco.Range(
                                    startPos.lineNumber, startPos.column,
                                    endPos.lineNumber, endPos.column
                                );

                                const altText = data.imgAlt || fileName.split('.').slice(0, -1).join('.');
                                const imageMarkdown = '![' + altText + '](' + data.path + ')';
                                editor.executeEdits('', [{
                                    range: replaceRange,
                                    text: imageMarkdown,
                                    forceMoveMarkers: true,
                                }]);
                            }
                        } else {
                            console.error('Image upload failed:', data.message);
                            alert('Image upload failed: ' + data.message);
                            this._removePlaceholder(editor, placeholderText);
                        }
                    })
                    .catch(error => {
                        console.error('Error uploading image:', error);
                        alert('Error uploading image: ' + error.message);
                        this._removePlaceholder(editor, placeholderText);
                    });
                }
            });
        },

        _removePlaceholder(editor, placeholderText) {
            const model = editor.getModel();
            const content = model.getValue();
            const placeholderIndex = content.indexOf(placeholderText);

            if (placeholderIndex !== -1) {
                const startPos = model.getPositionAt(placeholderIndex);
                const endPos = model.getPositionAt(placeholderIndex + placeholderText.length);
                const replaceRange = new monaco.Range(
                    startPos.lineNumber, startPos.column,
                    endPos.lineNumber, endPos.column
                );

                editor.executeEdits('', [{
                    range: replaceRange,
                    text: '',
                    forceMoveMarkers: true,
                }]);
            }
        },

        init() {
            this.monacoId = this.$id('monaco-editor');
            this.$el.id = this.monacoId;
            this.monacoLoader = false;

            window.monacoInstances = window.monacoInstances || {};

            this.loadCss();

            let lineNumberAttributes = {};

            if (!config.lineNumbers) {
                lineNumberAttributes = {
                    lineNumbers: 'off',
                    lineNumberMinChars: 0,
                    glyphMargin: false,
                };
            } else {
                lineNumberAttributes = {
                    lineNumbers: config.lineNumbersStyle || 'on',
                    lineNumbersMinChars: 3,
                    lineDecorationsWidth: '12px',
                };
            }

            if (!window.MonacoEnvironment) {
                const workerBase = config.workerUrl.replace('monaco-editor-worker.js', '');
                window.MonacoEnvironment = {
                    getWorker: function (workerId, label) {
                        if (label === 'typescript' || label === 'javascript') {
                            return new Worker(workerBase + 'monaco-ts-worker.js');
                        }
                        if (label === 'html' || label === 'handlebars' || label === 'razor') {
                            return new Worker(workerBase + 'monaco-html-worker.js');
                        }
                        if (label === 'css' || label === 'scss' || label === 'less') {
                            return new Worker(workerBase + 'monaco-css-worker.js');
                        }
                        if (label === 'json') {
                            return new Worker(workerBase + 'monaco-json-worker.js');
                        }
                        return new Worker(config.workerUrl);
                    },
                };
            }

            monaco.editor.defineTheme('light', monacoThemeLight);
            monaco.editor.defineTheme('dark', monacoThemeDark);

            const el = this.$el;

            el.editor = monaco.editor.create(this.$refs.monacoEditorElement, {
                value: this.stripLivewireAttributes(this.decodeHTMLEntities(this.monacoContent)),
                padding: { top: parseInt(config.paddingTop || 0) },
                theme: config.theme || 'dark',
                fontSize: config.fontSize || 14,
                automaticLayout: true,
                language: this.monacoLanguage,
                minimap: { enabled: this.minimap },
                readOnly: config.readOnly || false,
                autoIndent: config.autoIndent !== false ? 'advanced' : 'none',
                wordWrap: config.wordWrap || 'off',
                renderWhitespace: config.showWhitespace || 'none',
                formatOnPaste: config.formatOnPaste !== false,
                suggestOnTriggerCharacters: config.suggestOnTriggerCharacters !== false,
                tabIndex: config.tabIndex || 0,
                ...lineNumberAttributes,
            });

            // Auto-size editor height based on minLines/maxLines
            if (config.minLines || config.maxLines) {
                const lineHeight = el.editor.getOption(monaco.editor.EditorOption.lineHeight);
                const paddingTop = parseInt(config.paddingTop || 0);
                const updateEditorHeight = () => {
                    const lineCount = el.editor.getModel().getLineCount();
                    const minH = config.minLines ? config.minLines * lineHeight + paddingTop : 0;
                    const maxH = config.maxLines ? config.maxLines * lineHeight + paddingTop : Infinity;
                    const contentH = Math.max(minH, Math.min(maxH, lineCount * lineHeight + paddingTop));
                    this.$refs.monacoEditorElement.style.height = contentH + 'px';
                    el.style.height = contentH + 'px';
                    el.editor.layout();
                };
                el.editor.onDidChangeModelContent(updateEditorHeight);
                updateEditorHeight();
            }

            this.setupEditorEvents(el.editor);

            el.addEventListener('monaco-editor-focused', () => {
                el.editor.focus();
            });

            this.updatePlaceholder(el.editor.getValue());

            el.editor.getModel().onDidChangeContent(() => {
                const content = el.editor.getValue();
                window.dispatchEvent(new CustomEvent('monaco-content-changed', {
                    detail: { id: this.monacoId, content: content },
                }));
            });

            window.addEventListener('monaco-editor-height-update', (event) => {
                this.$refs.monacoEditorElement.style.height = event.detail.height;
            });

            window.monacoInstances[this.monacoId] = {
                editor: el.editor,
                element: el,
            };
        },
    };
};