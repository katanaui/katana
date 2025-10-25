@props([
    // Keyboard shortcut key (Cmd/Ctrl + key) to open
    'shortcut' => 'k',

    // ID prefix your list items will use, e.g. "design-item-0", "design-item-1", ...
    'itemPrefix' => 'command-item-',

    // Input placeholder
    'placeholder' => 'Type to search…',

    // Livewire binding for the input (optional) — ex: 'searchTerm'
    'wireModel' => null,

    // Whether to lock body scroll when open
    'lockBody' => true,

    // Optional: show the ⌘K hint chip in the trigger area
    'showHint' => true,
])

<div
    x-data="{
        commandOpen: false,
        activeIndex: 0,
        prefix: @js($itemPrefix),
        get count() {
            return document.querySelectorAll(`[id^='${this.prefix}']`).length;
        },
        resetIndex(){ this.activeIndex = 0; this.scrollToActive(); },
        next(){
            if(this.count > 0 && this.activeIndex < this.count - 1){
                this.activeIndex++; this.scrollToActive();
            }
        },
        prev(){
            if(this.count > 0 && this.activeIndex > 0){
                this.activeIndex--; this.scrollToActive();
            }
        },
        select(){
            const el = document.getElementById(this.prefix + this.activeIndex);
            if(el){ el.click(); }
        },
        isActive(i){ return this.activeIndex === i; },
        scrollToActive(){
            const el = document.getElementById(this.prefix + this.activeIndex);
            if(el){
                // Smooth scroll but keep it reasonable for long lists
                el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        }
    }"
    x-init="
        $watch('commandOpen', (v) => {
            if (v) {
                {{ $lockBody ? "document.body.classList.add('overflow-hidden');" : '' }}
                $nextTick(() => { $refs.commandInput.focus(); resetIndex(); });
            } else {
                {{ $lockBody ? "document.body.classList.remove('overflow-hidden');" : '' }}
            }
        });

        // Cmd/Ctrl + {{$shortcut}} opens the palette
        document.addEventListener('keydown', (event) => {
            if (event.key === @js($shortcut) && (event.metaKey || event.ctrlKey)) {
                event.preventDefault();
                commandOpen = true;
            }
        });

        // Optional: if your page dispatches any event after results render,
        // call resetIndex() once DOM items exist (example Livewire event):
        document.addEventListener('command-reset-index', () => { $nextTick(() => resetIndex()); });
    "
    @keydown.escape.window="commandOpen = false"
    class="relative z-50 w-full h-auto"
    {{ $attributes }}
>
    {{-- Trigger slot (your button goes here) --}}
    <div class="relative">
        <div @click="commandOpen = true">
            {{ $slot }}
        </div>

        @if($showHint)
            <div class="absolute right-0 top-1/2 px-2 mr-2 rounded-md border -translate-y-1/2 pointer-events-none bg-stone-100 border-stone-300">
                <kbd class="font-sans text-gray-500 text-xs/4 dark:text-gray-400">⌘{{ strtoupper($shortcut) }}</kbd>
            </div>
        @endif
    </div>

    {{-- Teleport modal --}}
    <template x-teleport="body">
        <div
            x-show="commandOpen"
            class="fixed inset-0 z-9999 flex items-center justify-center w-screen h-screen"
            x-cloak
        >
            {{-- Backdrop --}}
            <div
                x-show="commandOpen"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-300"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                @click="commandOpen=false"
                class="absolute inset-0 bg-black/40"
            ></div>

            {{-- Dialog --}}
            <div
                x-show="commandOpen"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                @keydown="
                    if (event.key === 'ArrowDown') { event.preventDefault(); next(); }
                    else if (event.key === 'ArrowUp') { event.preventDefault(); prev(); }
                    else if (event.key === 'Enter') { event.preventDefault(); select(); }
                "
                role="dialog"
                aria-modal="true"
                aria-labelledby="command-title"
                class="relative flex w-full max-w-xl min-h-[370px] items-start justify-center"
                x-cloak
            >
                <div class="box-border flex overflow-hidden flex-col w-full h-full bg-white rounded-xl shadow-lg">
                    {{-- Top: Search input --}}
                    <div class="flex items-center px-3 border-b border-gray-300">
                        <svg class="mr-0 w-4 h-4 shrink-0 text-neutral-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>

                        <input
                            x-ref="commandInput"
                            id="command-title"
                            @if($wireModel) wire:model.live.debounce.300ms="{{ $wireModel }}" @endif
                            aria-label="Command search"
                            aria-expanded="true"
                            aria-haspopup="listbox"
                            role="combobox"
                            type="text"
                            class="px-2 py-3 w-full h-11 text-sm bg-transparent rounded-md border-0 outline-none placeholder:text-neutral-400 focus:border-0 focus:outline-none focus:ring-0 disabled:cursor-not-allowed disabled:opacity-50"
                            placeholder="{{ $placeholder }}"
                            autocomplete="off"
                            autocorrect="off"
                            spellcheck="false"
                        >
                    </div>

                    {{-- Body: results area (you control this with named slots) --}}
                    <div class="overflow-y-auto overflow-x-hidden h-auto" role="listbox" aria-label="Command results">
                        {{-- Loading slot --}}
                        @if (isset($loading))
                            {{ $loading }}
                        @endif

                        {{-- Results slot --}}
                        @if (isset($results))
                            {{ $results }}
                        @endif

                        {{-- Empty slot --}}
                        @if (isset($empty))
                            {{ $empty }}
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>