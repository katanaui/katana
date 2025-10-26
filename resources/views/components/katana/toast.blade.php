<template x-teleport="body">
    <div x-data="{
        toasts: [],
        toastsProgress: [],
        toast: @js(session('toast')),
        type: ['success', 'error', 'warning', 'info'],
        closeInterval: 5000,
        addToast(message, type, description = '') {
            const id = Date.now() + Math.random();
            const toast = { id, type, message, description, startTime: null, rafId: null, pausedAt: null, totalPausedTime: 0 };
            this.toasts.push(toast);
            this.toastsProgress[id] = 0;
            const duration = this.closeInterval;
            const animate = (timestamp) => {
                if (!toast.startTime) toast.startTime = timestamp;
                if (toast.pausedAt !== null) {
                    toast.rafId = requestAnimationFrame(animate);
                    return;
                }
                const elapsed = timestamp - toast.startTime - toast.totalPausedTime;
                this.toastsProgress[id] = Math.round(Math.min((elapsed / duration) * 100, 100));
                if (this.toastsProgress[id] < 100) {
                    toast.rafId = requestAnimationFrame(animate);
                } else {
                    this.removeToast(id);
                }
                this.toasts = [...this.toasts];
            };
            toast.rafId = requestAnimationFrame(animate);
        },
        pauseToast(id) {
            const toast = this.toasts.find(t => t.id === id);
            if (toast && toast.pausedAt === null) {
                toast.pausedAt = performance.now();
            }
        },
        resumeToast(id) {
            const toast = this.toasts.find(t => t.id === id);
            if (toast && toast.pausedAt !== null) {
                toast.totalPausedTime += performance.now() - toast.pausedAt;
                toast.pausedAt = null;
            }
        },
        removeToast(id) {
            const idx = this.toasts.findIndex(t => t.id === id);
            if (idx !== -1) {
                const toast = this.toasts[idx];
                if (toast.rafId) cancelAnimationFrame(toast.rafId);
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
            success: { icon: 'check-circle', colorClass: 'text-green-400' },
            error: { icon: 'exclamation-circle', colorClass: 'text-red-400' },
            warning: { icon: 'exclamation-triangle', colorClass: 'text-yellow-400' },
            info: { icon: 'information-circle', colorClass: 'text-blue-400' }
        },
        icons: {
            'check-circle': `<svg xmlns='http://www.w3.org/2000/svg' class='h-full w-full' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><path d='M21.801 10A10 10 0 1 1 17 3.335'/><path d='m9 11 3 3L22 4'/></svg>`,
            'exclamation-circle': `<svg xmlns='http://www.w3.org/2000/svg' class='h-full w-full' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><circle cx='12' cy='12' r='10'/><line x1='12' x2='12' y1='8' y2='12'/><line x1='12' x2='12.01' y1='16' y2='16'/></svg>`,
            'exclamation-triangle': `<svg xmlns='http://www.w3.org/2000/svg' class='h-full w-full' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><path d='M7.9 20A9 9 0 1 0 4 16.1L2 22Z'/><path d='M12 8v4'/><path d='M12 16h.01'/></svg>`,
            'information-circle': `<svg xmlns='http://www.w3.org/2000/svg' class='h-full w-full' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><path d='M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z'/><path d='M13 8H7'/><path d='M17 12H7'/></svg>`
        }
    }" x-init="@if(session('toast'))
    addToast(toast.message, toast.type, toast.description);
    @endif
    let toastFunction = function(message, type, description) {
        addToast(message, type, description);
    }
    window.toast = toastFunction;" x-show="toasts.length" x-transition:enter="transition ease-in-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in-out duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" x-cloak {{ $attributes->twMerge('fixed inset-0 px-2 pointer-events-none pb-4 space-y-2 w-screen h-screen max-w-sm z-[99999999]') }} @pop-toast.window="if(typeof(event.detail[0]) != 'undefined'){ addToast(event.detail[0].message, event.detail[0].type, event.detail[0].description) } else { addToast(event.detail.message, event.detail.type, event.detail.description); }">
        <template x-for="toast in toasts" :key="toast.id">
            <div x-data class="starting:opacity-0 starting:-translate-y-full ending:-translate-y-full ending:opacity-0 backdrop-blur-xs group pointer-events-auto fixed left-1/2 top-0 mt-3 flex w-full max-w-sm -translate-x-1/2 translate-y-0 flex-col items-start overflow-hidden rounded-2xl bg-black/60 p-3.5 px-5 text-sm text-white opacity-100 duration-300 ease-out" :id="'katana-toast-' + toast.id" popover="manual" @mouseenter="pauseToast(toast.id)" @mouseleave="resumeToast(toast.id)" role="alert">
                <!-- Progress Bar -->
                <div class="absolute inset-0 z-10 h-full bg-black/70 duration-300 ease-linear" :style="`width: ${toastsProgress[toast.id]}%;`"></div>
                <span class="relative z-20 flex w-full items-start space-x-2">
                    <span x-show="toast.type" x-html="icons[types[toast.type].icon]" :class="'w-5 h-5 -ml-1.5 shrink-0 ' + types[toast.type].colorClass"></span>
                    <span x-text="toast.message"></span>
                    <span x-on:click="removeToast(toast.id)" class="absolute right-0 top-1/2 flex h-6 w-6 -translate-y-1/2 translate-x-1.5 scale-50 cursor-pointer items-center justify-center rounded-lg bg-black/50 opacity-0 duration-100 ease-out hover:opacity-100 group-hover:scale-100 group-hover:text-white group-hover:opacity-50 group-hover:hover:opacity-100" :class="{ '-mt-1 -mr-1': toast.description != '' }">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 6 6 18" />
                            <path d="m6 6 12 12" />
                        </svg>
                    </span>
                </span>
                <p x-show="toast.description" x-text="toast.description" class="relative z-20 pl-7 text-xs text-white/70">
                </p>
            </div>
        </template>
    </div>
</template>
