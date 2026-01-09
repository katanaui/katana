@props([
    'open' => false,
    'close_button' => true,
    'zIndex' => 50,
])

<div x-data="{ modalOpen: false }" 
    x-trap="modalOpen"
    class="relative w-auto h-auto" 
    @keydown.escape.window="modalOpen = false"
    x-init=" 
        $watch('modalOpen', function(value) {
            if(value) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = 'auto';
            }
        });
    ">
    <div @click="modalOpen=true">
        @if (empty(trim($slot)))
            <x-button variant="outline">Open</x-button>
        @else
            {!! $slot !!}
        @endif
    </div>
    <template x-teleport="body">
        <div x-show="modalOpen" x-cloak 
            @open-modal.window="if($event.detail.id === $el.id) modalOpen=true"
            @close-modal.window="if($event.detail.id === $el.id) modalOpen=false"
            x-init="console.log('Modal component initialized with id:', $el.id);"
            {{ $attributes->withoutTwMergeClasses()->only('id') }}
            class="fixed left-0 top-0 flex h-screen w-screen items-center justify-center" style="z-index: {{ $zIndex }}">
            <div class="absolute inset-0 w-full h-full bg-black/60" x-show="modalOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" @click="modalOpen=false"></div>
            <div x-show="modalOpen" x-trap.inert.noscroll="modalOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="flex justify-center items-center w-full h-full">
                <div {{  $attributes->twMerge('relative px-7 py-6 w-full dark:border dark:border-accent bg-background text-foreground sm:max-w-lg sm:rounded-medium') }}>
                    @if(isset($header))
                        <h2 {{ $attributes->twMergeFor('header', '-translate-y-1.5 text-lg mb-2') }}>{{ $header }}</h2>
                    @endif
                    {{ $content ?? '' }}
                    @if(isset($footer))
                        <div {{ $attributes->twMergeFor('footer', 'w-full flex items-center justify-end gap-2 translate-y-1.5 mt-5') }}>{{ $footer }}</div>
                    @endif
                    @if ($close_button ?? true)
                        <div class="hidden absolute top-0 right-0 pt-4 pr-4 sm:block z-50">
                            <button class="text-foreground/30 p-1 rounded-md hover:bg-accent bg-transparent hover:text-foreground/50 focus-visible:ring-2 focus-visible:ring-foreground/20 focus:outline-none" @click="modalOpen=false" type="button" aria-expanded="true">
                                <span class="sr-only">Close</span>
                                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" data-slot="icon" aria-hidden="true">
                                    <path d="M6 18 18 6M6 6l12 12" stroke-linecap="round" stroke-linejoin="round"></path>
                                </svg>
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </template>
</div>
