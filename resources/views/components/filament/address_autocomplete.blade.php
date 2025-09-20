<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div>
        <x-katana.address_autocomplete
            wire:model="{{ $getStatePath() }}"
            value="abc"
        />
    </div>
</x-dynamic-component>