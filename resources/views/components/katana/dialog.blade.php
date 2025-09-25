@props([
    'open' => false,
    'close_button' => true
])

<div 
    x-data="{ modalOpen: false }"
    @keydown.escape.window="modalOpen = false"
    class="relative z-50 w-auto h-auto"
>
    @if($trigger ?? false)
        {!! $trigger !!}
    @else
        <button @click="modalOpen=true" class="inline-flex justify-center items-center px-4 py-2 h-10 text-sm font-medium bg-white rounded-md border transition-colors hover:bg-neutral-100 active:bg-white focus:bg-white focus:outline-none focus:ring-2 focus:ring-neutral-200/60 focus:ring-offset-2 disabled:opacity-50 disabled:pointer-events-none">Open</button>
    @endif
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
                class="relative px-7 py-6 w-full bg-white sm:max-w-lg sm:rounded-lg">
                @if($header ?? false)
                    <div class="flex justify-between items-center pb-2">
                        <h3 class="text-lg font-semibold">{{ $header }}</h3>
                        @if($close_button ?? true)
                            <button @click="modalOpen=false" class="flex absolute top-0 right-0 justify-center items-center mt-5 mr-5 w-8 h-8 text-gray-600 rounded-full hover:text-gray-800 hover:bg-gray-50">
                                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>  
                            </button>
                        @endif
                    </div>
                @endif
                {{ $slot }}
            </div>
        </div>
    </template>
</div>