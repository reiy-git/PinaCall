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
      <div v-if="inline || modelValue" :id="inline ? 'sign-in' : undefined" :class="inline ? 'w-full' : 'fixed inset-0 z-50'" @keydown.esc.prevent="close" @keydown.tab="!inline && keepFocusInside($event)">
        <button v-if="!inline" class="absolute inset-0 h-full w-full bg-slate-950/55 backdrop-blur-[2px]" aria-label="Close sign in panel" @click="close" />
        <aside
          ref="panel"
          class="flex w-full flex-col overflow-y-auto border border-emerald-900/10 bg-white shadow-xl"
          :class="inline ? 'min-h-[32rem] rounded-3xl lg:sticky lg:top-24' : 'absolute inset-y-0 right-0 max-w-xl border-l shadow-2xl'"
          :role="inline ? undefined : 'dialog'"
          :aria-modal="inline ? undefined : 'true'"
          aria-labelledby="auth-panel-title"
          tabindex="-1"
        >
          <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-6 py-5 sm:px-8">
            <div>
              <p class="text-xs font-bold uppercase tracking-[0.16em] text-emerald-800">PINACALL University Portal</p>
              <h2 id="auth-panel-title" class="mt-2 text-2xl font-bold tracking-tight text-slate-950">
                {{ mode === 'login' ? 'Sign in to your account' : 'Create a student account' }}
              </h2>
              <p class="mt-1 text-sm text-slate-600">
                {{ mode === 'login' ? 'Access your academic and university services.' : 'Start your student registration securely.' }}
              </p>
            </div>
            <button v-if="!inline" ref="closeButton" type="button" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-700" aria-label="Close sign in panel" @click="close">
              <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 6 12 12M18 6 6 18" /></svg>
            </button>
          </div>

          <div class="flex-1 px-6 py-7 sm:px-8">
            <div v-if="!inline" class="mb-7 rounded-xl border border-emerald-900/10 bg-emerald-50/70 p-4 text-sm text-emerald-950">
              <p class="font-semibold">Institutional access</p>
              <p class="mt-1 leading-relaxed text-emerald-900/80">Use the account issued to you by PINACALL. If you need help accessing your account, contact the Registrar or ICT Service Desk.</p>
            </div>

            <div class="mb-6 flex border-b border-slate-200" role="tablist" aria-label="Account access options">
              <button type="button" role="tab" :aria-selected="mode === 'login'" class="flex-1 border-b-2 py-3 text-sm font-semibold" :class="mode === 'login' ? 'border-emerald-800 text-emerald-900' : 'border-transparent text-slate-500 hover:text-slate-800'" @click="switchMode('login')">Sign in</button>
              <button type="button" role="tab" :aria-selected="mode === 'register'" class="flex-1 border-b-2 py-3 text-sm font-semibold" :class="mode === 'register' ? 'border-emerald-800 text-emerald-900' : 'border-transparent text-slate-500 hover:text-slate-800'" @click="switchMode('register')">New student</button>
            </div>

            <form v-if="mode === 'login'" class="space-y-5" @submit.prevent="handleLogin">
              <div>
                <label for="login-email" class="mb-1.5 block text-sm font-semibold text-slate-800">Institutional email</label>
                <input id="login-email" ref="firstField" v-model.trim="loginEmail" type="email" autocomplete="username" required maxlength="255" placeholder="name@university.edu" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-emerald-800 focus:ring-2 focus:ring-emerald-800/15">
              </div>
              <div>
                <label for="login-password" class="mb-1.5 block text-sm font-semibold text-slate-800">Password</label>
                <input id="login-password" v-model="loginPassword" type="password" autocomplete="current-password" required maxlength="255" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-emerald-800 focus:ring-2 focus:ring-emerald-800/15">
              </div>
              <p v-if="errorMessage" class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800" role="alert">{{ errorMessage }}</p>
              <button type="submit" :disabled="isLoading" class="w-full rounded-xl bg-emerald-900 px-4 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-emerald-950 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-700 focus-visible:ring-offset-2 disabled:cursor-wait disabled:opacity-60">
                {{ isLoading ? 'Signing in…' : 'Sign in securely' }}
              </button>
              <p class="text-center text-xs leading-relaxed text-slate-500">Your account permissions determine the academic services available to you.</p>
            </form>

            <form v-else class="space-y-4" @submit.prevent="handleRegister">
              <div>
                <label for="register-name" class="mb-1.5 block text-sm font-semibold text-slate-800">Full legal name</label>
                <input id="register-name" ref="firstField" v-model.trim="regForm.name" type="text" autocomplete="name" required maxlength="255" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-emerald-800 focus:ring-2 focus:ring-emerald-800/15">
              </div>
              <div>
                <label for="register-email" class="mb-1.5 block text-sm font-semibold text-slate-800">Email address</label>
                <input id="register-email" v-model.trim="regForm.email" type="email" autocomplete="email" required maxlength="255" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-emerald-800 focus:ring-2 focus:ring-emerald-800/15">
              </div>
              <div class="grid gap-4 sm:grid-cols-2">
                <div>
                  <label for="register-type" class="mb-1.5 block text-sm font-semibold text-slate-800">Student category</label>
                  <select id="register-type" v-model="regForm.student_type" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-3 text-sm outline-none focus:border-emerald-800 focus:ring-2 focus:ring-emerald-800/15">
                    <option value="new">New student</option><option value="transferee">Transferee</option><option value="irregular">Irregular</option><option value="old">Continuing student</option><option value="shiftee">Shiftee</option><option value="returnee">Returnee</option>
                  </select>
                </div>
                <div>
                  <label for="register-year" class="mb-1.5 block text-sm font-semibold text-slate-800">Year level</label>
                  <select id="register-year" v-model.number="regForm.year_level" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-3 text-sm outline-none focus:border-emerald-800 focus:ring-2 focus:ring-emerald-800/15">
                    <option :value="1">1st year</option><option :value="2">2nd year</option><option :value="3">3rd year</option><option :value="4">4th year</option>
                  </select>
                </div>
              </div>
              <div>
                <label for="register-program" class="mb-1.5 block text-sm font-semibold text-slate-800">Degree program</label>
                <select id="register-program" v-model="regForm.course_program" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-3 text-sm outline-none focus:border-emerald-800 focus:ring-2 focus:ring-emerald-800/15">
                  <option>BS Information Technology</option><option>BS Computer Science</option><option>BS Information Systems</option>
                </select>
              </div>
              <div>
                <label for="register-password" class="mb-1.5 block text-sm font-semibold text-slate-800">Password</label>
                <input id="register-password" v-model="regForm.password" type="password" autocomplete="new-password" minlength="12" maxlength="255" required class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-emerald-800 focus:ring-2 focus:ring-emerald-800/15">
                <p class="mt-1 text-xs text-slate-500">Use at least 12 characters.</p>
              </div>
              <p v-if="errorMessage" class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800" role="alert">{{ errorMessage }}</p>
              <button type="submit" :disabled="isLoading" class="w-full rounded-xl bg-emerald-900 px-4 py-3 text-sm font-bold text-white transition hover:bg-emerald-950 disabled:cursor-wait disabled:opacity-60">
                {{ isLoading ? 'Creating account…' : 'Create student account' }}
              </button>
            </form>
          </div>

          <footer class="border-t border-slate-200 px-6 py-4 text-xs text-slate-500 sm:px-8">
            PINACALL Management System · Official academic and student services
          </footer>
        </aside>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { nextTick, ref, watch } from 'vue';
import { portalApi } from '../../services/api';
import type { StudentType } from '../../types';

const props = withDefaults(defineProps<{ modelValue: boolean; inline?: boolean }>(), { inline: false });
const emit = defineEmits<{ 'update:modelValue': [value: boolean] }>();

const panel = ref<HTMLElement | null>(null);
const firstField = ref<HTMLInputElement | null>(null);
const previouslyFocusedElement = ref<HTMLElement | null>(null);
const mode = ref<'login' | 'register'>('login');
const loginEmail = ref('');
const loginPassword = ref('');
const isLoading = ref(false);
const errorMessage = ref('');
const regForm = ref({
  name: '',
  email: '',
  student_type: 'new' as StudentType,
  year_level: 1,
  course_program: 'BS Information Technology',
  password: '',
});

function close() {
  if (!isLoading.value) emit('update:modelValue', false);
}

function switchMode(nextMode: 'login' | 'register') {
  mode.value = nextMode;
  errorMessage.value = '';
  void nextTick(() => firstField.value?.focus());
}

function keepFocusInside(event: KeyboardEvent) {
  if (!panel.value) return;
  const focusable = [...panel.value.querySelectorAll<HTMLElement>('button:not(:disabled), input:not(:disabled), select:not(:disabled), [tabindex="0"]')];
  const first = focusable[0];
  const last = focusable[focusable.length - 1];
  if (event.shiftKey && document.activeElement === first) {
    event.preventDefault();
    last?.focus();
  } else if (!event.shiftKey && document.activeElement === last) {
    event.preventDefault();
    first?.focus();
  }
}

watch(() => props.modelValue, async (isOpen) => {
  if (isOpen) {
    previouslyFocusedElement.value = document.activeElement instanceof HTMLElement
      ? document.activeElement
      : null;
    errorMessage.value = '';
    await nextTick();
    firstField.value?.focus();
  } else {
    if (!props.inline) previouslyFocusedElement.value?.focus();
    previouslyFocusedElement.value = null;
  }
});

async function handleLogin() {
  isLoading.value = true;
  errorMessage.value = '';
  try {
    await portalApi.login(loginEmail.value, loginPassword.value);
    emit('update:modelValue', false);
    loginPassword.value = '';
  } catch (error) {
    errorMessage.value = error instanceof Error ? error.message : 'Sign-in failed. Please try again.';
  } finally {
    isLoading.value = false;
  }
}

async function handleRegister() {
  isLoading.value = true;
  errorMessage.value = '';
  try {
    await portalApi.register(regForm.value);
    emit('update:modelValue', false);
    regForm.value.password = '';
  } catch (error) {
    errorMessage.value = error instanceof Error ? error.message : 'Registration failed. Please review your details.';
  } finally {
    isLoading.value = false;
  }
}
</script>
