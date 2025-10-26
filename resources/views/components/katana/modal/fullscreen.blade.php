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
        <div x-show="open" class="z-999 relative" @keydown.window.escape="open=false">
            <div x-show="open" class="fixed inset-0 bg-black/10" @click="open = false"></div>
            <div class="fixed inset-0 overflow-hidden">
                <div class="absolute inset-0 overflow-hidden">
                    <div class="fixed inset-y-0 right-0 flex max-w-full">
                        <div x-show="open" class="w-screen max-w-full">
                            <div class="flex h-full flex-col overflow-y-scroll border-l border-neutral-100/70 bg-white shadow-lg">
                                <div class="flex h-16 w-full shrink-0 items-center justify-center">
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
