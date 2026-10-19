<template>
  <section class="mx-auto max-w-xl rounded-3xl border border-emerald-900/10 bg-white p-6 shadow-xl">
    <p class="text-xs font-bold uppercase tracking-wider text-emerald-800">PINACALL queue</p>
    <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-900">Confirm physical token check-in</h1>
    <p class="mt-2 text-sm text-slate-600">Select the request for the physical token you scanned. Keep the token and hand it to staff when your ticket is called.</p>

    <p v-if="error" class="mt-5 rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-800" role="alert">{{ error }}</p>
    <p v-else-if="!authState.user" class="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900">Sign in below with your student account. This check-in will stay ready, so you do not need to scan the token again.</p>

    <form v-else class="mt-5 space-y-4" @submit.prevent="activate">
      <label class="block text-sm font-semibold text-slate-800" for="queue-request">Eligible request</label>
      <select id="queue-request" v-model.number="requestId" required class="w-full rounded-xl border border-slate-300 bg-white px-3 py-3 text-sm">
        <option :value="0" disabled>Select a request</option>
        <option v-for="request in eligibleRequests" :key="request.id" :value="request.id">
          {{ request.document_type }} — {{ request.status === 'waiting_for_payment' ? 'Cashier payment' : 'Registrar pickup' }}
        </option>
        <option v-if="!loadingRequests && eligibleRequests.length === 0" :value="0" disabled>No requests are currently eligible</option>
      </select>
      <p v-if="loadingRequests" class="text-sm text-slate-500" role="status">Loading your eligible requests…</p>
      <button :disabled="loading || loadingRequests || !eligibleRequests.length || !requestId" class="w-full rounded-xl bg-emerald-700 px-5 py-3 text-sm font-bold text-white hover:bg-emerald-600 disabled:opacity-50">
        {{ loading ? 'Checking in…' : 'Join queue' }}
      </button>
    </form>

    <div v-if="ticket" class="printable-ticket mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 p-5 text-center">
      <p class="text-sm font-semibold text-emerald-800">You are checked in</p>
      <p class="mt-2 text-4xl font-black text-emerald-950">{{ ticket.token_number }}</p>
      <p class="mt-3 text-sm text-emerald-900">Keep the physical token with you. Check your portal dashboard for queue updates.</p>
      <button type="button" class="mt-4 rounded-xl bg-emerald-700 px-5 py-3 text-sm font-bold text-white hover:bg-emerald-600 print:hidden" @click="printTicket">
        Print queue number
      </button>
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue';
import { authState, portalApi } from '../../services/api';
import type { DocumentRequest, QueueToken } from '../../types';

const props = defineProps<{ qrSecret: string }>();
const requests = ref<DocumentRequest[]>([]);
const requestId = ref(0);
const loading = ref(false);
const loadingRequests = ref(false);
const error = ref('');
const ticket = ref<QueueToken | null>(null);

const eligibleRequests = computed(() => requests.value.filter((request) => (
  (request.status === 'waiting_for_payment' && request.payment_status === 'unpaid')
  || (request.status === 'ready_for_pickup' && request.payment_status === 'paid')
)));

async function loadEligibleRequests() {
  if (!authState.user) return;
  loadingRequests.value = true;
  try {
    requests.value = await portalApi.getDocuments();
  } catch (cause) {
    error.value = cause instanceof Error ? cause.message : 'Eligible requests could not be loaded.';
  } finally {
    loadingRequests.value = false;
  }
}

onMounted(async () => {
  if (!authState.user && authState.token) await portalApi.restoreSession();
});

watch(() => authState.user?.id, (userId) => {
  if (userId) void loadEligibleRequests();
}, { immediate: true });

async function activate() {
  loading.value = true;
  error.value = '';
  try {
    const result = await portalApi.activateQueueToken(props.qrSecret, requestId.value);
    ticket.value = result.token || null;
  } catch (cause) {
    error.value = cause instanceof Error ? cause.message : 'Queue check-in could not be completed.';
  } finally {
    loading.value = false;
  }
}

function printTicket() {
  window.print();
}
</script>

<style>
@media print {
  body * {
    visibility: hidden;
  }

  .printable-ticket,
  .printable-ticket * {
    visibility: visible;
  }

  .printable-ticket {
    position: absolute;
    inset: 0 auto auto 0;
    width: 100%;
    border: 0;
    background: white;
    padding: 2rem;
  }
}
</style>
