<div x-data="{
    open: false
}" x-init="console.log('made');
$watch('open', function(value) {
    console.log('cahnged: ' + value);
});" class="relative h-auto w-auto" @close-fullscreen-modal.window="open=false" {{ $attributes }}>
    <div @click="open=true">
        {{ $trigger }}
    </div>
    <template x-teleport="body">
<<<<<<< HEAD
        <div x-show="open" class="z-999 relative" @keydown.window.escape="open=false">
            <div x-show="open" class="fixed inset-0 bg-black/10" @click="open = false"></div>
            <div class="fixed inset-0 overflow-hidden">
                <div class="absolute inset-0 overflow-hidden">
                    <div class="fixed inset-y-0 right-0 flex max-w-full">
                        <div x-show="open" class="w-screen max-w-full">
                            <div class="flex h-full flex-col overflow-y-scroll border-l border-neutral-100/70 bg-white shadow-lg">
                                <div class="flex h-16 w-full shrink-0 items-center justify-center">
=======
        <div 
            x-show="open"
            @keydown.window.escape="open=false"
            class="relative z-999">
            <div x-show="open" x-transition.opacity.duration.600ms @click="open = false" class="fixed inset-0 bg-black bg-opacity-10"></div>
            <div class="overflow-hidden fixed inset-0">
                <div class="overflow-hidden absolute inset-0">
                    <div class="flex fixed inset-y-0 right-0 max-w-full">
                        <div 
                            x-show="open"
                            x-transition:enter="transform transition ease-in-out duration-300" 
                            x-transition:enter-start="scale-[0.95] opacity-0" 
                            x-transition:enter-end="scale-1 opacity-100" 
                            x-transition:leave="transform transition ease-in-out duration-300" 
                            x-transition:leave-start="scale-1 opacity-100" 
                            x-transition:leave-end="scale-[0.99] opacity-0" 
                            class="w-screen max-w-full">
                            <div class="flex overflow-y-scroll flex-col h-full bg-white border-l shadow-lg border-neutral-100/70">
                                <div class="flex shrink-0 justify-center items-center w-full h-16">
>>>>>>> ce93a45476b2d817eb5e85b2232dd61263b5867d
                                    {{ $title }}
                                </div>
                                <div class="relative flex-1">
                                    {{ $slot }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
