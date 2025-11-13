@props([
    'label' => null,
    'api_key' => config('katana.api_keys.address_autocomplete'),
    'id' => 'address_autocomplete_' . uniqid()
])

<div
    x-data="placesAutocomplete"
    x-init="init()"
    class="space-y-2 w-full"
>
    @if($label ?? false)
        <label for="{{ $id }}" class="block text-sm font-medium">
            {{ $label }}
        </label>
    @endif

    <x-katana.input
        x-ref="input"
        {{ $attributes }}
        type="text"
        placeholder="{{ $placeholder ?? 'Start typing an address…' }}"
        autocomplete="off"
        @keydown.enter.prevent="true"
    />

    <!-- Show selection -->
    <div class="hidden">
        <div x-show="place.formatted" class="text-xs text-gray-600">
            Selected: <span class="font-medium" x-text="place.formatted"></span>
            <template x-if="place.lat && place.lng">
                <span>
                    ( <span x-text="place.lat.toFixed(6)"></span>,
                    <span x-text="place.lng.toFixed(6)"></span> )
                </span>
            </template>
        </div>
    </div>
</div>

@once
        <!-- Load Google Maps API only once -->
        <script
            src="https://maps.googleapis.com/maps/api/js?key={{ $api_key ?? '' }}&libraries=places&loading=async"
            async
            defer>
        </script>

        <script>
            (function registerPlacesAutocomplete(){
                const initAlpineComponent = () => {
                    Alpine.data('placesAutocomplete', () => ({
                    autocomplete: null,
                    place: { formatted: "", lat: null, lng: null, components: {} },

                    init() {
                        // Wait until Google Maps is available
                        let interval = setInterval(() => {
                            if (window.google?.maps?.places) {
                                clearInterval(interval);
                                this.mountAutocomplete();
                            }
                        }, 200);
                    },

                    mountAutocomplete() {
                        this.autocomplete = new google.maps.places.Autocomplete(this.$refs.input, {
                            types: ["address"],
                            fields: ["formatted_address", "geometry", "address_components"],
                        });

                        this.autocomplete.addListener("place_changed", () => {
                            const p = this.autocomplete.getPlace();
                            this.place.formatted = p.formatted_address ?? this.$refs.input.value;

                            const lat = p.geometry?.location?.lat?.();
                            const lng = p.geometry?.location?.lng?.();
                            this.place.lat = lat ?? null;
                            this.place.lng = lng ?? null;


                            window.dispatchEvent(new CustomEvent('address_autocomplete_change', {
                                detail: {
                                    'id' : '{{ $id }}',
                                    'lat': lat,
                                    'long': lng
                                }
                            }));

                            this.$refs.input.value = this.place.formatted;

                            // Trigger input event to ensure Livewire's wire:model is updated
                            this.$refs.input.dispatchEvent(new Event('input', { bubbles: true }));
                            this.$refs.input.dispatchEvent(new Event('blur', { bubbles: true }));
                        });
                    },
                    }));
                };

                if (window.Alpine && typeof window.Alpine.data === 'function') {
                    initAlpineComponent();
                } else {
                    document.addEventListener('alpine:init', initAlpineComponent, { once: true });
                }
            })();
        </script>
@endonce
