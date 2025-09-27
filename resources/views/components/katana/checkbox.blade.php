@props([
    'label' => null,
    'description' => null,
    'checked' => false,
    'disabled' => false,
    'clickable' => false,
    'checkmarkStrokeWidth' => 3
])

@php
    $classes = \Illuminate\Support\Arr::toCssClasses([
        'flex space-x-2',
        'items-start' => $description != null,
        'items-center' => $description == null
    ]);

    $id = ($attributes->get('id')) ? $attributes->get('id') : uniqid('checkbox-');
    $containerAttribute = $clickable ? 'label for="' . $id . '"' : 'div';
    $innerLabelAttribute = $clickable ? 'span' : 'label for="' . $id . '"';
@endphp

<{!! $containerAttribute !!} x-data="{ checked: {{ $checked ? 'true' : 'false' }} }" {{ $attributes->twMerge($classes) }}>
    <input
        type="checkbox"
        id="{{ $id }}"
        class="sr-only peer"
        x-bind:aria-checked="checked"
        x-on:change="checked = $event.target.checked"
        x-ref="checkbox"
        @checked($checked)
        @disabled($disabled)
        {{ $attributes->withoutTwMergeClasses() }}
    />

    <span @if(!$clickable) @click="$refs.checkbox.checked = !$refs.checkbox.checked" @endif class="w-5 h-5 border-2 border-gray-300 rounded-md flex items-center justify-center transition peer-checked:border-primary peer-checked:bg-primary peer-focus:ring-2 peer-focus:ring-primary/30 peer-checked:[&>svg]:opacity-100 [&>svg]:opacity-0 peer-checked:[&>svg]:scale-100 [&>svg]:scale-50">
        <!-- Checkmark (only visible when checked) -->
        <svg class="w-3.5 h-3.5 text-white stroke-current duration-200 text-primary-foreground transition" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ $checkmarkStrokeWidth }}"><path d="M20 6 9 17l-5-5"/></svg>
    </span>


    @if($label || $description)
        <span class="flex flex-col">
            @if($label)
                <{!! $innerLabelAttribute !!} {{ $attributes->twMergeFor('label', 'text-sm font-medium select-none text-gray-900') }}>
                    {{ $label }}
                </{!! $innerLabelAttribute !!}
            @endif

            @if($description)
                <span class="{{ $attributes->twMergeFor('description', 'text-sm text-gray-500') }}">
                    {{ $description }}
                </span>
            @endif
        </span>
    @endif
</{!! $containerAttribute !!}>