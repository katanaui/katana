@props([
    'label' => null,
    'hint'  => null,
    'src'   => null, // string|Livewire TemporaryUploadedFile|null
    'disk'  => config('filesystems.default'),
    'nextToLabel' => null
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
        $previewUrl = Str::startsWith($src, ['http://', 'https://', '//', 'data:'])
            ? $src
            : Storage::disk($disk)->url($src);
    }
@endphp

<div
    x-data="{
        isUploading:false,
        progress:0,
        isDragOver:false,
        handleDrop(e){
            e.preventDefault();
            this.isDragOver=false;
            const files=e.dataTransfer.files;
            if(files.length){
                const i=this.$refs.fileInput;
                i.files=files;
                i.dispatchEvent(new Event('change',{bubbles:true}));
            }
        },
        handleDragOver(e){ e.preventDefault(); this.isDragOver=true },
        handleDragLeave(e){ e.preventDefault(); this.isDragOver=false },
    }"
    x-on:livewire-upload-start="isUploading = true"
    x-on:livewire-upload-finish="isUploading = false; progress = 0"
    x-on:livewire-upload-error="isUploading = false; progress = 0"
    x-on:livewire-upload-progress="progress = $event.detail.progress"
>
    
    @if($label)
        <div class="flex relative items-center mb-2 space-x-1.5 jutify-between">
            <x-katana.form.label class="shrink-0">{{ $label }}</x-katana.form.label>
            @if($nextToLabel)
                {!! $nextToLabel !!}
            @endif
        </div>
    @endif

    <!-- Audio Preview -->
    @if($previewUrl)
        <div class="flex relative items-center pr-4 mt-3 rounded-lg border border-gray-200" wire:key="audio-preview-{{ $wireModelName }}">
            <div class="flex items-stretch w-full justify-stretch">
                <x-katana.audio-player wire:key="audio-player-{{ $wireModelName }}" :src="$previewUrl" class="border-0" />
            </div>
            
            
            @if($wireModelName)
                <button type="button" class="relative z-10 p-2 rounded-full bg-stone-900" wire:click="$set('{{ $wireModelName }}', null)" title="Remove">
                    <x-phosphor-x-bold class="w-3 h-3 text-white" />
                </button>
            @endif
        </div>
    @else
        <div class="w-full h-auto">
            <!-- File Input Area -->
            <label
                class="flex flex-col justify-center items-center px-8 w-full h-44 text-gray-500 rounded-lg border border-dashed transition-colors cursor-pointer hover:bg-gray-50 border-gray-900/25 hover:border-gray-900/30"
                :class="{ 'border-blue-400 bg-blue-50 text-blue-600': isDragOver }"
                @drop="handleDrop"
                @dragover="handleDragOver"
                @dragleave="handleDragLeave"
                @dragenter.prevent
            >
                <div x-show="!isUploading" class="flex flex-col justify-center items-center space-y-1">
                    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" class="mx-auto text-gray-300/70 size-12 dark:text-gray-600">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14.5v-9l6 4.5-6 4.5z"/>
                    </svg>
                    <div class="flex mt-4 text-gray-500 text-sm/6">
                        <span class="font-medium text-blue-600 cursor-pointer hover:text-indigo-500">Upload audio file</span>
                        <p class="pl-1">or drag and drop</p>
                    </div>
                    <p class="text-xs text-gray-400">MP3, WAV, OGG files supported</p>
                </div>

                <!-- Upload Progress Indicator -->
                <div x-show="isUploading" class="w-full">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-xs font-medium text-gray-500">Uploading...</span>
                        <span class="text-xs font-medium text-gray-500" x-text="`${progress}%`"></span>
                    </div>
                    <div class="w-full h-2 rounded-full bg-stone-200">
                        <div class="h-2 bg-blue-600 rounded-full" :style="`width: ${progress}%`"></div>
                    </div>
                </div>

                <input
                    type="file"
                    accept="audio/*"
                    class="hidden"
                    x-ref="fileInput"
                    {{ $attributes }} {{-- includes wire:model etc --}}
                />
            </label>

            @if($hint)
                <p class="mt-3 text-xs text-gray-500">{{ $hint }}</p>
            @endif
        </div>
    @endif
</div>