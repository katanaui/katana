@props([
    'api_key' => config('katana.api_keys.address_autocomplete'),
    'id' => 'address_autocomplete_' . uniqid()
])

<div 
    x-data="placesAutocomplete()" 
    x-init="init()" 
    class="space-y-2 w-full"
>
    @if($label ?? false)
        <label for="{{ $id }}" class="block text-sm font-medium">
            {{ $label }}
        </label>
    @endif

    <x-katana.input
        id="{{ $id }}"
        x-ref="input"
        x-model="query"
        {{ $attributes }}
        type="text"
        placeholder="{{ $placeholder ?? 'Start typing an address…' }}"
        autocomplete="off"
        @keydown.enter.prevent
    />

    <!-- Show selection -->
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

@once
    @push('scripts')
        <!-- Load Google Maps API only once -->
        <script 
            src="https://maps.googleapis.com/maps/api/js?key={{ $api_key ?? '' }}&libraries=places" 
            async 
            defer>
        </script>

        <script>
            function placesAutocomplete() {
                return {
                    query: "",
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
                            
                            const lat = p.geometry?.location?.lat?.();
                            const lng = p.geometry?.location?.lng?.();
                            this.place.lat = lat ?? null;
                            this.place.lng = lng ?? null;

                            const comps = {};
                            (p.address_components || []).forEach(c => {
                                if (c.types && c.types.length) comps[c.types[0]] = c.long_name;
                            });
                            this.place.components = comps;
                            
                            // Build custom formatted address WITHOUT country
                            let customFormatted = '';
                            const addressParts = [];
                            
                            // Add street number and route
                            if (comps.street_number) addressParts.push(comps.street_number);
                            if (comps.route) addressParts.push(comps.route);
                            
                            // Add locality (city)
                            if (comps.locality) addressParts.push(comps.locality);
                            
                            // Add administrative_area_level_1 (state)
                            if (comps.administrative_area_level_1) addressParts.push(comps.administrative_area_level_1);
                            
                            // Add postal_code
                            if (comps.postal_code) addressParts.push(comps.postal_code);
                            
                            // Join with appropriate separators
                            if (addressParts.length > 0) {
                                // Street address
                                let streetAddress = '';
                                if (comps.street_number && comps.route) {
                                    streetAddress = `${comps.street_number} ${comps.route}`;
                                } else if (comps.route) {
                                    streetAddress = comps.route;
                                }
                                
                                // City, State ZIP
                                let cityStateZip = '';
                                const cityParts = [];
                                if (comps.locality) cityParts.push(comps.locality);
                                if (comps.administrative_area_level_1) cityParts.push(comps.administrative_area_level_1);
                                if (comps.postal_code) cityParts.push(comps.postal_code);
                                
                                if (cityParts.length > 0) {
                                    cityStateZip = cityParts.join(', ');
                                }
                                
                                // Combine street and city parts
                                const finalParts = [];
                                if (streetAddress) finalParts.push(streetAddress);
                                if (cityStateZip) finalParts.push(cityStateZip);
                                
                                customFormatted = finalParts.join(', ');
                            }
                            
                            // Use custom formatted address or fallback to original without country
                            this.place.formatted = customFormatted || p.formatted_address?.replace(/, USA$/, '') || this.query;
                            
                            if (this.place.formatted) {
                                this.query = this.place.formatted;
                                
                                // Update the input value directly
                                this.$refs.input.value = this.place.formatted;
                                
                                // Trigger input event to ensure Livewire's local model is updated
                                this.$refs.input.dispatchEvent(new Event('input', { bubbles: true }));
                            }
                        });
                    },
                };
            }
        </script>
    @endpush
@endonce