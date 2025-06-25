# Split Pane Component

The Split Pane component utilizes the SplitJS flex library: https://split.js.org/

## Example usage:

```php
<x-katana.splitter gutterSize="8" minSize="200" class="h-screen">
    <x-katana.splitter.pane>left</x-katana.splitter.pane>
    <x-katana.splitter.pane>right</x-katana.splitter.pane>
</x-katana.splitter>
```

## Props:

- gutterSize: int
- minSize: int
- direction: string (horizontal | vertical)