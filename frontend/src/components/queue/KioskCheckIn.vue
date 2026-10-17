<template>
  <div class="min-h-screen bg-gradient-to-br from-zinc-950 via-emerald-950 to-emerald-900 flex flex-col items-center justify-center p-6 text-white">
    <!-- Header -->
    <div class="text-center mb-8">
      <p class="text-xs font-bold uppercase tracking-[0.25em] text-emerald-400">PINACALL Campus Check-In</p>
      <h1 class="mt-2 text-3xl font-black tracking-tight sm:text-4xl">Administration Building Hall</h1>
    </div>

    <!-- Check-in Card -->
    <div class="w-full max-w-lg rounded-3xl border border-emerald-800/40 bg-black/40 p-8 shadow-2xl backdrop-blur-sm">
      <!-- Success State -->
      <div v-if="result" class="text-center space-y-5">
        <div v-if="result.success" class="space-y-4">
          <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-emerald-500/20 ring-2 ring-emerald-400">
            <span class="text-4xl">✓</span>
          </div>
          <div>
            <p class="text-sm text-emerald-300 font-semibold uppercase tracking-wider">{{ result.already_checked_in ? 'Already Checked In' : 'Check-In Successful' }}</p>
            <p class="mt-3 text-5xl font-black text-white tracking-tight">{{ result.token?.token_number }}</p>
            <p class="mt-2 text-sm text-slate-300">{{ result.message }}</p>
          </div>
          <button
            @click="resetForm"
            class="mt-4 w-full rounded-xl bg-emerald-600 px-6 py-3 text-sm font-bold text-white shadow-lg hover:bg-emerald-500 transition"
          >
            New Check-In
          </button>
        </div>

        <div v-else class="space-y-4">
          <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-red-500/20 ring-2 ring-red-400">
            <span class="text-4xl">✕</span>
          </div>
          <p class="text-sm text-red-300 font-semibold">{{ result.message }}</p>
          <button
            @click="resetForm"
            class="mt-4 w-full rounded-xl bg-white/10 px-6 py-3 text-sm font-bold text-white border border-white/20 hover:bg-white/20 transition"
          >
            Try Again
          </button>
        </div>
      </div>

      <!-- Input State -->
      <div v-else class="space-y-6">
        <div class="text-center">
          <p class="text-emerald-200 text-sm font-medium">Welcome! Please check in to enter the digital queue.</p>
        </div>

        <div>
          <label class="text-xs font-bold uppercase tracking-wider text-emerald-300/80 mb-2 block">Enter Your Student ID</label>
          <div
            class="w-full rounded-xl border border-emerald-700/50 bg-black/50 px-4 py-4 text-2xl font-bold tracking-[0.15em] text-white text-center min-h-[3.5rem] flex items-center justify-center"
          >
            {{ studentId || '—' }}
          </div>
        </div>

        <!-- Numpad -->
        <div class="grid grid-cols-3 gap-2">
          <button
            v-for="key in numpadKeys"
            :key="key"
            @click="onKeyPress(key)"
            class="rounded-xl border border-emerald-800/40 bg-white/5 px-4 py-4 text-xl font-bold text-white hover:bg-emerald-700/30 active:bg-emerald-600/40 transition select-none"
            :class="{
              'col-span-1': key !== 'ENTER',
            }"
          >
            {{ key === 'DEL' ? '⌫' : key === 'CLR' ? 'C' : key }}
          </button>
        </div>

        <!-- Submit -->
        <button
          @click="submitCheckin"
          :disabled="!studentId || isLoading"
          class="w-full rounded-xl bg-emerald-600 px-6 py-4 text-lg font-black uppercase tracking-wider text-white shadow-lg transition disabled:opacity-40 disabled:cursor-not-allowed hover:bg-emerald-500"
        >
          <span v-if="isLoading" class="flex items-center justify-center gap-2">
            <span class="h-4 w-4 animate-spin rounded-full border-2 border-white/30 border-t-white"></span>
            Checking In...
          </span>
          <span v-else>Generate Queue Token</span>
        </button>

        <p class="text-center text-xs text-emerald-200/60">
          <span class="font-semibold text-amber-300">Note:</span> You must have an active request submitted in the PINACALL portal to activate your token.
        </p>
      </div>
    </div>

    <!-- Queue Status Lookup (bottom) -->
    <div class="mt-6 w-full max-w-lg">
      <button
        @click="showStatusLookup = !showStatusLookup"
        class="w-full text-center text-xs font-semibold text-emerald-300/70 hover:text-emerald-300 transition"
      >
        {{ showStatusLookup ? 'Hide' : 'Check' }} Queue Status
      </button>
      <div v-if="showStatusLookup" class="mt-3 rounded-2xl border border-emerald-800/30 bg-black/30 p-4 space-y-3">
        <div class="flex gap-2">
          <input
            v-model="statusLookupId"
            type="text"
            placeholder="Enter Student ID"
            class="flex-1 rounded-lg border border-emerald-700/40 bg-black/40 px-3 py-2 text-sm text-white placeholder-slate-500 outline-none focus:border-emerald-500"
          />
          <button
            @click="lookupStatus"
            :disabled="!statusLookupId"
            class="rounded-lg bg-emerald-700 px-4 py-2 text-xs font-bold text-white hover:bg-emerald-600 disabled:opacity-40 transition"
          >
            Search
          </button>
        </div>
        <div v-if="statusResult" class="text-sm">
          <p v-if="statusResult.active_token" class="text-emerald-200">
            <span class="font-bold text-white">{{ statusResult.active_token.token_number }}</span> —
            <span class="capitalize">{{ statusResult.active_token.status.replace('_', ' ') }}</span>
            <span v-if="statusResult.position_in_queue"> · {{ statusResult.position_in_queue === 1 ? "You're next!" : `${statusResult.position_in_queue}${ordinalSuffix(statusResult.position_in_queue)} in line` }}</span>
          </p>
          <p v-else class="text-slate-400">No active queue token found for today.</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { portalApi } from '../../services/api';
import type { QueueToken, QueueStatusResponse } from '../../types';

const studentId = ref('');
const isLoading = ref(false);
const result = ref<{ success: boolean; message: string; token?: QueueToken; already_checked_in?: boolean } | null>(null);
const showStatusLookup = ref(false);
const statusLookupId = ref('');
const statusResult = ref<QueueStatusResponse | null>(null);

const numpadKeys = ['1', '2', '3', '4', '5', '6', '7', '8', '9', 'CLR', '0', 'DEL'];

function onKeyPress(key: string) {
  if (key === 'CLR') {
    studentId.value = '';
  } else if (key === 'DEL') {
    studentId.value = studentId.value.slice(0, -1);
  } else if (studentId.value.length < 20) {
    studentId.value += key;

    // Auto-insert dash after 4 digits for student ID format (e.g., 2023-1042)
    if (studentId.value.length === 4 && !studentId.value.includes('-')) {
      studentId.value += '-';
    }
  }
}

async function submitCheckin() {
  if (!studentId.value || isLoading.value) return;
  isLoading.value = true;
  try {
    const res = await portalApi.kioskCheckin(studentId.value);
    result.value = res;
  } catch (err: any) {
    result.value = { success: false, message: err.message || 'Check-in failed. Please try again.' };
  } finally {
    isLoading.value = false;
  }
}

function resetForm() {
  studentId.value = '';
  result.value = null;
}

async function lookupStatus() {
  if (!statusLookupId.value) return;
  try {
    const res = await portalApi.getQueueStatus(statusLookupId.value);
    statusResult.value = res;
  } catch {
    statusResult.value = null;
  }
}

function ordinalSuffix(n: number): string {
  const s = ['th', 'st', 'nd', 'rd'];
  const v = n % 100;
  return (s[(v - 20) % 10] || s[v] || s[0]);
}
</script>
