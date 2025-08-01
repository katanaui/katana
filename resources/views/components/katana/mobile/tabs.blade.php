@props([
    'active' => 'home'
])

<nav class="fixed right-0 bottom-0 left-0 border-t z-[99] border-gray-200 backdrop-blur-xl bg-white/50">
    <div class="flex py-1.5 mx-auto max-w-3xl">
        <a href="/" 
            @class([
                'flex-1 flex flex-col items-center justify-center py-2 px-1 space-y-1.5 text-gray-500 min-h-[60px]',
                'text-indigo-500' => $active == 'home',
                'text-gray-500' => $active != 'home'
            ])>
            <x-phosphor-house-line-duotone class="w-8 h-8" />
            <span class="text-xs font-medium leading-tight">Home</span>
        </a>

        <a href="/tour/map"
            @class([
                'flex-1 flex flex-col items-center justify-center py-2 px-1 space-y-1.5 text-gray-500 min-h-[60px]',
                'text-indigo-500' => $active == 'map',
                'text-gray-500' => $active != 'map'
            ])>
            <x-phosphor-map-trifold-duotone class="w-8 h-8" />
            <span class="text-xs font-medium leading-tight">Map</span>
        </a>

        <div class="relative flex-1 w-auto h-auto">
        <a href="/tour/begin" 
            @class([
                'flex-1 flex flex-col items-center w-full shadow-xl border border-gray-200 rounded-t-xl -translate-y-3 bg-white justify-center py-4 absolute px-1 space-y-1.5 transition-colors min-h-[60px]',
                'text-indigo-500' => $active == 'tour',
                'text-gray-500' => $active != 'tour'
            ])>
            <x-phosphor-signpost-duotone class="w-8 h-8" />
            <span class="text-xs font-medium leading-tight">My Tour</span>
        </a>
        </div>

        <a href="/tour/stops"
            @class([
                'flex-1 flex flex-col items-center justify-center py-2 px-1 space-y-1.5 text-gray-500 min-h-[60px]',
                'text-indigo-500' => $active == 'stops',
                'text-gray-500' => $active != 'stops'
            ])>
            <x-phosphor-map-pin-duotone class="w-8 h-8" />
            <span class="text-xs font-medium leading-tight">Tour Stops</span>
        </a>

        <a href="/profile"
            @class([
                'flex-1 flex flex-col items-center justify-center py-2 px-1 space-y-1.5 text-gray-500 min-h-[60px]',
                'text-indigo-500' => $active == 'profile',
                'text-gray-500' => $active != 'profile'
            ])>
            <x-phosphor-user-circle-duotone class="w-8 h-8" />
            <span class="text-xs font-medium leading-tight">Profile</span>
        </a>
    </div>
</nav>