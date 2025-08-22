<div x-show="linkModal"
    @click.outside="linkModal=false"
    @keyup.enter="insertLink"
    @keyup.escape="linkModal=false"
    x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-y-10 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-out duration-200"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 translate-y-1 scale-95"
 class="absolute top-1/2 left-1/2 z-50 w-full max-w-xs h-auto -translate-x-1/2 -translate-y-1/2" x-cloak>
    <input x-ref="linkInput" x-model="linkHref" type="text" class="px-3 py-2 w-full h-full text-sm rounded-lg border shadow-sm backdrop-blur-sm bg-white/80 border-stone-200 focus:ring-2 focus:ring-stone-200/30 focus:border-stone-300 focus:outline-none" />
    <div class="flex absolute top-0 right-0 items-center pr-2 space-x-2 w-auto h-full">
        <button @click="insertLink()" class="p-1 w-6 h-6 text-stone-400 hover:text-stone-700">
            <svg class="w-full h-full" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"><path d="M20 6 9 17l-5-5"/></svg>
        </button>
        <button @click="unLink(); linkModal=false" class="p-1 w-6 h-6 text-stone-400 hover:text-stone-700">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"><path d="m18.84 12.25 1.72-1.71h-.02a5.004 5.004 0 0 0-.12-7.07 5.006 5.006 0 0 0-6.95 0l-1.72 1.71M5.17 11.75l-1.71 1.71a5.004 5.004 0 0 0 .12 7.07 5.006 5.006 0 0 0 6.95 0l1.71-1.71M8 2v3M2 8h3M16 19v3M19 16h3"/></svg>
        </button>
    </div>
</div>