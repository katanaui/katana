<script id="search-js" defer src="https://api.mapbox.com/search-js/v1.5.0/web.js"></script>

<x-katana.input id="address-input" type="text" name="address" class="w-full" placeholder="Start typing an address..." autocomplete="shipping street-address" />

<script>
const script = document.getElementById('search-js');
  // wait for the Mapbox Search JS script to load before using it
  script.onload = function () {
      // instantiate a <mapbox-address-autofill> element using the MapboxAddressAutofill class
      const autofillElement = new mapboxsearch.MapboxAddressAutofill()

      autofillElement.accessToken = 'pk.eyJ1IjoiZGV2ZG9qbyIsImEiOiJjbWdxbDVyamwxaGhnMm5xNTh6d3R2OW91In0.ehWxemaD7SAx_NDSd4yabw'

      // set the <mapbox-address-autofill> element's options
      autofillElement.options = {
          country: 'US',
      }


      const the_input = document.getElementById('address-input');
      const the_form = the_input.parentElement

      // append the <input> to <mapbox-address-autofill>
      autofillElement.appendChild(the_input);
      // append <mapbox-address-autofill> to the <form>
      the_form.appendChild(autofillElement);

  };
</script>
