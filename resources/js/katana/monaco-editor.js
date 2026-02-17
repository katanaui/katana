import * as monaco from 'monaco-editor';

window.monaco = monaco;

const monacoThemeDark = { "base": "vs-dark", "inherit": true };

const monacoThemeLight = { "base": "vs", "inherit": true };

window.KatanaMonacoEditor = function (config) {
    return {
        monacoContent: config.content || '',
        monacoLanguage: config.language || 'html',
        monacoPlaceholder: false,
        monacoPlaceholderText: config.placeholder || 'Start typing here',
        monacoLoader: true,
        monacoFontSize: '15px',
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

            let lineNumberAttributes = {
                lineNumbers: false,
                lineNumberMinChars: 0,
                glyphMargin: false,
            };

            if (config.lineNumbers) {
                lineNumberAttributes = {
                    lineNumbersMinChars: 3,
                    lineDecorationsWidth: '12px',
                };
            }

            if (!window.MonacoEnvironment) {
                window.MonacoEnvironment = {
                    getWorker: function (workerId, label) {
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
                wordWrap: true,
                theme: config.theme || 'dark',
                fontSize: this.monacoFontSize,
                automaticLayout: true,
                language: this.monacoLanguage,
                minimap: { enabled: false },
                tabIndex: config.tabIndex || 0,
                ...lineNumberAttributes,
            });

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
