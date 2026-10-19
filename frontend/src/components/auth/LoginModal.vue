<template>
  <Teleport to="body" :disabled="inline">
    <Transition
      enter-active-class="transition-opacity duration-200"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-opacity duration-150"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div v-if="inline || modelValue" :id="inline ? 'sign-in' : undefined" :class="inline ? 'w-full lg:h-full' : 'fixed inset-0 z-50'" @keydown.esc.prevent="close" @keydown.tab="!inline && keepFocusInside($event)">
        <button v-if="!inline" class="absolute inset-0 h-full w-full bg-slate-950/55 backdrop-blur-[2px]" aria-label="Close sign in panel" @click="close" />
        <aside ref="panel" class="flex w-full flex-col overflow-y-auto border border-emerald-900/10 bg-white shadow-xl" :class="inline ? 'rounded-3xl overflow-hidden lg:h-full' : 'absolute inset-y-0 right-0 max-w-xl border-l shadow-lg'" :role="inline ? undefined : 'dialog'" :aria-modal="inline ? undefined : 'true'" aria-labelledby="auth-panel-title" tabindex="-1">
          <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-6 py-4 sm:px-8">
            <div>
              <p class="text-xs font-bold uppercase tracking-wider text-emerald-800">PINACALL University Portal</p>
              <h2 id="auth-panel-title" class="mt-2 text-2xl font-bold tracking-tight text-slate-900">Sign in to your account</h2>
              <p class="mt-1 text-sm text-slate-600">Access your academic and university services.</p>
            </div>
            <button v-if="!inline" ref="closeButton" type="button" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100" aria-label="Close sign in panel" @click="close">
              <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
            </button>
          </div>

          <form class="flex flex-1 flex-col px-6 py-5 sm:px-8 sm:py-6" @submit.prevent="handleLogin">
            <div class="space-y-4">
              <div>
                <label for="login-email" class="mb-1 block text-sm font-semibold text-slate-800">Email address</label>
                <input id="login-email" ref="firstField" v-model="loginEmail" type="email" autocomplete="email" required class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm outline-none focus:border-emerald-800 focus:ring-2 focus:ring-emerald-800/15">
              </div>
              <div>
                <label for="login-password" class="mb-1 block text-sm font-semibold text-slate-800">Password</label>
                <div class="relative">
                  <input id="login-password" v-model="loginPassword" :type="showLoginPassword ? 'text' : 'password'" autocomplete="current-password" required class="w-full rounded-2xl border border-slate-300 py-2.5 pl-4 pr-12 text-sm outline-none focus:border-emerald-800 focus:ring-2 focus:ring-emerald-800/15">
                  <button type="button" class="absolute inset-y-0 right-0 flex w-12 items-center justify-center rounded-r-2xl text-slate-500 hover:text-navy" :aria-label="showLoginPassword ? 'Hide password' : 'Show password'" :aria-pressed="showLoginPassword" @click="showLoginPassword = !showLoginPassword">
                    <svg v-if="!showLoginPassword" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z" /><circle cx="12" cy="12" r="3" /></svg>
                    <svg v-else class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 3l18 18M9.9 9.9a3 3 0 0 0 4.2 4.2M2 12s3.6 7 10 7c1.9 0 3.6-.5 5-1.3M9.9 5.2A10.4 10.4 0 0 1 12 5c6.4 0 10 7 10 7a17 17 0 0 1-3.2 4.2" /></svg>
                  </button>
                </div>
              </div>
            </div>
            <div class="mt-auto space-y-3 pt-6">
              <p v-if="errorMessage" class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800" role="alert">{{ errorMessage }}</p>
              <button type="submit" :disabled="isLoading" class="w-full rounded-2xl bg-lime px-4 py-2.5 text-sm font-bold text-navy transition hover:brightness-95 disabled:cursor-wait disabled:opacity-60">{{ isLoading ? 'Signing in…' : 'Sign in' }}</button>
              <p class="text-center text-sm leading-relaxed text-slate-600">New applicant? <a href="/apply" class="font-semibold text-emerald-800 underline underline-offset-2 hover:text-emerald-950">Apply for admission</a></p>
            </div>
          </form>
          <footer class="border-t border-slate-200 px-6 py-3 text-xs text-slate-500 sm:px-8">PINACALL · Independent student project, not an official University system</footer>
        </aside>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { nextTick, ref, watch } from 'vue';
import { portalApi } from '../../services/api';

const props = withDefaults(defineProps<{ modelValue: boolean; inline?: boolean }>(), { inline: false });
const emit = defineEmits<{ 'update:modelValue': [value: boolean] }>();
const panel = ref<HTMLElement | null>(null);
const firstField = ref<HTMLInputElement | null>(null);
const previouslyFocusedElement = ref<HTMLElement | null>(null);
const loginEmail = ref('');
const loginPassword = ref('');
const showLoginPassword = ref(false);
const isLoading = ref(false);
const errorMessage = ref('');

function close() { if (!isLoading.value) emit('update:modelValue', false); }
function keepFocusInside(event: KeyboardEvent) {
  if (!panel.value) return;
  const focusable = [...panel.value.querySelectorAll<HTMLElement>('button:not(:disabled), input:not(:disabled), [tabindex="0"]')];
  const first = focusable[0]; const last = focusable[focusable.length - 1];
  if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last?.focus(); }
  else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first?.focus(); }
}
watch(() => props.modelValue, async (isOpen) => {
  if (isOpen) { previouslyFocusedElement.value = document.activeElement instanceof HTMLElement ? document.activeElement : null; errorMessage.value = ''; await nextTick(); firstField.value?.focus(); }
  else { if (!props.inline) previouslyFocusedElement.value?.focus(); previouslyFocusedElement.value = null; }
});
async function handleLogin() {
  isLoading.value = true; errorMessage.value = '';
  try { await portalApi.login(loginEmail.value, loginPassword.value); emit('update:modelValue', false); loginPassword.value = ''; }
  catch (error) { errorMessage.value = error instanceof Error ? error.message : 'Sign-in failed. Please try again.'; }
  finally { isLoading.value = false; }
}
</script>
