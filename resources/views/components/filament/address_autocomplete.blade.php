<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div>
        <x-katana.address-autocomplete
            wire:model="{{ $getStatePath() }}"
        />
    </div>
</x-dynamic-component>