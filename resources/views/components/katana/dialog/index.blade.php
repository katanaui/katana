@props([
    'open' => false,
    'close_button' => true,
])

<div x-data="{ modalOpen: '{{ $open }}' }" class="relative z-50 h-auto w-auto" @keydown.escape.window="modalOpen = false" {{ $attributes->except('id') }}>
    <div @click="modalOpen = true;">{{ $slot ?? 'Open drawer' }}</div>
    <template x-teleport="body">
<<<<<<< HEAD
        <div x-show="modalOpen" x-cloak class="fixed left-0 top-0 z-[99] flex h-screen w-screen items-center justify-center" {{ $attributes->only('id') }} @open-dialog.window="if($event.detail.id === $el.id) modalOpen=true" @close-dialog.window="if($event.detail.id === $el.id) modalOpen=false">
            <div x-show="modalOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="absolute inset-0 h-full w-full bg-black/40" @click="modalOpen=false"></div>
            <div x-show="modalOpen" x-trap.inert.noscroll="modalOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="relative w-full bg-white px-7 py-6 sm:max-w-lg sm:rounded-lg">
                @if ($header ?? false)
                    <div class="-mt-6 flex h-20 items-center justify-between">
                        <div class="relative flex h-full items-center">
=======
        <div x-show="modalOpen" 
            {{ $attributes->only('id') }}
            class="fixed top-0 left-0 z-99 flex items-center justify-center w-screen h-screen"
            @open-dialog.window="if($event.detail.id === $el.id) modalOpen=true"
            @close-dialog.window="if($event.detail.id === $el.id) modalOpen=false"
             x-cloak>
            <div x-show="modalOpen" 
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-300"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                @click="modalOpen=false" class="absolute inset-0 w-full h-full bg-black bg-opacity-40"></div>
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
                  <div class="flex justify-between items-center -mt-6 h-20">  
                        <div class="flex relative items-center h-full">
>>>>>>> ce93a45476b2d817eb5e85b2232dd61263b5867d
                            {{ $header }}
                        </div>
                    </div>
                @endif
                @if ($close_button ?? true)
                    <button class="absolute right-0 top-0 mr-5 mt-5 flex h-8 w-8 items-center justify-center rounded-full text-gray-600 hover:bg-gray-50 hover:text-gray-800" @click="modalOpen=false">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                @endif
                <div class="relative w-auto">
                    {{ $content }}
                </div>
            </div>
        </div>
    </template>
</div>
