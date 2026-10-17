<template>
  <div class="space-y-5">
    <!-- Queue Control Header -->
    <div class="rounded-3xl bg-white p-5 text-slate-900 shadow-xl shadow-slate-900/5 ring-1 ring-slate-900/5 sm:p-6">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <p class="eyebrow text-coral">Active queue control</p>
          <h3 class="mt-1 text-xl font-bold tracking-tight text-navy">{{ stage === 'cashier' ? 'Cashier' : 'Registrar' }} service window</h3>
        </div>
        <div class="flex items-center gap-2">
          <label class="text-xs font-semibold text-slate-500">Window:</label>
          <select
            v-model="windowNumber"
            class="rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-800 outline-none"
          >
            <option value="Window 1">Window 1</option>
            <option value="Window 2">Window 2</option>
            <option value="Window 3">Window 3</option>
          </select>
          <button
            @click="loadQueue"
            class="rounded-2xl bg-slate-100 px-4 py-2 text-xs font-bold text-navy transition hover:bg-slate-200"
          >
            Refresh
          </button>
        </div>
      </div>

      <!-- Currently Serving -->
      <div class="mt-5 rounded-3xl bg-navy p-4 text-white">
        <template v-if="currentlyServing">
          <div class="flex items-center justify-between flex-wrap gap-3">
            <div>
              <p class="eyebrow text-lime">Current ticket</p>
              <p class="mt-1 text-3xl font-bold text-white">{{ currentlyServing.token_number }}</p>
              <p class="mt-1 text-sm text-slate-300">
                {{ currentlyServing.student?.user?.name || currentlyServing.student?.name || 'Unknown' }}
                <span class="text-slate-500 ml-1">({{ currentlyServing.student?.student_number }})</span>
              </p>
            </div>
            <div class="flex flex-wrap gap-2">
              <button
                @click="handleCallNext"
                class="rounded-lg bg-emerald-600 px-4 py-2.5 text-xs font-bold text-white hover:bg-emerald-500 transition shadow"
              >
                Call Next Number
              </button>
              <button
                @click="handleRecall(currentlyServing.id)"
                class="rounded-lg bg-amber-600 px-4 py-2.5 text-xs font-bold text-white hover:bg-amber-500 transition"
              >
                Re-call Display
              </button>
              <button
                @click="handleNoShow(currentlyServing.id)"
                class="rounded-lg bg-red-700 px-4 py-2.5 text-xs font-bold text-white hover:bg-red-600 transition"
              >
                Mark No-Show / Skip
              </button>
            </div>
          </div>
        </template>
        <template v-else>
          <div class="flex items-center justify-between">
            <p class="text-sm text-slate-300">No student currently being served.</p>
            <button
              @click="handleCallNext"
              :disabled="!queueData || queueData.waiting.length === 0"
              class="rounded-lg bg-emerald-600 px-5 py-2.5 text-xs font-bold text-white hover:bg-emerald-500 disabled:opacity-40 disabled:cursor-not-allowed transition shadow"
            >
              Call Next Number
            </button>
          </div>
        </template>
      </div>

      <!-- Stats Row -->
      <div v-if="queueData" class="grid grid-cols-4 gap-3 mt-4">
        <div class="rounded-lg bg-black/20 p-2.5 text-center border border-emerald-800/20">
          <p class="text-[10px] text-emerald-300/60 font-bold uppercase tracking-wider">Waiting</p>
          <p class="text-xl font-black text-amber-300">{{ queueData.stats.waiting_count }}</p>
        </div>
        <div class="rounded-lg bg-black/20 p-2.5 text-center border border-emerald-800/20">
          <p class="text-[10px] text-emerald-300/60 font-bold uppercase tracking-wider">Served</p>
          <p class="text-xl font-black text-emerald-300">{{ queueData.stats.served }}</p>
        </div>
        <div class="rounded-lg bg-black/20 p-2.5 text-center border border-emerald-800/20">
          <p class="text-[10px] text-emerald-300/60 font-bold uppercase tracking-wider">No-Shows</p>
          <p class="text-xl font-black text-red-400">{{ queueData.stats.no_shows }}</p>
        </div>
        <div class="rounded-lg bg-black/20 p-2.5 text-center border border-emerald-800/20">
          <p class="text-[10px] text-emerald-300/60 font-bold uppercase tracking-wider">Total Today</p>
          <p class="text-xl font-black text-white">{{ queueData.stats.total_today }}</p>
        </div>
      </div>
    </div>

    <!-- Student Transaction Verification -->
    <div v-if="currentlyServing" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
      <h4 class="text-sm font-bold uppercase tracking-wider text-emerald-700 mb-4">Student Transaction Verification</h4>

      <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-3">
        <div class="grid grid-cols-2 gap-3 text-sm">
          <div>
            <span class="text-slate-500 text-xs font-semibold">Student Name</span>
            <p class="font-bold text-slate-900">{{ currentlyServing.student?.user?.name || currentlyServing.student?.name || '—' }}</p>
          </div>
          <div>
            <span class="text-slate-500 text-xs font-semibold">Student ID</span>
            <p class="font-bold text-slate-900">{{ currentlyServing.student?.student_number || '—' }}</p>
          </div>
          <div>
            <span class="text-slate-500 text-xs font-semibold">Document Type</span>
            <p class="font-bold text-slate-900">{{ currentlyServing.document_request?.document_type || currentlyServing.document_request?.type || '—' }}</p>
          </div>
          <div>
            <span class="text-slate-500 text-xs font-semibold">Fee</span>
            <p class="font-bold text-slate-900">₱{{ (currentlyServing.document_request?.fee || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}</p>
          </div>
        </div>

        <!-- Checks -->
        <div class="border-t border-slate-200 pt-3 space-y-2">
          <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Checks</p>
          <div class="flex items-center gap-2 text-sm">
            <span :class="currentlyServing.document_request?.payment_status === 'paid' ? 'text-emerald-600' : 'text-amber-600'" class="font-bold">
              {{ currentlyServing.document_request?.payment_status === 'paid' ? '✔' : '✕' }}
            </span>
            <span class="text-slate-700">Payment Status:</span>
            <span :class="currentlyServing.document_request?.payment_status === 'paid' ? 'text-emerald-600 font-bold' : 'text-amber-600 font-bold'" class="uppercase">
              {{ currentlyServing.document_request?.payment_status || 'unpaid' }}
            </span>
          </div>
          <div class="flex items-center gap-2 text-sm">
            <span :class="currentlyServing.document_request?.status === 'ready_for_pickup' ? 'text-emerald-600' : 'text-slate-400'" class="font-bold">
              {{ currentlyServing.document_request?.status === 'ready_for_pickup' ? '✔' : '○' }}
            </span>
            <span class="text-slate-700">Processing Status:</span>
            <span class="font-bold uppercase" :class="{
              'text-emerald-600': currentlyServing.document_request?.status === 'ready_for_pickup',
              'text-amber-600': currentlyServing.document_request?.status === 'processing',
              'text-slate-500': !['ready_for_pickup', 'processing'].includes(currentlyServing.document_request?.status || ''),
            }">
              {{ (currentlyServing.document_request?.status || 'pending').replace(/_/g, ' ') }}
            </span>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="border-t border-slate-200 pt-3">
          <template v-if="stage === 'cashier'">
            <button
              @click="handleComplete('pay_and_process')"
              :disabled="actionLoading"
              class="w-full rounded-xl bg-emerald-700 px-6 py-3 text-sm font-black text-white uppercase tracking-wider hover:bg-emerald-600 disabled:opacity-50 transition shadow-lg"
            >
              Confirm Payment & Send to Processing
            </button>
          </template>
          <template v-else>
            <button
              @click="handleComplete('release_document')"
              :disabled="actionLoading || currentlyServing.document_request?.payment_status !== 'paid' || currentlyServing.document_request?.status !== 'ready_for_pickup'"
              class="w-full rounded-xl bg-emerald-700 px-6 py-3 text-sm font-black text-white uppercase tracking-wider hover:bg-emerald-600 disabled:opacity-50 transition shadow-lg"
            >
              Complete Transaction & Release Document
            </button>
          </template>
        </div>
      </div>
    </div>

    <!-- Waiting Queue List -->
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
      <h4 class="text-sm font-bold uppercase tracking-wider text-emerald-700 mb-3">
        Waiting Queue ({{ queueData?.waiting.length ?? 0 }})
      </h4>
      <div v-if="queueData?.waiting.length" class="divide-y divide-slate-100">
        <div v-for="token in queueData.waiting" :key="token.id" class="flex items-center justify-between py-2.5">
          <div class="flex items-center gap-3">
            <span class="rounded-lg bg-emerald-100 px-2.5 py-1 text-sm font-black text-emerald-800">{{ token.token_number }}</span>
            <div class="text-sm">
              <p class="font-semibold text-slate-900">{{ token.student?.user?.name || token.student?.name }}</p>
              <p class="text-xs text-slate-500">{{ token.student?.student_number }}</p>
            </div>
          </div>
          <span class="text-xs text-slate-400">{{ token.document_request?.document_type || token.document_request?.type }}</span>
        </div>
      </div>
      <p v-else class="text-sm text-slate-400">No students currently waiting.</p>
    </div>

    <!-- On Hold (Cashier only — documents being processed) -->
    <div v-if="stage === 'cashier' && queueData?.on_hold.length" class="rounded-2xl border border-amber-200 bg-amber-50 p-5 shadow-sm">
      <h4 class="text-sm font-bold uppercase tracking-wider text-amber-700 mb-3">
        On Hold — Processing ({{ queueData.on_hold.length }})
      </h4>
      <div class="divide-y divide-amber-100">
        <div v-for="token in queueData.on_hold" :key="token.id" class="flex items-center justify-between py-2.5">
          <div class="flex items-center gap-3">
            <span class="rounded-lg bg-amber-200 px-2.5 py-1 text-sm font-black text-amber-900">{{ token.token_number }}</span>
            <div class="text-sm">
              <p class="font-semibold text-slate-900">{{ token.student?.user?.name || token.student?.name }}</p>
              <p class="text-xs text-slate-500">{{ token.document_request?.document_type }}</p>
            </div>
          </div>
          <button
            @click="handleReadyForPickup(token.id)"
            :disabled="actionLoading"
            class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-emerald-500 disabled:opacity-40 transition"
          >
            Mark Ready for Pickup
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { portalApi, showToast } from '../../services/api';
import type { StaffQueueData, QueueToken, ServiceStage } from '../../types';

const props = defineProps<{
  stage: ServiceStage;
}>();

const windowNumber = ref('Window 1');
const queueData = ref<StaffQueueData | null>(null);
const actionLoading = ref(false);

let refreshInterval: ReturnType<typeof setInterval>;

const currentlyServing = computed<QueueToken | null>(() => {
  if (!queueData.value) return null;
  return queueData.value.serving[0] || null;
});

async function loadQueue() {
  try {
    const res = await portalApi.getStaffQueue(props.stage);
    queueData.value = res;
  } catch {
    // retry on next interval
  }
}

async function handleCallNext() {
  actionLoading.value = true;
  try {
    const res = await portalApi.callNextInQueue(props.stage, windowNumber.value);
    showToast('success', 'Queue Updated', res.message);
    await loadQueue();
  } catch (err: any) {
    showToast('info', 'Queue', err.message || 'No more students waiting.');
  } finally {
    actionLoading.value = false;
  }
}

async function handleRecall(tokenId: number) {
  try {
    const res = await portalApi.recallDisplay(tokenId);
    showToast('info', 'Re-called', res.message);
  } catch (err: any) {
    showToast('error', 'Error', err.message);
  }
}

async function handleNoShow(tokenId: number) {
  actionLoading.value = true;
  try {
    const res = await portalApi.markNoShow(tokenId);
    showToast('warning', 'No-Show', res.message);
    await loadQueue();
  } catch (err: any) {
    showToast('error', 'Error', err.message);
  } finally {
    actionLoading.value = false;
  }
}

async function handleComplete(action: 'pay_and_process' | 'release_document') {
  if (!currentlyServing.value) return;
  actionLoading.value = true;
  try {
    const res = await portalApi.completeTransaction(currentlyServing.value.id, action);
    showToast('success', 'Transaction Complete', res.message);
    await loadQueue();
  } catch (err: any) {
    showToast('error', 'Error', err.message);
  } finally {
    actionLoading.value = false;
  }
}

async function handleReadyForPickup(tokenId: number) {
  actionLoading.value = true;
  try {
    const res = await portalApi.markReadyForPickup(tokenId);
    showToast('success', 'Ready for Pickup', res.message);
    await loadQueue();
  } catch (err: any) {
    showToast('error', 'Error', err.message);
  } finally {
    actionLoading.value = false;
  }
}

watch(() => props.stage, () => { void loadQueue(); });

onMounted(() => {
  void loadQueue();
  refreshInterval = setInterval(loadQueue, 8000);
});

onUnmounted(() => {
  clearInterval(refreshInterval);
});
</script>
