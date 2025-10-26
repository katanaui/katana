@props([
    'position' => 'right',
    'parent' => null,
    'open' => false,
    'header' => null,
    'footer' => null,
    'content' => null,
])

@php
<<<<<<< HEAD
    $classes = \Illuminate\Support\Arr::toCssClasses(['flex overflow-hidden justify-center relative items-center w-full bg-white sm:rounded-[var(--radius)] border-0 sm:border starting:opacity-0 opacity-100 shadow-sm border-stone-200', 'ml-auto w-full h-full' => $position == 'right', 'mr-auto w-full h-full' => $position == 'left']);
=======
    $classes = \Illuminate\Support\Arr::toCssClasses([
        'flex overflow-hidden justify-center relative items-center w-full bg-white sm:rounded-(--radius) border-0 sm:border starting:opacity-0 opacity-100 shadow-sm border-stone-200',
        'ml-auto w-full h-full' => $position == 'right',
        'mr-auto w-full h-full' => $position == 'left',
    ]);
>>>>>>> ce93a45476b2d817eb5e85b2232dd61263b5867d
@endphp

<div
    x-data="{
        open: {{ $open ? 'true' : 'false' }},
        closeDrawer() {
            this.open = false;
        }
    }" x-init="$watch('open', function(value) {
        if (value) {
            document.body.style.overflow = 'hidden';
            // If this drawer has a parent, scale it down
            if ('{{ $parent }}') {
                document.querySelector('{{ $parent }}').classList.add('scale-[0.98]', 'brightness-[0.95]', 'ease-out', 'sm:duration-500', 'delay-200', 'duration-300', '-translate-x-5');
                setTimeout(() => document.querySelector('{{ $parent }}').classList.remove('delay-200'), 200);
            }
        } else {
            if ('{{ $parent }}') {
                setTimeout(() => document.querySelector('{{ $parent }}').classList.remove('ease-out', 'sm:duration-500', 'duration-300'), 300)
                document.querySelector('{{ $parent }}').classList.remove('scale-[0.98]', 'brightness-[0.95]', '-translate-x-5');
            }
            document.body.style.overflow = '';
            window.dispatchEvent(new CustomEvent('drawer-closed', { detail: { 'id': $refs.container.id } }));
        }
    });" {{ $attributes->except('id') }} @keydown.escape.window="open = false">
    <div @click="open = true;">{{ $slot ?? 'Open drawer' }}</div>

    <template x-teleport="body">
        <div x-show="open" class="fixed inset-0 z-50 h-screen w-screen">
            <!-- Backdrop -->
            <div x-show="open" x-transition:enter="transition-opacity ease-linear duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity ease-linear duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 z-40 bg-black/40" @click="open = false" style="display: none;"></div>

            <!-- Drawer -->
            <div x-show="open" x-ref="container" x-transition:enter="transform transition ease-in-out duration-300 sm:duration-500" x-transition:enter-start="@if ($position == 'right') translate-x-full @elseif($position == 'left') -translate-x-full @endif" x-transition:enter-end="translate-x-0" x-transition:leave="transform transition ease-in-out duration-300 sm:duration-500" x-transition:leave-start="translate-x-0" x-transition:leave-end="@if ($position == 'right') translate-x-full @elseif($position == 'left') -translate-x-full @endif" role="dialog" aria-modal="true" @class([
                'fixed inset-y-0 right-0 z-50 w-full max-w-2xl sm:p-3',
                'right-0' => $position == 'right',
                'left-0' => $position == 'left',
            ])>
                <div
<<<<<<< HEAD
                    class="{{ $classes }}" @open-drawer.window="if($event.detail.id === $el.id) open=true" @close-drawer.window="console.log('gotit'); if($event.detail.id === $el.id) open=false" {{ $attributes->only('id') }}>
                    @if ($header ?? false)
                        <div class="absolute top-0 z-50 flex h-16 w-full shrink-0 items-center bg-white/90 px-5 font-semibold text-stone-700 backdrop-blur-sm sm:rounded-t-xl sm:px-6">
                            {{ $header }}
                        </div>
                    @endif

                    <div class="absolute right-0 top-0 z-[51]">
                        <button class="absolute right-0 top-0 mr-3 mt-3 cursor-pointer rounded-full p-2 hover:bg-black/10" @click="open=false">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500" viewBox="0 0 256 256">
                                <path fill="none" d="M0 0h256v256H0z" />
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16" d="M200 56 56 200M200 200 56 56" />
                            </svg>
=======
                    @open-drawer.window="if($event.detail.id === $el.id) open=true"
                    @close-drawer.window="console.log('gotit'); if($event.detail.id === $el.id) open=false"
                    class="{{ $classes }}" {{ $attributes->only('id') }}>
                    @if($header ?? false)
                        <div class="flex absolute font-semibold top-0 z-50 shrink-0 items-center px-5 w-full h-16 backdrop-blur-sm text-stone-700 sm:px-6 sm:rounded-t-xl bg-white/90">
                            {{ $header }}
                        </div>
                    @endif
                    
                    <div class="absolute top-0 right-0 z-51">
                        <button @click="open=false" class="absolute top-0 right-0 p-2 mt-3 mr-3 rounded-full cursor-pointer hover:bg-black/10">
                            <svg class="w-5 h-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256"><path fill="none" d="M0 0h256v256H0z"/><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16" d="M200 56 56 200M200 200 56 56"/></svg>
>>>>>>> ce93a45476b2d817eb5e85b2232dd61263b5867d
                        </button>
                    </div>

                    <div @class([
                        'block overflow-y-auto px-5 pt-16 w-full h-full sm:px-6',
                        'pb-24' => $footer,
                        'sm:pb-6 pb-5' => !$footer,
                    ])>
                        <div class="h-100 flex min-h-full w-full flex-col items-stretch justify-stretch">
                            @if (!$content)
                                <x-katana.placeholder />
                            @else
                                {{ $content }}
                            @endif
                        </div>
                    </div>
<<<<<<< HEAD

                    @if ($footer ?? false)
                        <div class="absolute bottom-0 z-[999999] flex h-20 w-full shrink-0 items-center justify-between border-t border-gray-100 bg-white/70 px-5 backdrop-blur-sm sm:rounded-b-xl sm:px-8">
=======
                    
                    @if($footer ?? false)
                        <div class="flex absolute bottom-0 z-999999 shrink-0 justify-between items-center px-5 w-full h-20 border-t border-gray-100 backdrop-blur-sm sm:px-8 sm:rounded-b-xl bg-white/70">
>>>>>>> ce93a45476b2d817eb5e85b2232dd61263b5867d
                            {{ $footer }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </template>
</div>
