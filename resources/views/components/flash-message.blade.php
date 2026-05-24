{{-- // PURPOSE: Session-driven toast stack for success, error, warning and info messages. --}}
@php
    $messages = collect(['success', 'error', 'warning', 'info', 'status'])
        ->filter(fn ($type) => session()->has($type))
        ->map(fn ($type) => [
            'type' => $type === 'status' ? 'info' : $type,
            'message' => session($type),
        ])
        ->take(3)
        ->values();
@endphp

<div
    x-data="{
        toasts: @js($messages),
        remove(index) { this.toasts.splice(index, 1) },
        init() {
            this.toasts.forEach((toast, index) => {
                toast.timer = setTimeout(() => this.remove(index), 4000 + (index * 300));
            });
        }
    }"
    class="fixed bottom-20 right-4 z-[70] flex w-[calc(100%-2rem)] max-w-sm flex-col gap-3 md:bottom-4"
    aria-live="polite"
>
    <template x-for="(toast, index) in toasts" :key="index">
        <div
            x-show="true"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-3"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 translate-y-3"
            @mouseenter="clearTimeout(toast.timer)"
            @mouseleave="toast.timer = setTimeout(() => remove(index), 1800)"
            class="rounded-lg border bg-white p-4 shadow-soft dark:bg-gray-800"
            :class="{
                'border-success-200 text-success-800 dark:border-success-800 dark:text-success-200': toast.type === 'success',
                'border-danger-200 text-danger-800 dark:border-danger-800 dark:text-danger-200': toast.type === 'error',
                'border-warning-200 text-warning-800 dark:border-warning-800 dark:text-warning-200': toast.type === 'warning',
                'border-info-200 text-info-800 dark:border-info-800 dark:text-info-200': toast.type === 'info'
            }"
            role="status"
        >
            <div class="flex items-start gap-3">
                <i :data-lucide="toast.type === 'success' ? 'check-circle-2' : (toast.type === 'error' ? 'x-circle' : (toast.type === 'warning' ? 'triangle-alert' : 'info'))" class="mt-0.5 h-5 w-5 shrink-0" aria-hidden="true"></i>
                <p class="min-w-0 flex-1 text-sm font-medium" x-text="toast.message"></p>
                <button type="button" @click="remove(index)" class="rounded-md p-1 opacity-70 hover:opacity-100" aria-label="Fermer la notification">
                    <i data-lucide="x" class="h-4 w-4" aria-hidden="true"></i>
                </button>
            </div>
        </div>
    </template>
</div>
