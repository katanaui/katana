<div class="flex justify-start h-10 bg-linear-to-br from-stone-900 to-stone-950 items-strech">
    <ul x-ref="tabContainer" class="flex overflow-x-scroll items-center w-full">
        <template x-for="([file, content], index) in Object.entries(fileTabs)">
            <li x-on:click="addCodeFromClickedTab(file)" class="relative h-full">
                <flux:tooltip position="bottom" align="start" class="h-full">
                    <span :class="{ 'bg-black' : activeTabFile === file, 'bg-stone-900 hover:bg-stone-950' : activeTabFile !== file }" :key="index" class="flex relative items-center py-1 pr-7 pl-3 h-full text-xs font-medium text-white border-r cursor-pointer border-stone-800">
                        <span x-text="getFileNameFromPath(file)" class="truncate"></span>
                        <span x-on:click="removeFile(file); $event.stopPropagation();" class="flex absolute right-0 justify-center items-center w-5 h-5 text-lg rounded -translate-x-[5px] cursor-pointer hover:bg-white/10">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-4"><path d="M5.28 4.22a.75.75 0 0 0-1.06 1.06L6.94 8l-2.72 2.72a.75.75 0 1 0 1.06 1.06L8 9.06l2.72 2.72a.75.75 0 1 0 1.06-1.06L9.06 8l2.72-2.72a.75.75 0 0 0-1.06-1.06L8 6.94 5.28 4.22Z" /></svg>
                        </span>
                    </span>
                    <flux:tooltip.content class="rounded-sm">
                        <span x-text="file" class="text-[10px] opacity-70"></span>
                    </flux:tooltip.content>
                </flux:tooltip>
            </li>
        </template>
    </ul>
</div>