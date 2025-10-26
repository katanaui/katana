@props([
    'label' => null,
    'hint' => null,
    'src' => null, // string|Livewire TemporaryUploadedFile|null
    'disk' => config('filesystems.default'),
    'nextToLabel' => null,
])

@php
    use Illuminate\Support\Facades\Storage;
    use Illuminate\Support\Str;
    use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

    // For the clear button
    $wireModelName = optional($attributes->wire('model'))->value();

    // Decide preview URL
    $previewUrl = null;

    if ($src instanceof TemporaryUploadedFile) {
        $previewUrl = $src->temporaryUrl();
    } elseif (is_string($src) && $src !== '') {
        // If it already looks like a URL (http(s), protocol-relative, or data URL), use as-is.
        $previewUrl = Str::startsWith($src, ['http://', 'https://', '//', 'data:']) ? $src : Storage::disk($disk)->url($src);
    }
@endphp

<div
    x-data="{
        isUploading: false,
        progress: 0,
        isDragOver: false,
        handleDrop(e) {
            e.preventDefault();
            this.isDragOver = false;
            const files = e.dataTransfer.files;
            if (files.length) {
                const i = this.$refs.fileInput;
                i.files = files;
                i.dispatchEvent(new Event('change', { bubbles: true }));
            }
        },
        handleDragOver(e) { e.preventDefault();
            this.isDragOver = true },
        handleDragLeave(e) { e.preventDefault();
            this.isDragOver = false },
    }" x-on:livewire-upload-start="isUploading = true" x-on:livewire-upload-finish="isUploading = false; progress = 0" x-on:livewire-upload-error="isUploading = false; progress = 0" x-on:livewire-upload-progress="progress = $event.detail.progress">

    @if ($label)
        <div class="jutify-between relative mb-2 flex items-center space-x-1.5">
            <x-katana.form.label class="shrink-0">{{ $label }}</x-katana.form.label>
            @if ($nextToLabel)
                {!! $nextToLabel !!}
            @endif
        </div>
    @endif

    <!-- Audio Preview -->
    @if ($previewUrl)
        <div class="relative mt-3 flex items-center rounded-lg border border-gray-200 pr-4" wire:key="audio-preview-{{ $wireModelName }}">
            <div class="flex w-full items-stretch justify-stretch">
                <x-katana.audio-player class="border-0" wire:key="audio-player-{{ $wireModelName }}" :src="$previewUrl" />
            </div>

            @if ($wireModelName)
                <button class="relative z-10 rounded-full bg-stone-900 p-2" type="button" wire:click="$set('{{ $wireModelName }}', null)" title="Remove">
                    <x-phosphor-x-bold class="h-3 w-3 text-white" />
                </button>
            @endif
        </div>
    @else
        <div class="h-auto w-full">
            <!-- File Input Area -->
            <label
                class="flex h-44 w-full cursor-pointer flex-col items-center justify-center rounded-lg border border-dashed border-gray-900/25 px-8 text-gray-500 transition-colors hover:border-gray-900/30 hover:bg-gray-50" :class="{ 'border-blue-400 bg-blue-50 text-blue-600': isDragOver }" @drop="handleDrop" @dragover="handleDragOver" @dragleave="handleDragLeave" @dragenter.prevent>
                <div x-show="!isUploading" class="flex flex-col items-center justify-center space-y-1">
                    <svg class="mx-auto size-12 text-gray-300/70 dark:text-gray-600" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14.5v-9l6 4.5-6 4.5z" />
                    </svg>
                    <div class="mt-4 flex text-sm/6 text-gray-500">
                        <span class="cursor-pointer font-medium text-blue-600 hover:text-indigo-500">Upload audio file</span>
                        <p class="pl-1">or drag and drop</p>
                    </div>
                    <p class="text-xs text-gray-400">MP3, WAV, OGG files supported</p>
                </div>

                <!-- Upload Progress Indicator -->
                <div x-show="isUploading" class="w-full">
                    <div class="mb-2 flex items-center justify-between">
                        <span class="text-xs font-medium text-gray-500">Uploading...</span>
                        <span x-text="`${progress}%`" class="text-xs font-medium text-gray-500"></span>
                    </div>
                    <div class="h-2 w-full rounded-full bg-stone-200">
                        <div class="h-2 rounded-full bg-blue-600" :style="`width: ${progress}%`"></div>
                    </div>
                </div>

                <input
                    x-ref="fileInput" class="hidden" type="file" accept="audio/*" {{ $attributes }} {{-- includes wire:model etc --}} />
            </label>

            @if ($hint)
                <p class="mt-3 text-xs text-gray-500">{{ $hint }}</p>
            @endif
        </div>
    @endif
</div>
