@props([
    'active' => 'home'
])

<nav class="fixed right-0 bottom-0 left-0 border-t z-9999 border-gray-200 backdrop-blur-xl bg-white/50">
    <div class="flex py-0 mx-auto max-w-3xl">
        <a href="/" 
            @class([
                'flex-1 flex flex-col items-center justify-center py-2 px-1 space-y-1 text-gray-500 min-h-[50px]',
                'text-indigo-500' => $active == 'home',
                'text-gray-500' => $active != 'home'
            ])>
            <x-phosphor-house-line-duotone class="w-7 h-7" />
            <span class="text-xs font-medium leading-tight">Home</span>
        </a>

        <a href="/tour/stops"
            @class([
                'flex-1 flex flex-col items-center justify-center py-2 px-1 space-y-1 text-gray-500 min-h-[60px]',
                'text-indigo-500' => $active == 'stops',
                'text-gray-500' => $active != 'stops'
            ])>
            <x-phosphor-map-pin-duotone class="w-7 h-7" />
            <span class="text-xs font-medium leading-tight">Stops</span>
        </a>

        <div class="relative flex-1 px-2 w-auto h-auto">
        <a href="{{ route('property.tour') }}" 
            @class([
                'flex-1 flex flex-col items-center w-full shadow-xl border border-gray-200 rounded-t-xl -translate-y-1 bg-white justify-center py-2 pb-3 absolute px-1 space-y-1 transition-colors min-h-[58px]',
                'text-indigo-500' => $active == 'tour',
                'text-gray-500' => $active != 'tour'
            ])>
            <x-phosphor-map-trifold-duotone class="w-7 h-7" />
            <span class="text-xs font-medium leading-tight">My Tour</span>
        </a>
        </div>

        <a href="/tour/chat"
            @class([
                'flex-1 flex flex-col items-center justify-center py-2 px-1 space-y-1 text-gray-500 min-h-[60px]',
                'text-indigo-500' => $active == 'chat',
                'text-gray-500' => $active != 'chat'
            ])>
            <x-phosphor-chat-text-duotone class="w-7 h-7" />
            <span class="text-xs font-medium leading-tight">Chat</span>
        </a>

        <a href="/tour/apply"
            @class([
                'flex-1 flex flex-col items-center justify-center py-2 px-1 space-y-1 text-gray-500 min-h-[60px]',
                'text-indigo-500' => $active == 'apply',
                'text-gray-500' => $active != 'apply'
            ])>
            <x-phosphor-key-duotone class="w-7 h-7" />
            <span class="text-xs font-medium leading-tight">Apply</span>
        </a>
    </div>
</nav>