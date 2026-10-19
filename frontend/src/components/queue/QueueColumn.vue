<template>
  <section class="flex flex-col rounded-[2rem] bg-white p-5 shadow-xl shadow-slate-900/5 ring-1 ring-slate-900/5 sm:p-7">
    <div class="flex items-start justify-between gap-3">
      <div>
        <p class="eyebrow" :class="accent === 'lime' ? 'text-navy' : 'text-coral'">{{ label }}</p>
        <h2 class="mt-1 text-xl font-bold tracking-tight text-slate-900">{{ description }}</h2>
      </div>
      <span class="rounded-full px-3 py-1 text-xs font-bold" :class="accent === 'lime' ? 'bg-lime/40 text-navy' : 'bg-coral/10 text-coral'">{{ data?.waiting_count ?? 0 }} waiting</span>
    </div>

    <div class="mt-6 flex min-h-64 flex-col items-center justify-center rounded-3xl p-6 text-center" :class="accent === 'lime' ? 'bg-lime/25' : 'bg-coral/10'">
      <p class="eyebrow text-slate-500">Now serving</p>
      <template v-if="data?.now_serving">
        <p class="mt-2 text-7xl font-bold tracking-tight" :class="accent === 'lime' ? 'text-navy' : 'text-coral'">{{ data.now_serving.token_number }}</p>
        <p class="mt-2 text-sm font-semibold text-slate-700">{{ data.now_serving.window_number || 'Service window' }}</p>
        <p class="mt-1 text-xs text-slate-500">{{ action }}</p>
      </template>
      <template v-else>
        <p class="mt-2 text-6xl font-bold text-slate-300">—</p>
        <p class="mt-2 text-sm text-slate-500">Please wait for the next call</p>
      </template>
    </div>

    <div class="mt-5">
      <div class="flex items-center justify-between">
        <p class="eyebrow text-slate-500">Up next</p>
        <span class="text-xs font-semibold text-slate-400">{{ data?.upcoming.length ?? 0 }} tickets</span>
      </div>
      <div v-if="data?.upcoming.length" class="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-3">
        <div v-for="token in data.upcoming" :key="token.id" class="rounded-2xl bg-slate-50 px-3 py-3 text-center ring-1 ring-slate-900/5">
          <p class="font-mono text-lg font-bold text-navy">{{ token.token_number }}</p>
          <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-500">Waiting</p>
        </div>
      </div>
      <p v-else class="mt-3 rounded-2xl bg-slate-50 px-4 py-4 text-center text-sm text-slate-500">No tickets waiting.</p>
    </div>
  </section>
</template>

<script setup lang="ts">
import type { QueueToken } from '../../types';

defineProps<{
  label: string;
  description: string;
  data?: { now_serving: QueueToken | null; upcoming: QueueToken[]; waiting_count: number };
  accent: 'lime' | 'coral';
  action: string;
}>();
</script>
