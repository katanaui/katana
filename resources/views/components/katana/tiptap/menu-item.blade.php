@php

// if the $name starts with 'heading'...
if(str_starts_with($name, 'heading')){
    $level = explode('_', $name)[1];
}

@endphp

<button
    @click="{{ $click }}"
    @if(isset($level))
        :class="{ 'text-foreground bg-foreground/5 dark:bg-foreground/10' : activeStates.heading_{{ $level }}, 'text-foreground/70 hover:bg-foreground/5 dark:hover:bg-foreground/10 hover:text-foreground' : !activeStates.heading_{{ $level }} }"
    @elseif($name == 'link')
        :class="{ 'text-foreground bg-foreground/5 dark:bg-foreground/10' : linkModal, 'text-foreground/70 hover:bg-foreground/5 dark:hover:bg-foreground/10 hover:text-foreground' : !linkModal }"
    @else
        :class="{ 'text-foreground bg-foreground/5 dark:bg-foreground/10' : activeStates.{{ $name }}, 'text-foreground/70 hover:bg-foreground/5 dark:hover:bg-foreground/10 hover:text-foreground' : !activeStates.{{ $name }} }"
    @endif
    class="flex relative justify-center items-center w-7 h-7 rounded-md group/tiptap">
    <span class="w-4 h-4"><x-dynamic-component component="katana.tiptap.icons.{{ $name }}" /></span>
    <span class="pointer-events-none invisible absolute bottom-0 @if($tooltipLeft ?? false) left-0 @else left-1/2 -translate-x-1/2 @endif mb-0 translate-y-full whitespace-nowrap rounded-md bg-foreground/80 backdrop-blur-lg px-2 py-1 text-[0.6rem] text-background shadow-lg duration-0 ease-linear group-hover/tiptap:visible group-hover/tiptap:-mb-1 group-hover/tiptap:duration-300 group-hover/tiptap:ease-out">{{ $tooltip }}</span>
</button>
