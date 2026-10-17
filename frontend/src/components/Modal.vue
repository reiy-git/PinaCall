<template>
  <teleport to="body">
    <transition
      enter-active-class="ease-out duration-200"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="ease-in duration-150"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div v-if="modelValue" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="close"></div>

        <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
          <transition
            enter-active-class="ease-out duration-300"
            enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            enter-to-class="opacity-100 translate-y-0 sm:scale-100"
            leave-active-class="ease-in duration-200"
            leave-from-class="opacity-100 translate-y-0 sm:scale-100"
            leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
          >
            <div
              v-if="modelValue"
              class="relative transform overflow-hidden rounded-3xl bg-white text-left shadow-lg transition-all w-full border border-slate-200"
              :class="maxWidthClass"
            >
              <!-- Header -->
              <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/70">
                <div>
                  <h3 class="text-lg font-bold text-slate-900 leading-tight">{{ title }}</h3>
                  <p v-if="subtitle" class="text-xs text-slate-500 mt-0.5">{{ subtitle }}</p>
                </div>
                <button
                  @click="close"
                  type="button"
                  class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-200/70 hover:text-slate-700 transition"
                >
                  <span class="sr-only">Close</span>
                  <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>

              <!-- Body -->
              <div class="px-6 py-6 max-h-[75vh] overflow-y-auto">
                <slot></slot>
              </div>

              <!-- Footer -->
              <div v-if="$slots.footer" class="px-6 py-4 border-t border-slate-100 bg-slate-50 flex items-center justify-end gap-3">
                <slot name="footer"></slot>
              </div>
            </div>
          </transition>
        </div>
      </div>
    </transition>
  </teleport>
</template>

<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(
  defineProps<{
    modelValue: boolean;
    title: string;
    subtitle?: string;
    maxWidth?: 'sm' | 'md' | 'lg' | 'xl' | '2xl' | '3xl' | '4xl';
  }>(),
  {
    maxWidth: 'lg',
  }
);

const emit = defineEmits(['update:modelValue', 'close']);

const maxWidthClass = computed(() => {
  switch (props.maxWidth) {
    case 'sm': return 'sm:max-w-sm';
    case 'md': return 'sm:max-w-md';
    case 'lg': return 'sm:max-w-lg';
    case 'xl': return 'sm:max-w-xl';
    case '2xl': return 'sm:max-w-2xl';
    case '3xl': return 'sm:max-w-3xl';
    case '4xl': return 'sm:max-w-4xl';
    default: return 'sm:max-w-lg';
  }
});

function close() {
  emit('update:modelValue', false);
  emit('close');
}
</script>
