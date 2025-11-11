@props([
    'open' => false,
    'close_button' => true,
])

<div x-data="{ modalOpen: false }"  class="relative z-50 w-auto h-auto" @keydown.escape.window="modalOpen = false">
    <div @click="modalOpen=true">
        @if ($trigger ?? false)
            {!! $trigger !!}
        @else
            <button class="inline-flex justify-center items-center px-4 py-2 h-10 text-sm font-medium bg-white rounded-md border transition-colors hover:bg-neutral-100 focus:bg-white focus:outline-none focus:ring-2 focus:ring-neutral-200/60 focus:ring-offset-2 active:bg-white disabled:pointer-events-none disabled:opacity-50">Open</button>
        @endif
    </div>
    <template x-teleport="body">
        <div x-show="modalOpen" x-cloak 
            @open-modal.window="if($event.detail.id === $el.id) modalOpen=true"
            @close-modal.window="if($event.detail.id === $el.id) modalOpen=false"
            x-init="console.log('Modal component initialized with id:', $el.id);"
            {{ $attributes->withoutTwMergeClasses()->only('id') }}
            class="fixed left-0 top-0 z-[99] flex h-screen w-screen items-center justify-center">
            <div class="absolute inset-0 w-full h-full bg-black/60" x-show="modalOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" @click="modalOpen=false"></div>
            <div x-show="modalOpen" x-trap.inert.noscroll="modalOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="flex justify-center items-center w-full h-full">
                <div {{  $attributes->twMerge('relative px-7 py-6 w-full bg-white sm:max-w-lg sm:rounded-lg') }}>
                    @if ($close_button ?? true)
                        <div class="hidden absolute top-0 right-0 pt-4 pr-4 sm:block">
                            <button class="text-gray-400 bg-white rounded-md dark:bg-gray-800 dark:hover:text-gray-300 dark:focus:outline-white hover:text-gray-500 focus:outline-2 focus:outline-offset-2 focus:outline-indigo-600" @click="modalOpen=false" type="button" aria-expanded="true">
                                <span class="sr-only">Close</span>
                                <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" data-slot="icon" aria-hidden="true">
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
