@props([
    'position' => 'right',
    'parent' => null,
    'open' => false,
    'header' => null,
    'footer' => null,
    'content' => null,
    'zIndex' => '50'
])

@php
    $classes = \Illuminate\Support\Arr::toCssClasses([
        'flex overflow-hidden justify-center relative items-center w-full bg-background sm:rounded-medium border-0 sm:border starting:opacity-0 opacity-100 shadow-sm border-foreground/10 dark:border-foreground/15',
        'ml-auto w-full h-full' => $position == 'right',
        'mr-auto w-full h-full' => $position == 'left',
    ]);
@endphp

<div
    x-data="{ 
        open: {{ $open ? 'true' : 'false' }},
        closeDrawer() {
            this.open = false;
        }
    }"
    {{ $attributes->except('id') }}
    x-init="
        $watch('open', function(value) {
            if (value) {
                document.body.style.overflow = 'hidden';
                // If this drawer has a parent, scale it down
                if ('{{ $parent }}') {
                    document.querySelector('{{ $parent }}').classList.add('scale-[0.98]', 'brightness-[0.95]', 'ease-out', 'sm:duration-500', 'delay-200', 'duration-300', '-translate-x-5');
                    setTimeout(() =>  document.querySelector('{{ $parent }}').classList.remove('delay-200'), 200);
                }
            } else {
                if ('{{ $parent }}') {
                    setTimeout(() =>  document.querySelector('{{ $parent }}').classList.remove('ease-out', 'sm:duration-500', 'duration-300'), 300)
                    document.querySelector('{{ $parent }}').classList.remove('scale-[0.98]', 'brightness-[0.95]', '-translate-x-5');
                }
                document.body.style.overflow = '';
                window.dispatchEvent(new CustomEvent('drawer-closed', { detail: { 'id' : $refs.container.id }}));
            }
        });
    "
    @keydown.escape.window="open = false"
>
    <div @click="open = true;">{{ $slot ?? 'Open drawer' }}</div>

    <template x-teleport="body">
        <div x-show="open" class="fixed inset-0 w-screen h-screen" data-poopstick style="z-index: {{ $zIndex }}"> 
            <!-- Backdrop -->
            <div 
                x-show="open"
                x-transition:enter="transition-opacity ease-linear duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition-opacity ease-linear duration-300"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                @click="open = false"
                class="fixed inset-0 z-40 bg-black/40"
                style="display: none;"
            ></div>

            <!-- Drawer -->
            <div 
                x-show="open"
                x-ref="container"
                role="dialog"
                aria-modal="true"
                x-transition:enter="transform transition ease-in-out duration-300 sm:duration-500" 
                x-transition:enter-start="@if($position == 'right') translate-x-full @elseif($position == 'left') -translate-x-full @endif"
                x-transition:enter-end="translate-x-0" 
                x-transition:leave="transform transition ease-in-out duration-300 sm:duration-500" 
                x-transition:leave-start="translate-x-0" 
                x-transition:leave-end="@if($position == 'right') translate-x-full @elseif($position == 'left') -translate-x-full @endif"
                @class([
                    'fixed inset-y-0 right-0 z-50 w-full max-w-2xl sm:p-3',
                    'right-0' => $position == 'right',
                    'left-0' => $position == 'left'
            ])>
                <div
                    @open-drawer.window="if($event.detail.id === $el.id) open=true"
                    @close-drawer.window="console.log('gotit'); if($event.detail.id === $el.id) open=false"
                    class="{{ $classes }}" {{ $attributes->only('id') }}>
                    @if($header ?? false)
                        <div {{ $attributes->twMergeFor('header', 'flex absolute font-semibold top-0 z-50 shrink-0 items-center px-5 w-full h-16 backdrop-blur-sm text-foreground sm:px-6 sm:rounded-t-xl bg-background/90') }}>
                            {{ $header }}
                        </div>
                    @endif
                    
                    <div class="absolute top-0 right-0 z-51">
                        <button @click="open=false" class="absolute group top-0 right-0 p-2 mt-3.5 mr-5 sm:mr-6 rounded-full cursor-pointer hover:bg-foreground/5 dark:hover:bg-foreground/15">
                            <svg class="w-5 h-5 text-foreground/50 group-hover:text-foreground" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256"><path fill="none" d="M0 0h256v256H0z"/><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16" d="M200 56 56 200M200 200 56 56"/></svg>
                        </button>
                    </div>
                    
                    <div @class([
                        'block overflow-y-auto px-5 pt-16 w-full h-full sm:px-6',
                        'pb-24' => $footer,
                        'sm:pb-6 pb-5' => !$footer
                    ])>
                        <div class="w-full h-100 min-h-full flex flex-col justify-stretch items-stretch">
                            @if(!$content)
                                <x-katana.placeholder />
                            @else
                                {{ $content }}
                            @endif
                        </div>
                    </div>
                    
                    @if($footer ?? false)
                        <div {{ $attributes->twMergeFor('footer', 'flex absolute bottom-0 z-999999 shrink-0 justify-end gap-2 items-center px-5 w-full h-20 border-t border-accent backdrop-blur-sm sm:px-8 sm:rounded-b-xl bg-background/70') }}>
                            {{ $footer }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </template>
</div>

