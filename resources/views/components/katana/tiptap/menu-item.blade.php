@php

// if the $name starts with 'heading'...
if(str_starts_with($name, 'heading')){
    $level = explode('_', $name)[1];
}

@endphp

<button
    @click="{{ $click }}"
    @if(isset($level))
        :class="{ 'text-black bg-black/5' : (window.tiptap && window.tiptap[elementId] && window.tiptap[elementId].isActive('heading', { level: parseInt('{{ $level }}') })), 'text-gray-700 hover:bg-black/5 hover:text-black' : (window.titap && window.tiptap[elementId] && !window.tiptap[elementId].isActive('heading', { level: parseInt('{{ $level }}') })) }"
    @elseif($name == 'link')
        :class="{ 'text-black bg-black/5' : linkModal, 'text-gray-700 hover:bg-black/5 hover:text-black' : !linkModal }"
    @else
        :class="{ 'text-black bg-black/5' : (window.tiptap && window.tiptap[elementId] && window.tiptap[elementId].isActive('{{ $name }}')), 'text-gray-700 hover:bg-black/5 hover:text-black' : (window.tiptap && window.tiptap[elementId] && !window.titap[elementId].isActive('{{ $name }}')) }"
    @endif
    class="flex relative justify-center items-center w-7 h-7 rounded-md group">
    <span class="w-4 h-4"><x-dynamic-component component="tiptap.icons.{{ $name }}" /></span>
    <span class="pointer-events-none invisible absolute bottom-0 @if($tooltipLeft ?? false) left-0 @else left-1/2 -translate-x-1/2 @endif mb-0 translate-y-full whitespace-nowrap rounded bg-black/70 px-2 py-1 text-[0.6rem] text-white shadow-lg duration-0 ease-linear group-hover:visible group-hover:-mb-1 group-hover:duration-300 group-hover:ease-out">{{ $tooltip }}</span>
</button>
