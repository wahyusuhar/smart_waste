@props(['message', 'title' => 'Berhasil'])

{{--
    Success message after create, update, delete and registration (session('status')). It closes itself when
    the countdown bar finishes; hovering pauses the bar (app.css), and with it the closing.
--}}
<div class="pointer-events-none fixed inset-x-4 top-4 z-50 flex justify-center sm:inset-x-auto sm:top-6 sm:right-6">
    <div
        x-data="{ show: false }"
        x-init="$nextTick(() => show = true)"
        x-show="show"
        x-transition:enter="transition duration-200 ease-out"
        x-transition:enter-start="-translate-y-2 opacity-0"
        x-transition:enter-end="translate-y-0 opacity-100"
        x-transition:leave="transition duration-150 ease-in"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        x-cloak
        role="status"
        aria-live="polite"
        class="toast pointer-events-auto w-full max-w-sm overflow-hidden rounded-xl border border-gray-200 bg-white shadow-lg shadow-gray-900/5"
    >
        <div class="flex items-start gap-3 p-4">
            <span class="flex size-8 shrink-0 items-center justify-center rounded-full border border-completed-border bg-completed-bg text-completed-text">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M20 6 9 17l-5-5" />
                </svg>
            </span>
            <div class="min-w-0 flex-1 pt-0.5">
                <p class="text-sm font-semibold text-gray-900">{{ $title }}</p>
                <p class="mt-0.5 text-sm text-gray-600">{{ $message }}</p>
            </div>
            <button type="button" @click="show = false" class="-mt-1 -mr-1 rounded-md p-1 text-gray-400 hover:bg-gray-50 hover:text-gray-600" aria-label="Tutup pesan">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                    <path d="M18 6 6 18M6 6l12 12" />
                </svg>
            </button>
        </div>
        <div class="h-0.5 bg-gray-100">
            <div class="toast-countdown h-full bg-completed-text/60" @animationend="show = false"></div>
        </div>
    </div>
</div>
