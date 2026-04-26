@props([
    'open' => false,
    'close_button' => true,
    'zIndex' => 50,
])

<div x-data="{ modalOpen: false }" x-trap="modalOpen" x-init=" $watch('modalOpen', function(value) {
     if (value) {
         document.body.style.overflow = 'hidden';
     } else {
         document.body.style.overflow = 'auto';
     }
 });" class="relative h-auto w-auto" @keydown.escape.window="modalOpen = false">
    <div @click="modalOpen=true">
        @if (empty(trim($slot)))
            <x-katana.button variant="outline">Open</x-katana.button>
        @else
            {!! $slot !!}
        @endif
    </div>
    <template x-teleport="body">
        <div x-show="modalOpen" x-cloak x-init="console.log('Modal component initialized with id:', $el.id);" class="fixed left-0 top-0 flex h-dvh w-screen items-center justify-center" @open-modal.window="if($event.detail.id === $el.id) modalOpen=true" @close-modal.window="if($event.detail.id === $el.id) modalOpen=false" {{ $attributes->withoutTwMergeClasses()->only('id') }} style="z-index: {{ $zIndex }}">
            <div x-show="modalOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="absolute inset-0 h-full w-full bg-black/60" @click="modalOpen=false"></div>
            <div x-show="modalOpen" x-trap.inert.noscroll="modalOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="flex h-full w-full items-center justify-center">
                <div {{ $attributes->twMerge('relative px-7 py-6 w-full dark:border dark:border-accent bg-background text-foreground sm:max-w-lg sm:rounded-medium') }}>
                    @if (isset($header))
                        <h2 {{ $attributes->twMergeFor('header', '-translate-y-1.5 text-lg mb-2') }}>{{ $header }}</h2>
                    @endif
                    {{ $content ?? '' }}
                    @if (isset($footer))
                        <div {{ $attributes->twMergeFor('footer', 'w-full flex items-center justify-end gap-2 translate-y-1.5 mt-5') }}>{{ $footer }}</div>
                    @endif
                    @if ($close_button ?? true)
                        <div class="absolute right-0 top-0 z-50 hidden pr-4 pt-4 sm:block">
                            <button class="text-foreground/30 hover:bg-accent hover:text-foreground/50 focus-visible:ring-foreground/20 rounded-md bg-transparent p-1 focus:outline-none focus-visible:ring-2" @click="modalOpen=false" type="button" aria-expanded="true">
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
