@props([
    'position' => 'top',
    'arrow' => true,
    'content' => '',
])


<katana-tooltip>
    {{ $slot }}
</katana-tooltip>

@push('scripts')
    <script src="{{ asset('katana/tooltip.js') }}" defer></script>
    <script>console.log('stacks is working');</script>
@endpush