<div 
    x-data="codeEditor()"
    @file-selected.window="addFileToTabs($event.detail[0].file, $event.detail[0].content); setCodeAndActiveTab($event.detail[0].content, $event.detail[0].file);"
    class="flex flex-col w-full h-full">
    <div x-show="Object.keys(fileTabs).length > 0" class="flex flex-col w-full h-full">
        <x-katana.code-editor.tabs />
        <div class="overflow-hidden flex-1 w-full h-100">
            <x-katana.monaco-editor />
        </div>
    </div>
    <div x-show="Object.keys(fileTabs).length === 0" class="flex justify-center items-center w-full h-full text-white">
        <x-no-file-selected />
    </div>
</div>

<script>
function codeEditor() {
    return {
        activeTabFile: '', 
        fileTabs: {},
        getFileNameFromPath(path){
            return path.split('/').pop();
        },
        addCodeFromClickedTab(file){
            this.setCodeAndActiveTab(this.fileTabs[file], file);
        },
        setCodeAndActiveTab(code, activeTabFile){
            window.dispatchEvent(new CustomEvent('set-code', { detail: { code: code } }));
            this.activeTabFile = activeTabFile;
            setTimeout(() => { this.$refs.tabContainer.scrollLeft = this.$refs.tabContainer.scrollWidth; }, 100);
        },
        addFileToTabs(file, content){
            this.fileTabs[file] = content;
        },
        removeFile(file){
            delete this.fileTabs[file];
            if (this.activeTabFile === file) {
                const remainingFiles = Object.keys(this.fileTabs);
                this.activeTabFile = remainingFiles.length > 0 ? remainingFiles[0] : '';
                if (!this.fileTabs[this.activeTabFile]) {
                    window.dispatchEvent(new CustomEvent('set-code', { detail: { code: '' } }));
                } else {
                    window.dispatchEvent(new CustomEvent('set-code', { detail: { code: this.fileTabs[this.activeTabFile] } }));
                }
                
            }
            /*console.log(this.activeTabIndex, index);
            if (this.activeTabIndex === index) {
                // set active tab to previous index
                this.activeTabIndex = 0;
            }*/
        }
    }
}
</script>