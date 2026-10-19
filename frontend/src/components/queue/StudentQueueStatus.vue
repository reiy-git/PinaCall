<template>
  <div class="rounded-2xl border border-emerald-200 bg-emerald-50/50 p-5 shadow-sm">
    <h3 class="text-sm font-bold uppercase tracking-wider text-emerald-700 mb-4">Queue Status</h3>

    <!-- Active Token -->
    <div v-if="activeToken" class="rounded-xl border border-emerald-300/40 bg-white p-4 shadow-sm">
      <div class="flex items-center justify-between flex-wrap gap-3">
        <div class="flex items-center gap-4">
          <div class="rounded-xl bg-emerald-100 px-4 py-2.5">
            <p class="text-2xl font-black text-emerald-800">{{ activeToken.token_number }}</p>
          </div>
          <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-emerald-600">
              {{ activeToken.service_stage === 'cashier' ? 'Cashier Queue' : 'Registrar Queue' }}
            </p>
            <p class="text-sm font-bold text-slate-900 mt-0.5">
              <template v-if="activeToken.status === 'waiting'">
                <span v-if="position !== null" class="text-amber-600">
                  {{ position === 1 ? "You're next!" : `${position} in line` }}
                </span>
                <span v-else class="text-slate-500">Waiting to be called</span>
              </template>
              <template v-else-if="activeToken.status === 'serving'">
                <span class="text-emerald-600 animate-pulse">Now being served at {{ activeToken.window_number }}</span>
              </template>
              <template v-else-if="activeToken.status === 'hold'">
                <span class="text-amber-600">Document being processed — please wait</span>
              </template>
              <template v-else-if="activeToken.status === 'ready'">
                <span class="text-teal-600">Ready for pickup — proceed to Registrar</span>
              </template>
            </p>
          </div>
        </div>
        <span
          class="rounded-full px-3 py-1 text-[10px] font-extrabold uppercase tracking-wider"
          :class="{
            'bg-amber-100 text-amber-700': activeToken.status === 'waiting',
            'bg-emerald-100 text-emerald-700': activeToken.status === 'serving',
            'bg-orange-100 text-orange-700': activeToken.status === 'hold',
            'bg-teal-100 text-teal-700': activeToken.status === 'ready',
          }"
        >
          {{ activeToken.status.replace('_', ' ') }}
        </span>
      </div>

      <div v-if="activeToken.document_request" class="mt-3 pt-3 border-t border-emerald-100 text-xs text-slate-500">
        <span class="font-semibold text-slate-600">{{ activeToken.document_request.document_type || activeToken.document_request.type }}</span>
        · {{ activeToken.document_request.copies }} {{ activeToken.document_request.copies === 1 ? 'copy' : 'copies' }}
        · ₱{{ (activeToken.document_request.fee || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}
      </div>
    </div>

    <!-- No active token but has history -->
    <div v-else-if="hasQueueHistory" class="text-sm text-slate-500">
      No active queue token. Your completed tokens for today are listed below.
    </div>
    <div v-else class="rounded-xl border border-dashed border-emerald-200 bg-white p-4 text-sm text-slate-700">
      <p class="font-bold text-slate-900">How to join the queue</p>
      <ol class="mt-2 list-decimal space-y-1 pl-5">
        <li>At the service counter, take an available reusable token for the step you need: Cashier for payment, or Registrar for pickup.</li>
        <li>Scan the QR printed on that token and sign in here. Choose your document request and tap <strong>Join queue</strong>.</li>
        <li>Keep the physical token and your printed queue number; bring both when your number is called.</li>
      </ol>
      <p class="mt-2 text-xs text-slate-500">A Registrar token is for requests marked Ready for Pick-up. A payment-required request starts at the Cashier.</p>
    </div>

    <!-- Today's token history -->
    <div v-if="completedTokens.length" class="mt-4">
      <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-2">Today's History</p>
      <div class="space-y-1.5">
        <div v-for="token in completedTokens" :key="token.id" class="flex items-center justify-between text-xs bg-white rounded-lg px-3 py-2 border border-slate-100">
          <div class="flex items-center gap-2">
            <span class="font-bold text-slate-700">{{ token.token_number }}</span>
            <span class="text-slate-400 capitalize">{{ token.service_stage }}</span>
          </div>
          <span
            class="rounded-full px-2 py-0.5 font-bold uppercase tracking-wider"
            :class="{
              'bg-emerald-100 text-emerald-700': token.status === 'completed',
              'bg-red-100 text-red-600': token.status === 'no_show',
              'bg-slate-100 text-slate-500': token.status === 'skipped',
            }"
          >
            {{ token.status.replace('_', ' ') }}
          </span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { portalApi, authState } from '../../services/api';
import type { QueueToken, QueueStatusResponse } from '../../types';

const statusData = ref<QueueStatusResponse | null>(null);

let refreshInterval: ReturnType<typeof setInterval>;

const activeToken = computed(() => statusData.value?.active_token || null);
const position = computed(() => statusData.value?.position_in_queue || null);
const completedTokens = computed<QueueToken[]>(() =>
  (statusData.value?.tokens || []).filter(t => ['completed', 'no_show', 'skipped'].includes(t.status))
);
const hasQueueHistory = computed(() => completedTokens.value.length > 0);

async function loadStatus() {
  if (!authState.user?.student) return;
  try {
    statusData.value = await portalApi.getQueueStatus();
    if (!statusData.value.active_token) clearInterval(refreshInterval);
  } catch {
    // retry on the next scheduled interval
  }
}

onMounted(() => {
  void loadStatus();
  refreshInterval = setInterval(loadStatus, 10000);
});

onUnmounted(() => {
  clearInterval(refreshInterval);
});
</script>
