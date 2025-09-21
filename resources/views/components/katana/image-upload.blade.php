@props([
    'label' => null,
    'hint'  => null,
    'src'   => null, // string|Livewire TemporaryUploadedFile|null
    'disk'  => config('filesystems.default'),
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
        <x-label>{{ $label }}</x-label>
    @endif

    <!-- Image Preview -->
    @if($previewUrl)
        <div class="relative mt-3 aspect-video">
            @if($wireModelName)
                <button
                    type="button"
                    class="absolute top-0 right-0 z-10 p-2 mt-2 mr-2 rounded-full bg-stone-900"
                    @click.prevent="$wire.set('{{ $wireModelName }}', null)"
                    title="Remove"
                >
                    <x-phosphor-x-bold class="w-3 h-3 text-white" />
                </button>
            @endif

            <img src="{{ $previewUrl }}" class="object-cover w-full h-full rounded-lg border border-gray-200" />
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
                        <path d="M1.5 6a2.25 2.25 0 0 1 2.25-2.25h16.5A2.25 2.25 0 0 1 22.5 6v12a2.25 2.25 0 0 1-2.25 2.25H3.75A2.25 2.25 0 0 1 1.5 18V6ZM3 16.06V18c0 .414.336.75.75.75h16.5A.75.75 0 0 0 21 18v-1.94l-2.69-2.689a1.5 1.5 0 0 0-2.12 0l-.88.879.97.97a.75.75 0 1 1-1.06 1.06l-5.16-5.159a1.5 1.5 0 0 0-2.12 0L3 16.061Zm10.125-7.81a1.125 1.125 0 1 1 2.25 0 1.125 1.125 0 0 1-2.25 0Z"/>
                    </svg>
                    <div class="flex mt-4 text-gray-500 text-sm/6">
                        <span class="font-medium text-blue-600 cursor-pointer hover:text-indigo-500">Upload a file</span>
                        <p class="pl-1">or drag and drop</p>
                    </div>
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