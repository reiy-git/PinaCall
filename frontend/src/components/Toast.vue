<template>
  <div class="fixed bottom-5 right-5 z-50 flex flex-col gap-3 max-w-sm w-full pointer-events-none">
    <transition-group
      enter-active-class="transform ease-out duration-300 transition"
      enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
      enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
      leave-active-class="transition ease-in duration-100"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-for="toast in toasts"
        :key="toast.id"
        class="pointer-events-auto p-4 rounded-2xl shadow-lg border flex items-start gap-3 bg-white text-slate-800"
        :class="{
          'border-emerald-500/40 bg-emerald-50/90 text-emerald-950': toast.type === 'success',
          'border-red-500/40 bg-red-50/90 text-red-950': toast.type === 'error',
          'border-amber-500/40 bg-amber-50/90 text-amber-950': toast.type === 'warning',
          'border-blue-500/40 bg-blue-50/90 text-blue-950': toast.type === 'info',
        }"
      >
        <div class="mt-0.5 flex-shrink-0">
          <span v-if="toast.type === 'success'" class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-emerald-600 text-white text-xs font-bold">✓</span>
          <span v-else-if="toast.type === 'error'" class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-red-600 text-white text-xs font-bold">✕</span>
          <span v-else-if="toast.type === 'warning'" class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-amber-600 text-white text-xs font-bold">!</span>
          <span v-else class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-blue-600 text-white text-xs font-bold">i</span>
        </div>
        <div class="flex-1 min-w-0">
          <p class="text-sm font-semibold leading-tight">{{ toast.title }}</p>
          <p class="text-xs mt-1 text-slate-600 leading-snug">{{ toast.message }}</p>
        </div>
        <button
          @click="removeToast(toast.id)"
          class="text-slate-400 hover:text-slate-600 text-xs font-bold p-1 rounded-md transition"
        >
          ✕
        </button>
      </div>
    </transition-group>
  </div>
</template>

<script setup lang="ts">
import { toasts } from '../services/api';

function removeToast(id: number) {
  toasts.value = toasts.value.filter((t) => t.id !== id);
}
</script>
