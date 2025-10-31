@props([
    'exclusive' => false,
])

<div x-data="{
    activeAccordions: @js($exclusive ? '' : []),
    toggle(id) {
        if (@js($exclusive)) {
            this.activeAccordions = (this.activeAccordions === id) ? '' : id;
        } else {
            if (this.activeAccordions.includes(id)) {
                this.activeAccordions = this.activeAccordions.filter(i => i !== id);
            } else {
                this.activeAccordions.push(id);
            }
        }
    },
    isOpen(id) {
        return @js($exclusive) ?
            this.activeAccordions === id :
            this.activeAccordions.includes(id);
    }
}" {{ $attributes->twMerge('w-full mx-auto divide-y divide-border bg-background') }}>
    {{ $slot }}
</div>
