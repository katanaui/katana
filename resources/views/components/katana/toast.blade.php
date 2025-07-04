<div class="fixed top-0 left-1/2 z-50 px-2 pb-4 mt-[52px] space-y-2 w-full max-w-sm z-[99999999] -translate-x-1/2" 
    x-data="{ 
        toasts: [],
        toastsProgress: [],
        toast: @js(session('toast')),
        type: [
            'success',
            'error',
            'warning',
            'info'
        ],
        closeInterval: 5000,
        addToast(message, type, description = '') {
            const id = this.toasts.length + 1;
            const toast = { id, type, message, description, startTime: null, rafId: null };
            this.toasts.push(toast);
            this.toastsProgress[id] = 0;

            const duration = this.closeInterval;
            const animate = (timestamp) => {
                if (!toast.startTime) toast.startTime = timestamp;
                const elapsed = timestamp - toast.startTime;
                this.toastsProgress[id] = Math.round(Math.min((elapsed / duration) * 100, 100));
                // For debugging:
                // console.log('looping');
                // console.log(this.toastsProgress[id]);

                if (this.toastsProgress[id] < 100) {
                    toast.rafId = requestAnimationFrame(animate);
                } else {
                    this.removeToast(id);
                }
                // Force Alpine to update
                this.toasts = [...this.toasts];
            };
            toast.rafId = requestAnimationFrame(animate);
        },
        removeToast(id) {
            const idx = this.toasts.findIndex(t => t.id === id);
            if (idx !== -1) {
                const toast = this.toasts[idx];
                if (toast.rafId) cancelAnimationFrame(toast.rafId);
                // Animate out
                const toastToRemoveEl = document.getElementById('katana-toast-' + id);
                if (toastToRemoveEl) {
                    toastToRemoveEl.classList.remove('translate-y-0');
                    toastToRemoveEl.classList.remove('opacity-100');
                    toastToRemoveEl.classList.add('-translate-y-full');
                    toastToRemoveEl.classList.add('opacity-0');
                }
                setTimeout(() => {
                    this.toasts = this.toasts.filter(toast => toast.id !== id);
                    delete this.toastsProgress[id];
                }, 1000);
            }
        },
        types: {
            success: {
                icon: 'check-circle',
                colorClass: 'text-green-400'
            },
            error: {
                icon: 'exclamation-circle',
                colorClass: 'text-red-400'
            },
            warning: {
                icon: 'exclamation-triangle',
                colorClass: 'text-yellow-400'
            },
            info: {
                icon: 'information-circle',
                colorClass: 'text-blue-400'
            }
        },
        icons: {
            'check-circle': `<svg xmlns='http://www.w3.org/2000/svg' class='w-full h-full' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><path d='M21.801 10A10 10 0 1 1 17 3.335'/><path d='m9 11 3 3L22 4'/></svg>`,
            'exclamation-circle': `<svg xmlns='http://www.w3.org/2000/svg' class='w-full h-full' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><circle cx='12' cy='12' r='10'/><line x1='12' x2='12' y1='8' y2='12'/><line x1='12' x2='12.01' y1='16' y2='16'/></svg>`,
            'exclamation-triangle': `<svg xmlns='http://www.w3.org/2000/svg' class='w-full h-full' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><path d='M7.9 20A9 9 0 1 0 4 16.1L2 22Z'/><path d='M12 8v4'/><path d='M12 16h.01'/></svg>`,
            'information-circle': `<svg xmlns='http://www.w3.org/2000/svg' class='w-full h-full' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><path d='M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z'/><path d='M13 8H7'/><path d='M17 12H7'/></svg>`
        }
    }" 
    x-init="
        @if(session('toast'))
            addToast(toast.message, toast.type, toast.description);
        @endif
    "
    @pop-toast.window="console.log('made yo'); addToast($event.detail.message, $event.detail.type, $event.detail.description)"
    x-show="toasts.length" x-transition:enter="transition ease-in-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in-out duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" x-cloak>
    <template x-for="toast in toasts" :key="toast.id">
        <div :id="'katana-toast-' + toast.id" class="flex overflow-hidden relative flex-col items-start p-3.5 space-y-0.5 text-sm text-white rounded-2xl opacity-100 duration-300 ease-out translate-y-0 starting:opacity-0 starting:-translate-y-full ending:-translate-y-full ending:opacity-0 backdrop-blur-xs group bg-black/60" role="alert">
            <!-- Progress Bar -->
            <div class="absolute inset-0 z-10 h-full duration-100 ease-in-out bg-black/70" :style="`width: ${toastsProgress[toast.id]}%;`"></div>
            <span class="flex relative z-20 items-center space-x-2 w-full">
                <span :class="'w-5 h-5 ' + types[toast.type].colorClass" x-html="icons[types[toast.type].icon]"></span>
                <span x-text="toast.message"></span>
                <span x-on:click="removeToast(toast.id)" 
                    class="flex absolute right-0 top-1/2 justify-center items-center w-6 h-6 rounded-lg opacity-0 duration-100 ease-out scale-50 -translate-y-1/2 cursor-pointer group-hover:scale-100 group-hover:opacity-50 group-hover:hover:opacity-100 group-hover:text-white hover:opacity-100 bg-black/50"
                    :class="{ '-mt-1 -mr-1' : toast.description != '' }">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                </span>
            </span>
            <p x-show="toast.description" class="relative z-20 pl-7 text-xs text-white/70" x-text="toast.description"></p>
        </div>
    </template>
</div>