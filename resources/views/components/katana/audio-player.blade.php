@props([
    'src' => '',
    'autoplay' => false,
    'id' => 'audio-player-' . uniqid(),
])

{{-- The @click.stop prevents any click events from being sent to the parent --}}
<div x-data="{
    isPlaying: false,
    currentTime: 0,
    duration: 0,
    progress: 0,
    autoplay: @if ($autoplay) true @else false @endif,

    init() {
        // Initialize audio element
        this.$refs.audio.load();
        window.addEventListener('audio-update-file', (event) => {
            console.log('updated audio file...');
            this.$refs.audio.src = event.detail.file;
            this.$refs.audio.load();
        });
        window.addEventListener('audio-play', (event) => {
            this.$refs.audio.play();
            this.isPlaying = true;
        });
        window.addEventListener('audio-pause', (event) => {
            this.$refs.audio.pause();
            this.isPlaying = false;
        });
        window.addEventListener('audio-stop', (event) => {
            this.$refs.audio.currentTime = 0;
            this.$refs.audio.pause();
            this.isPlaying = false;
        });

        if (this.autoplay) {
            console.log('autoplaying...');
            this.$refs.audio.play();
            this.isPlaying = true;
        }
    },

    togglePlay() {
        if (this.isPlaying) {
            this.$refs.audio.pause();
        } else {
            this.$refs.audio.play();
        }
        this.isPlaying = !this.isPlaying;
    },

    rewind(seconds) {
        this.$refs.audio.currentTime = Math.max(0, this.$refs.audio.currentTime - seconds);
    },

    forward(seconds) {
        this.$refs.audio.currentTime = Math.min(this.duration, this.$refs.audio.currentTime + seconds);
    },

    seek(event) {
        const rect = event.currentTarget.getBoundingClientRect();
        const x = event.clientX - rect.left;
        const percentage = (x / rect.width) * 100;
        const time = (percentage / 100) * this.duration;
        this.$refs.audio.currentTime = time;
    },

    updateProgress() {
        this.currentTime = this.$refs.audio.currentTime;
        if (this.duration > 0) {
            this.progress = (this.currentTime / this.duration) * 100;
        }
    },

    audioLoaded() {
        this.duration = this.$refs.audio.duration;
    },

    audioEnded() {
        window.dispatchEvent(new CustomEvent('audio-player-complete'));
        this.isPlaying = false;
        this.progress = 100;
    },

    formatTime(seconds) {
        if (isNaN(seconds) || seconds === 0) return '0:00';

        const mins = Math.floor(seconds / 60);
        const secs = Math.floor(seconds % 60);
        return `${mins}:${secs.toString().padStart(2, '0')}`;
    }
}" @click.stop="" {{ $attributes->twMerge('flex items-center pr-4 pl-1.5 w-full h-14 shrink-0 bg-gradient-to-b from-white border rounded-md border-gray-200 to-stone-50') }}>
    <!-- Hidden audio element -->
    <audio x-ref="audio" class="hidden" src="{{ $src }}" @timeupdate="updateProgress" @loadedmetadata="audioLoaded" @ended="audioEnded">
    </audio>

    <!-- Custom Player Controls -->
    <div class="flex w-full items-center gap-1">
        <!-- Rewind 15 seconds button -->
        <button class="group relative flex h-10 w-10 items-center justify-center rounded-full transition-colors hover:bg-gray-100" @click="rewind(15)">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-gray-500" viewBox="0 0 256 256">
                <path fill="none" d="M0 0h256v256H0z" />
                <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16" d="M24 56v48h48" />
                <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16" d="M67.59 192a88 88 0 1 0-1.82-126.23L24 104" />
            </svg>
            <span class="absolute text-[10px] font-medium text-gray-500" style="top: 50%; left: 50%; transform: translate(-50%, -50%);">15</span>
        </button>

        <!-- Play/Pause button -->

        <button class="flex h-10 w-10 items-center justify-center rounded-full bg-black text-white transition-colors" @click="togglePlay">
            <!-- Play icon -->
            <svg x-show="!isPlaying" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 translate-x-px" viewBox="0 0 24 24" fill="none" style="">
                <path d="M8.42737 3.41611C6.46665 2.24586 4.00008 3.67188 4.00007 5.9427L4 18.0572C3.99999 20.329 6.46837 21.7549 8.42907 20.5828L18.5698 14.5207C20.4775 13.3802 20.4766 10.6076 18.568 9.46853L8.42737 3.41611Z" fill="currentColor"></path>
            </svg>
            <!-- Pause icon -->
            <svg x-show="isPlaying" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" style="display: none;">
                <path fill-rule="evenodd" clip-rule="evenodd" d="M8 3C8.55228 3 9 3.44772 9 4L9 20C9 20.5523 8.55228 21 8 21C7.44772 21 7 20.5523 7 20L7 4C7 3.44772 7.44772 3 8 3ZM16 3C16.5523 3 17 3.44772 17 4V20C17 20.5523 16.5523 21 16 21C15.4477 21 15 20.5523 15 20V4C15 3.44772 15.4477 3 16 3Z" fill="currentColor"></path>
            </svg>
        </button>

        <!-- Forward 15 seconds button -->
        <button class="group relative flex h-10 w-10 items-center justify-center rounded-full transition-colors hover:bg-gray-100" @click="forward(15)">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-gray-500" viewBox="0 0 256 256">
                <path fill="none" d="M0 0h256v256H0z" />
                <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16" d="M184 104h48V56" />
                <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16" d="M188.4 192a88 88 0 1 1 1.83-126.23L232 104" />
            </svg>
            <span class="absolute text-[10px] font-medium text-gray-500" style="top: 50%; left: 50%; transform: translate(-50%, -50%);">15</span>
        </button>

        <!-- Progress bar and time -->
        <div class="flex flex-1 items-center gap-4 pl-4">
            <!-- Progress bar container -->
            <div class="relative flex-1">
                <div class="group relative h-2 cursor-pointer rounded-full bg-gray-200" @click="seek($event)">
                    <!-- Progress fill -->
                    <div class="absolute h-full rounded-full bg-black transition-all duration-1000 ease-out" :style="`width: ${progress}%`">
                    </div>
                    <!-- Scrubber handle -->
                    <div class="absolute -top-1 h-4 w-4 rounded-full bg-black shadow-md transition-all duration-1000 ease-out" :style="`left: calc(${progress}% - 8px)`">
                    </div>
                </div>
            </div>

            <!-- Time display -->
            <div class="whitespace-nowrap text-sm font-medium text-gray-600">
                <span x-text="formatTime(currentTime)">0:00</span>
                <span class="mx-1">/</span>
                <span x-text="formatTime(duration)">0:00</span>
            </div>
        </div>
    </div>
</div>
