@props([
    'label' => null,
    'description' => null,
    'checked' => false,
    'disabled' => false,
    'clickable' => false,
    'checkmarkStrokeWidth' => 3,
    'hideCheckbox' => false
])

@php
    $classes = \Illuminate\Support\Arr::toCssClasses([
        'flex space-x-2 group',
        'select-none' => $clickable,
        'items-start' => $description != null,
        'items-center' => $description == null
    ]);

    $id = ($attributes->get('id')) ? $attributes->get('id') : uniqid('checkbox-');
    $containerAttribute = $clickable ? 'label for="' . $id . '"' : 'div';
    $innerLabelAttribute = $clickable ? 'span' : 'label for="' . $id . '"';
@endphp

<{!! $containerAttribute !!} x-data="{ checked: {{ $checked ? 'true' : 'false' }} }" x-bind:data-checked="checked ? true : null" {{ $attributes->twMerge($classes) }}>
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

    @if(!$hideCheckbox)
        <span @if(!$clickable && !$disabled) @click="$refs.checkbox.checked = !$refs.checkbox.checked; $refs.checkbox.dispatchEvent(new Event('change')); $refs.checkbox.focus()" @endif 
            {{ $attributes->twMergeFor('checkbox', 'w-5 h-5 border border-gray-300 rounded-md flex items-center justify-center transition group-data-checked:border-primary group-data-checked:bg-primary peer-disabled:opacity-60 peer-focus:ring-2 peer-focus:ring-primary/30') }}>
            <!-- Checkmark (only visible when checked) -->
            <svg class="w-3.5 h-3.5 stroke-current duration-150 text-primary-foreground ease group-data-checked:opacity-100 opacity-0 group-data-checked:scale-100 scale-50" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ $checkmarkStrokeWidth }}"><path d="M20 6 9 17l-5-5"/></svg>
        </span>
    @endif

    @if($slot ?? false)
        {{ $slot }}
    @endif


    @if($label || $description)
        <span class="flex flex-col peer-disabled:opacity-60">
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