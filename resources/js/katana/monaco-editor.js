// Monaco Editor - Minimal ESM setup (no heavy language services/workers)
import * as monaco from 'monaco-editor/esm/vs/editor/editor.api';

// Basic language tokenizers (syntax highlighting only - no workers needed)
import 'monaco-editor/esm/vs/basic-languages/html/html.contribution';
import 'monaco-editor/esm/vs/basic-languages/css/css.contribution';
import 'monaco-editor/esm/vs/basic-languages/javascript/javascript.contribution';
import 'monaco-editor/esm/vs/basic-languages/typescript/typescript.contribution';
import 'monaco-editor/esm/vs/basic-languages/markdown/markdown.contribution';
import 'monaco-editor/esm/vs/basic-languages/php/php.contribution';
import 'monaco-editor/esm/vs/basic-languages/yaml/yaml.contribution';
import 'monaco-editor/esm/vs/basic-languages/python/python.contribution';
import 'monaco-editor/esm/vs/basic-languages/sql/sql.contribution';
import 'monaco-editor/esm/vs/basic-languages/xml/xml.contribution';
import 'monaco-editor/esm/vs/basic-languages/shell/shell.contribution';

// No language services (HTML, CSS, JSON, TypeScript) — these require heavy workers
// and will freeze the browser if workers fail to load. Basic syntax highlighting
// from the imports above is sufficient for a code editor component.

// Dark theme
const darkTheme = {
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

// Light theme
const lightTheme = {
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

// Register themes
monaco.editor.defineTheme('dark', darkTheme);
monaco.editor.defineTheme('light', lightTheme);

// Instance registry
const editors = {};

function createEditor(id, element, config = {}) {
    if (editors[id]) {
        editors[id].dispose();
        delete editors[id];
    }

    const editor = monaco.editor.create(element, {
        value: config.value || '',
        language: config.language || 'markdown',
        theme: config.theme || 'dark',
        fontSize: config.fontSize || '15px',
        wordWrap: config.wordWrap !== undefined ? config.wordWrap : 'on',
        automaticLayout: false,
        scrollBeyondLastLine: false,
        minimap: { enabled: config.minimap !== undefined ? config.minimap : false },
        lineNumbers: config.lineNumbers !== undefined ? (config.lineNumbers ? 'on' : 'off') : 'on',
        lineNumbersMinChars: config.lineNumbers ? 3 : 0,
        lineDecorationsWidth: config.lineNumbers ? '12px' : 0,
        glyphMargin: config.lineNumbers !== undefined ? config.lineNumbers : true,
        tabIndex: config.tabIndex || 0,
        padding: {
            top: config.paddingTop || 0,
        },
    });

    // One-time explicit layout with current container dimensions
    editor.layout({ width: element.clientWidth, height: element.clientHeight });

    editors[id] = editor;
    return editor;
}

function destroyEditor(id) {
    if (editors[id]) {
        editors[id].dispose();
        delete editors[id];
    }
}

function handleImageDrop(editor, drop) {
    const { event: e, position } = drop;
    e.preventDefault();

    const file = e.dataTransfer.files[0];
    if (!file || !file.type.startsWith('image/')) return;

    const fileName = file.name;
    const placeholderText = '<!-- Uploading ' + fileName + ' -->';

    // Insert placeholder at drop position
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

    // Upload the image
    const formData = new FormData();
    formData.append('image', file);

    const csrfToken = document.querySelector('meta[name=csrf-token]')?.getAttribute('content');

    fetch('/api/image/upload', {
        method: 'POST',
        headers: csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {},
        body: formData,
    })
    .then(response => response.json())
    .then(data => {
        const model = editor.getModel();
        const content = model.getValue();
        const placeholderIndex = content.indexOf(placeholderText);

        if (placeholderIndex === -1) return;

        const startPos = model.getPositionAt(placeholderIndex);
        const endPos = model.getPositionAt(placeholderIndex + placeholderText.length);
        const replaceRange = new monaco.Range(
            startPos.lineNumber,
            startPos.column,
            endPos.lineNumber,
            endPos.column
        );

        if (data.status === 1) {
            const altText = data.imgAlt || fileName.split('.').slice(0, -1).join('.');
            const imageMarkdown = '![' + altText + '](' + data.path + ')';
            editor.executeEdits('', [{
                range: replaceRange,
                text: imageMarkdown,
                forceMoveMarkers: true,
            }]);
        } else {
            console.error('Image upload failed:', data.message);
            editor.executeEdits('', [{
                range: replaceRange,
                text: '',
                forceMoveMarkers: true,
            }]);
        }
    })
    .catch(error => {
        console.error('Error uploading image:', error);
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
                forceMoveMarkers: true,
            }]);
        }
    });
}

// Export globally
window.KatanaMonaco = {
    monaco,
    createEditor,
    destroyEditor,
    handleImageDrop,
};
