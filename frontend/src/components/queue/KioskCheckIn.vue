<template>
  <main class="min-h-screen bg-[#1e1f22] px-3 py-4 text-slate-900 sm:px-6 sm:py-6">
    <div class="mx-auto flex min-h-[calc(100vh-2rem)] w-full max-w-xl flex-col justify-center">
      <section class="overflow-hidden rounded-[2rem] bg-white shadow-2xl shadow-black/25 ring-1 ring-white/30" aria-labelledby="kiosk-title">
        <header class="bg-navy px-6 py-4 text-white sm:px-8">
          <div class="flex items-center justify-center gap-3">
            <img src="/assets/images/seal.webp" alt="PinaCall logo" class="h-9 w-9 rounded-full" width="36" height="36">
            <p class="text-center text-sm font-bold tracking-[0.12em] text-lime">PinaCall CAMPUS CHECK-IN</p>
          </div>
        </header>

        <div class="p-5 sm:p-8">
          <div v-if="result" class="py-5 text-center" role="status" aria-live="polite">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl" :class="result.success ? 'bg-lime text-navy' : 'bg-coral text-white'">
              <svg v-if="result.success" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4L19 6" /></svg>
              <svg v-else class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
            </div>
            <p class="mt-5 text-sm font-bold uppercase tracking-wider" :class="result.success ? 'text-navy' : 'text-coral'">{{ result.success ? 'Check-in confirmed' : 'Check-in could not be completed' }}</p>
            <p v-if="result.success" class="mt-2 text-6xl font-bold tracking-tight text-navy">{{ result.token?.token_number }}</p>
            <p class="mt-3 text-sm text-slate-600">{{ result.success ? 'Please watch the queue monitor for your number.' : result.message }}</p>
            <button type="button" class="mt-7 min-h-16 w-full rounded-2xl bg-navy px-5 py-4 text-base font-bold text-white transition hover:brightness-110 focus-visible:outline-lime" @click="resetForm">Start new check-in</button>
            <p class="mt-3 text-xs text-slate-500">This screen resets automatically in a few seconds.</p>
          </div>

          <form v-else @submit.prevent="submitCheckin" @keydown.enter.prevent="submitCheckin">
            <div class="text-center">
              <h1 id="kiosk-title" class="text-lg font-bold uppercase tracking-wide text-slate-900 sm:text-xl">Welcome! Please check in to enter the digital queue.</h1>
            </div>

            <label for="student-id" class="sr-only">Student ID</label>
            <input
              id="student-id"
              ref="studentInput"
              v-model="studentId"
              type="text"
              inputmode="numeric"
              autocomplete="off"
              maxlength="20"
              @input="handleInput"
              placeholder="0000-0000"
              class="mt-6 min-h-[4.5rem] w-full rounded-2xl border-2 border-slate-200 bg-slate-50 px-4 text-center font-mono text-2xl font-bold tracking-[0.12em] text-navy outline-none placeholder:font-sans placeholder:text-base placeholder:font-medium placeholder:tracking-normal placeholder:text-slate-400 sm:text-3xl"
              aria-describedby="student-id-hint"
              :disabled="isLoading"
            >
            <p id="student-id-hint" class="mt-2 text-center text-xs text-slate-500">Enter your Student ID:</p>

            <div class="mx-auto mt-5 grid max-w-sm grid-cols-3 gap-3" aria-label="Student ID number pad">
              <button
                v-for="key in keypadKeys"
                :key="key"
                type="button"
                class="min-h-[4.25rem] rounded-2xl border border-slate-200 bg-white text-2xl font-bold text-navy shadow-sm transition hover:bg-slate-50 active:scale-[0.97] active:bg-lime/40 disabled:opacity-50 sm:min-h-[4.75rem]"
                :aria-label="key === 'delete' ? 'Delete last digit' : `Enter ${key}`"
                :disabled="isLoading || studentId.length >= studentIdMaxLength"
                @click="appendDigit(key)"
              >{{ key }}</button>
              <button type="button" class="min-h-[4.25rem] rounded-2xl border border-slate-200 bg-slate-100 text-xs font-bold uppercase tracking-wide text-slate-600 transition hover:bg-slate-200 active:scale-[0.97] disabled:opacity-50 sm:min-h-[4.75rem]" :disabled="isLoading || !studentId" @click="clearId">Clear</button>
              <button type="button" class="min-h-[4.25rem] rounded-2xl border border-slate-200 bg-white text-2xl font-bold text-navy shadow-sm transition hover:bg-slate-50 active:scale-[0.97] disabled:opacity-50 sm:min-h-[4.75rem]" aria-label="Enter zero" :disabled="isLoading || studentId.length >= studentIdMaxLength" @click="appendDigit('0')">0</button>
              <button type="button" class="min-h-[4.25rem] rounded-2xl border border-slate-200 bg-slate-100 text-xl font-bold text-navy transition hover:bg-slate-200 active:scale-[0.97] disabled:opacity-50 sm:min-h-[4.75rem]" aria-label="Delete last digit" :disabled="isLoading || !studentId" @click="deleteDigit">⌫</button>
            </div>

            <button type="submit" class="mt-5 min-h-16 w-full rounded-2xl bg-lime px-5 py-4 text-base font-bold text-navy shadow-sm transition hover:brightness-95 focus-visible:outline-navy disabled:cursor-not-allowed disabled:opacity-50" :disabled="!studentId.length || isLoading">
              {{ isLoading ? 'Checking your request…' : 'Generate Queue Token' }}
            </button>
            <p class="mt-5 text-center text-xs leading-relaxed text-slate-500">
              <span class="font-bold text-navy">[!] Note:</span>
              You must have an active request submitted in the PINACALL portal to activate your token.
            </p>
          </form>
        </div>
      </section>
      <p class="mt-4 text-center text-xs font-medium text-white/70">© 2026 PinaCall · Pamantasan ng Cabuyao. All rights reserved.</p>
    </div>
  </main>
</template>

<script setup lang="ts">
import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue';
import type { QueueToken } from '../../types';

const studentInput = ref<HTMLInputElement | null>(null);
const studentId = ref('');
const keypadKeys = ['1', '2', '3', '4', '5', '6', '7', '8', '9'];
const studentIdMaxLength = 20;
const isLoading = ref(false);
const result = ref<{ success: boolean; message: string; token?: QueueToken } | null>(null);
let resetTimer: ReturnType<typeof setTimeout> | undefined;

function handleInput(event: Event) {
  const input = event.target as HTMLInputElement;
  const numericValue = input.value.replace(/\D/g, '').slice(0, studentIdMaxLength);
  studentId.value = numericValue;
  input.value = numericValue;
}

function appendDigit(digit: string) {
  if (studentId.value.length < studentIdMaxLength) studentId.value += digit;
}

function deleteDigit() {
  studentId.value = studentId.value.slice(0, -1);
}

function clearId() {
  studentId.value = '';
}

async function submitCheckin() {
  result.value = {
    success: false,
    message: 'Kiosk check-in has been retired. Scan a physical queue token while signed in to the student portal.',
  };
}

function resetForm() {
  if (resetTimer) clearTimeout(resetTimer);
  resetTimer = undefined;
  result.value = null;
  studentId.value = '';
  void nextTick(() => studentInput.value?.focus());
}

onMounted(() => studentInput.value?.focus());
onBeforeUnmount(() => { if (resetTimer) clearTimeout(resetTimer); });
</script>
