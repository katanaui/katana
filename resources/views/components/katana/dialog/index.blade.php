@props([
    'drawer' => false,
    'side' => 'right',
    'parent' => null,
    'open' => false
])

<style>
dialog[open]{
    display:flex;
}

.modal[open] {
  display: flex;
}

dialog:not([open]) {
  pointer-events: none;
  opacity: 0;
}

dialog:modal {
  max-width: 100vw;
  max-height: 100vh;
}

dialog[open]::backdrop {
  animation: backdrop-fade-in 0.3s ease forwards;
}

dialog.closing::backdrop {
  animation: backdrop-fade-out 0.3s ease forwards;
}

@keyframes backdrop-fade-in {
  from {
    opacity: 0;
  }
  to {
    opacity: 0.4;
  }
}

@keyframes backdrop-fade-out {
  from {
    opacity: 0.4;
  }
  to {
    opacity: 0;
  }
}

dialog::backdrop {
  background: rgba(0,0,0,0.4);
  opacity: 0;
}

dialog[open]::backdrop {
  opacity: 0.4;
}
</style>

@php
    $classes = \Illuminate\Support\Arr::toCssClasses([
        'flex overflow-hidden justify-center relative items-center  bg-white rounded-xl border starting:opacity-0 opacity-100 shadow-sm border-stone-200',
        'starting:translate-y-2 translate-y-0' => !$drawer,
        'ml-auto w-full h-full' => $drawer
    ]);
@endphp

<div
    x-data="{ 
        open: {{ $open ? 'true' : 'false' }},
        closing: false,
        clickedBackground(event, element){
            if(event.target == element){
                console.log('made');
                this.open=false;
            }
        },
        closeDialog() {
            this.closing = true;
            // Wait for transition to complete before actually closing
            setTimeout(() => {
                $refs.dialog.close();
                this.open = false;
                this.closing = false;
                
                if ('{{ $parent }}') {
                    setTimeout(() =>  document.querySelector('{{ $parent }}').classList.remove('ease-out', 'sm:duration-500', 'duration-300'), 300)
                    document.querySelector('{{ $parent }}').classList.remove('scale-[0.98]', 'brightness-[0.95]', '-translate-x-5');
                }
            }, 300); // Match this with your transition duration
        }
    }"
    x-init="
        $watch('open', function(value) {
            if (value) {
                $refs.dialog.showModal();
                // If this dialog has a parent, scale it down
                if ('{{ $parent }}') {
                    document.querySelector('{{ $parent }}').classList.add('scale-[0.98]', 'brightness-[0.95]', 'ease-out', 'sm:duration-500', 'delay-200', 'duration-300', '-translate-x-5');
                    setTimeout(() =>  document.querySelector('{{ $parent }}').classList.remove('delay-200'), 200);
                }
            } else {
                closeDialog();
                window.dispatchEvent(new CustomEvent('dialog-closed', { detail: { 'id' : $refs.container.id }}));
            }
        });
    "
>
    <div @click="open = true;">{{ $slot ?? 'Open dialog' }}</div>

    <dialog 
        x-ref="dialog"
        class="flex overflow-hidden m-auto w-screen bg-transparent outline-none h-dvh"
        @close="open = false;"
        wire:ignore.self
        :class="{ 'closing' : closing }"
        x-init="
            $el.addEventListener('cancel', (event) => {
                event.preventDefault();
                closing=true;
                open=false;
            });
        "
    >
        <div @click="clickedBackground($event, $el)" class="block overflow-hidden absolute inset-0 p-3 w-full h-full">
            
                <div 
                    x-show="open" 
                    @click.away="open = false"
                    x-ref="container"
                    x-transition:enter="transform transition ease-in-out duration-300 sm:duration-500" 
                    x-transition:enter-start="translate-x-full" 
                    x-transition:enter-end="translate-x-0" 
                    x-transition:leave="transform transition ease-in-out duration-300 sm:duration-500" 
                    x-transition:leave-start="translate-x-0" 
                    x-transition:leave-end="translate-x-full"
                    {{ $attributes->twMerge($classes) }}
                    @open-dialog.window="if($event.detail.id === $el.id) open=true"
                    @close-dialog.window="if($event.detail.id === $el.id) open=false" 
                    >
                    @if($header ?? false)
                        <div class="flex absolute top-0 z-50 flex-shrink-0 items-center px-8 w-full h-16 rounded-t-xl backdrop-blur-sm bg-white/90">
                            {{ $header }}
                        </div>
                    @endif
                    <div class="absolute top-0 right-0 z-[51]">
                        <button @click="open=false" class="absolute top-0 right-0 p-2 mt-4 mr-4 rounded-full cursor-pointer hover:bg-black/10">
                            <svg class="w-5 h-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256"><path fill="none" d="M0 0h256v256H0z"/><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16" d="M200 56 56 200M200 200 56 56"/></svg>
                        </button>
                    </div>
                    <div class="overflow-scroll px-8 py-16 w-full h-full">
                      <div class="w-full h-auto @if(isset($footer)) pb-20 @endif">
                        {{ $content }}
                      </div>
                    </div>
                    @if($footer ?? false)
                        <div class="flex absolute bottom-0 z-50 flex-shrink-0 justify-between items-center px-8 w-full h-20 rounded-b-xl border-t border-gray-100 backdrop-blur-sm bg-white/70">
                            {{ $footer }}
                        </div>
                    @endif
                </div>
        </div>
    </dialog>
</div>

