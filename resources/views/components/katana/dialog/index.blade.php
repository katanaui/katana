@props([
    'drawer' => false,
    'side' => 'right'
])

<style>
dialog[open]{
    display:flex;
}

.modal[open] {
  display: flex;
}

dialog[open]::backdrop {
  animation: backdrop-fade 2s  ease forwards;
}

dialog.close::backdrop {
  animation: backdrop-fade 3s ease backwards;
  animation-direction: reverse;
}

@keyframes backdrop-fade {
  from {
    background: transparent;
  }
  to{
    background: rgba(0,0,0);
  }
}

dialog:not([open]) {
  pointer-events: none;
  opacity: 0;
}
dialog:modal {
  max-width: 100vw;
  max-height: 100vh;
}
dialog::backdrop{
    opacity: 0;
    transition: opacity 0.5s ease;
}

dialog[open]::backdrop{
    opacity:0.2;
}
</style>

@php
    $classes = \Illuminate\Support\Arr::toCssClasses([
        'flex overflow-hidden justify-center relative items-center p-10  bg-white rounded-xl border starting:opacity-0 opacity-100 shadow-sm border-stone-200',
        'starting:translate-y-2 translate-y-0' => !$drawer,
        'ml-auto w-full h-full' => $drawer
    ]);
@endphp

<div
    x-data="{ 
        open: false,
        clickedBackground(event, element){
            if(event.target == element){
                console.log('made');
                this.open=false;
            }
        }
    }"
    x-init="
        $watch('open', function(value) {
            if (value) {
                $refs.dialog.showModal();
            } else {
                $refs.dialog.close();
            }
        });
    "
>
    <div @click="open = true;">{{ $slot ?? 'Open dialog' }}</div>

    <dialog 
        x-ref="dialog"
        class="flex overflow-hidden m-auto w-screen h-screen bg-transparent"
        @close="open = false;"
    >
        <div @click="clickedBackground($event, $el)" class="block overflow-hidden absolute inset-0 p-3 w-full h-full">
            
                <div 
                    x-show="open" 
                    @click.away="open = false"
                    x-transition:enter="transform transition ease-in-out duration-500 sm:duration-700" 
                    x-transition:enter-start="translate-x-full" 
                    x-transition:enter-end="translate-x-0" 
                    x-transition:leave="transform transition ease-in-out duration-500 sm:duration-700" 
                    x-transition:leave-start="translate-x-0" 
                    x-transition:leave-end="translate-x-full" 
                    {{ $attributes->twMerge($classes) }}>
                    {{ $content }}
                    <form method="dialog" class="absolute top-0 right-0">
                        <button class="absolute top-0 right-0 p-2 mt-4 mr-4 rounded-full cursor-pointer hover:bg-black/10">
                            <svg class="w-5 h-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256"><path fill="none" d="M0 0h256v256H0z"/><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16" d="M200 56 56 200M200 200 56 56"/></svg>
                        </button>
                    </form>
                </div>
        </div>
    </dialog>
</div>

