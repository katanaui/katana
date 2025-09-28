@props([
    'open' => false,
    'close_button' => true
])

<div 
    x-data="{ modalOpen: false }"
    @keydown.escape.window="modalOpen = false"
    class="relative z-50 w-auto h-auto"
>
    <div @click="modalOpen=true">
        @if($trigger ?? false)
            {!! $trigger !!}
        @else
            <button class="inline-flex justify-center items-center px-4 py-2 h-10 text-sm font-medium bg-white rounded-md border transition-colors hover:bg-neutral-100 active:bg-white focus:bg-white focus:outline-none focus:ring-2 focus:ring-neutral-200/60 focus:ring-offset-2 disabled:opacity-50 disabled:pointer-events-none">Open</button>
        @endif
    </div>
    <template x-teleport="body">
        <div x-show="modalOpen" class="fixed top-0 left-0 z-[99] flex items-center justify-center w-screen h-screen" x-cloak>
            <div x-show="modalOpen" 
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-300"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                @click="modalOpen=false" class="absolute inset-0 w-full h-full bg-black/40"></div>
            <div x-show="modalOpen"
                x-trap.inert.noscroll="modalOpen"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                class="w-full max-w-lg rounded-2xl p-1 shadow-xl border border-white/20 bg-black/10 backdrop-blur-xl">
                <div class="relative px-6 py-5 w-full bg-white border border-black/30 sm:max-w-lg sm:rounded-xl">
                    @if($close_button ?? true)
                        <div class="relative flex items-center justify-between">
                            <h2 class="font-medium">{{ $title ?? 'Modal Title' }}</h2>
                        </div>
                        <div class="hidden absolute top-0 right-0 pt-5 pr-5 sm:block">
                            <button @click="modalOpen=false" type="button" command="close" commandfor="dialog" class="text-gray-400 bg-white cursor-pointer rounded-full hover:text-gray-500 focus:outline-2 focus:outline-offset-2 focus:outline-primary/20 dark:bg-gray-800 dark:hover:text-gray-300 dark:focus:outline-white" aria-expanded="true">
                                <span class="sr-only">Close</span>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" data-slot="icon" aria-hidden="true" class="size-6">
                                    <path d="M6 18 18 6M6 6l12 12" stroke-linecap="round" stroke-linejoin="round"></path>
                                </svg>
                            </button>
                        </div>
                    @endif
                    {{ $slot }}
                </div>
            </div>
        </div>
    </template>
</div>