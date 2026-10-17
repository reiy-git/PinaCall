<template>
  <div class="min-h-screen bg-zinc-950 text-white flex flex-col">
    <!-- Header Bar -->
    <div class="flex items-center justify-between bg-gradient-to-r from-emerald-900 to-emerald-800 px-6 py-3 border-b border-emerald-700/50">
      <div class="flex items-center gap-3">
        <span class="text-lg font-black tracking-tight">PINACALL Campus Queue Monitor</span>
      </div>
      <div class="flex items-center gap-4 text-sm">
        <span class="font-mono text-emerald-200">{{ currentTime }}</span>
        <span class="text-emerald-300/70">|</span>
        <span class="text-emerald-200/80">AY 2025–2026</span>
      </div>
    </div>

    <!-- Main Queue Display -->
    <div class="flex-1 grid grid-cols-1 lg:grid-cols-2 gap-0 lg:gap-px bg-emerald-900/20">
      <!-- Cashier Counter -->
      <div class="flex flex-col bg-zinc-950 p-6 lg:p-8">
        <div class="text-center mb-6">
          <p class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-400">Cashier Counter</p>
          <h2 class="text-lg font-black text-white mt-1">Fee Payment Window</h2>
        </div>

        <!-- Now Serving -->
        <div class="flex-1 flex flex-col items-center justify-center rounded-2xl border border-emerald-800/40 bg-gradient-to-b from-emerald-950/50 to-black/30 p-8">
          <p class="text-xs font-bold uppercase tracking-wider text-emerald-400/70 mb-3">Now Serving</p>
          <template v-if="lobbyData?.cashier.now_serving">
            <p class="text-6xl sm:text-7xl font-black tracking-tight text-emerald-300 animate-pulse">
              {{ lobbyData.cashier.now_serving.token_number }}
            </p>
            <div class="mt-4 space-y-1 text-center text-sm">
              <p class="text-slate-300">
                <span class="text-emerald-400/70 font-semibold">Student ID:</span>
                {{ lobbyData.cashier.now_serving.student?.student_number || '—' }}
              </p>
              <p class="text-slate-300">
                <span class="text-emerald-400/70 font-semibold">Counter:</span>
                {{ lobbyData.cashier.now_serving.window_number || '—' }}
              </p>
              <p class="text-slate-300">
                <span class="text-emerald-400/70 font-semibold">Action:</span> Fee Payment
              </p>
            </div>
          </template>
          <template v-else>
            <p class="text-5xl font-black text-slate-600">— — —</p>
            <p class="mt-3 text-sm text-slate-500">No student currently being served</p>
          </template>
        </div>

        <!-- Upcoming -->
        <div class="mt-4 rounded-xl border border-emerald-900/30 bg-black/20 p-4">
          <p class="text-[10px] font-bold uppercase tracking-wider text-emerald-400/60 mb-2">Upcoming in Line</p>
          <div v-if="lobbyData?.cashier.upcoming.length" class="space-y-1.5">
            <div v-for="token in lobbyData.cashier.upcoming" :key="token.id" class="flex items-center justify-between text-sm">
              <span class="font-bold text-white">{{ token.token_number }}</span>
              <span class="text-xs text-slate-500">Waiting</span>
            </div>
          </div>
          <p v-else class="text-xs text-slate-600">No students waiting</p>
        </div>

        <p class="mt-3 text-center text-xs text-emerald-300/40">{{ lobbyData?.cashier.waiting_count ?? 0 }} student(s) in cashier queue</p>
      </div>

      <!-- Registrar Counter -->
      <div class="flex flex-col bg-zinc-950 p-6 lg:p-8">
        <div class="text-center mb-6">
          <p class="text-xs font-bold uppercase tracking-[0.2em] text-teal-400">Registrar Counter</p>
          <h2 class="text-lg font-black text-white mt-1">Document Release Window</h2>
        </div>

        <!-- Now Serving -->
        <div class="flex-1 flex flex-col items-center justify-center rounded-2xl border border-teal-800/40 bg-gradient-to-b from-teal-950/50 to-black/30 p-8">
          <p class="text-xs font-bold uppercase tracking-wider text-teal-400/70 mb-3">Now Serving</p>
          <template v-if="lobbyData?.registrar.now_serving">
            <p class="text-6xl sm:text-7xl font-black tracking-tight text-teal-300 animate-pulse">
              {{ lobbyData.registrar.now_serving.token_number }}
            </p>
            <div class="mt-4 space-y-1 text-center text-sm">
              <p class="text-slate-300">
                <span class="text-teal-400/70 font-semibold">Student ID:</span>
                {{ lobbyData.registrar.now_serving.student?.student_number || '—' }}
              </p>
              <p class="text-slate-300">
                <span class="text-teal-400/70 font-semibold">Counter:</span>
                {{ lobbyData.registrar.now_serving.window_number || '—' }}
              </p>
              <p class="text-slate-300">
                <span class="text-teal-400/70 font-semibold">Action:</span> Document Collection
              </p>
            </div>
          </template>
          <template v-else>
            <p class="text-5xl font-black text-slate-600">— — —</p>
            <p class="mt-3 text-sm text-slate-500">No student currently being served</p>
          </template>
        </div>

        <!-- Ready for Pickup Queue -->
        <div class="mt-4 rounded-xl border border-teal-900/30 bg-black/20 p-4">
          <p class="text-[10px] font-bold uppercase tracking-wider text-teal-400/60 mb-2">Ready for Pickup Queue</p>
          <div v-if="lobbyData?.registrar.upcoming.length" class="space-y-1.5">
            <div v-for="token in lobbyData.registrar.upcoming" :key="token.id" class="flex items-center justify-between text-sm">
              <span class="font-bold text-white">{{ token.token_number }}</span>
              <span class="text-xs text-slate-500">Waiting</span>
            </div>
          </div>
          <p v-else class="text-xs text-slate-600">No documents queued for pickup</p>
        </div>

        <p class="mt-3 text-center text-xs text-teal-300/40">{{ lobbyData?.registrar.waiting_count ?? 0 }} student(s) in registrar queue</p>
      </div>
    </div>

    <!-- Bottom Status Search Bar -->
    <div class="border-t border-emerald-900/30 bg-black/40 px-6 py-3">
      <div class="flex items-center justify-center gap-3 flex-wrap">
        <span class="text-xs font-bold text-emerald-400/60 uppercase tracking-wider">Search Queue Status</span>
        <div class="flex items-center gap-2">
          <input
            v-model="searchStudentId"
            type="text"
            placeholder="Student ID (e.g., 2023-1042)"
            class="rounded-lg border border-emerald-800/40 bg-black/50 px-3 py-1.5 text-sm text-white placeholder-slate-600 outline-none focus:border-emerald-500 w-52"
            @keydown.enter="searchStatus"
          />
          <button
            @click="searchStatus"
            class="rounded-lg bg-emerald-800 px-4 py-1.5 text-xs font-bold text-white hover:bg-emerald-700 transition"
          >
            Search
          </button>
        </div>
        <span v-if="searchResult" class="text-sm">
          <template v-if="searchResult.active_token">
            <span class="font-bold text-white">{{ searchResult.active_token.token_number }}</span>
            <span class="text-slate-400"> — </span>
            <span class="text-emerald-300 capitalize">{{ searchResult.active_token.status.replace('_', ' ') }}</span>
            <span v-if="searchResult.position_in_queue" class="text-amber-300 font-semibold">
              ({{ searchResult.position_in_queue === 1 ? "Next!" : `${searchResult.position_in_queue} in line` }})
            </span>
          </template>
          <span v-else class="text-slate-500">No active token today</span>
        </span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { portalApi } from '../../services/api';
import type { LobbyDisplayData, QueueStatusResponse } from '../../types';

const lobbyData = ref<LobbyDisplayData | null>(null);
const currentTime = ref('');
const searchStudentId = ref('');
const searchResult = ref<QueueStatusResponse | null>(null);

let refreshInterval: ReturnType<typeof setInterval>;
let clockInterval: ReturnType<typeof setInterval>;

function updateClock() {
  currentTime.value = new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
}

async function loadLobbyData() {
  try {
    const res = await portalApi.getLobbyDisplay();
    lobbyData.value = res;
  } catch {
    // Silently retry on next interval
  }
}

async function searchStatus() {
  if (!searchStudentId.value) return;
  try {
    searchResult.value = await portalApi.getQueueStatus(searchStudentId.value);
  } catch {
    searchResult.value = null;
  }
}

onMounted(() => {
  updateClock();
  void loadLobbyData();
  refreshInterval = setInterval(loadLobbyData, 5000); // Refresh every 5 seconds
  clockInterval = setInterval(updateClock, 1000);
});

onUnmounted(() => {
  clearInterval(refreshInterval);
  clearInterval(clockInterval);
});
</script>
