<template>
  <div class="space-y-5">
    <div class="flex gap-2 border-b border-slate-200 pb-2">
      <button
        @click="activePane = 'queue'"
        class="rounded-xl px-4 py-2 text-xs font-bold transition"
        :class="activePane === 'queue' ? 'bg-emerald-800 text-white' : 'bg-white text-slate-600 hover:bg-slate-100'"
      >
        Live Queue
      </button>
      <button
        @click="openPhysicalTokens"
        class="rounded-xl px-4 py-2 text-xs font-bold transition"
        :class="activePane === 'tokens' ? 'bg-emerald-800 text-white' : 'bg-white text-slate-600 hover:bg-slate-100'"
      >
        Token Status · Print QR
      </button>
    </div>

    <div v-if="activePane === 'queue'" class="space-y-5">
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
          <button
            @click="openPhysicalTokens"
            class="rounded-lg border border-emerald-300/40 bg-emerald-500/15 px-4 py-2 text-xs font-bold text-emerald-100 hover:bg-emerald-500/25 transition"
          >
            Print Token QR
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
          <div class="mb-3 rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900">
            <p class="font-semibold">Physical token required</p>
            <p class="mt-1">Expected reference: <span class="font-mono font-bold">{{ currentlyServing.physical_queue_token?.reference || 'Unavailable' }}</span>. Collect and compare the token before completing service.</p>
          </div>
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
              :disabled="actionLoading || currentlyServing.document_request?.payment_status !== 'paid' || !['ready_for_pickup', 'released'].includes(currentlyServing.document_request?.status || '')"
              class="w-full rounded-xl bg-emerald-700 px-6 py-3 text-sm font-black text-white uppercase tracking-wider hover:bg-emerald-600 disabled:opacity-50 transition shadow-lg"
            >
              {{ currentlyServing.document_request?.status === 'released' ? 'Complete Queue Ticket' : 'Complete Transaction & Release Document' }}
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
        On Hold — Processing · Registrar Follow-up ({{ queueData.on_hold.length }})
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
          <span class="max-w-56 text-right text-xs text-amber-900">
            Registrar: once prepared, mark <strong>Ready for Pick-up</strong> in Document Request Fulfillment.
          </span>
        </div>
      </div>
    </div>
    </div>

    <section v-else class="space-y-4" aria-label="Physical token management">
      <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white p-4">
        <div>
          <h3 class="text-lg font-black text-slate-900">{{ stage === 'cashier' ? 'Cashier' : 'Registrar' }} Physical Tokens</h3>
          <p class="mt-1 text-xs text-slate-500">Print or reprint the same QR, review linked usage history, and manage returned or missing tokens.</p>
        </div>
        <button
          @click="refreshSelectedTokenManagement"
          :disabled="tokenLoading"
          class="rounded-xl border border-slate-300 px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 disabled:opacity-50"
        >
          {{ tokenLoading ? 'Loading…' : 'Refresh tokens' }}
        </button>
      </div>

      <div class="grid gap-4 xl:grid-cols-[minmax(260px,0.8fr)_minmax(0,1.5fr)]">
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
          <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
            <h4 class="text-sm font-bold text-slate-900">Token inventory</h4>
            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-600">{{ physicalTokens.length }}</span>
          </div>
          <div v-if="!physicalTokens.length && !tokenLoading" class="p-5 text-sm text-slate-500">
            No physical tokens are seeded for this service stage.
          </div>
          <div v-else class="max-h-[620px] divide-y divide-slate-100 overflow-y-auto">
            <button
              v-for="token in physicalTokens"
              :key="token.id"
              @click="selectedTokenId = token.id"
              class="flex w-full items-center justify-between gap-3 px-4 py-3 text-left transition hover:bg-slate-50"
              :class="selectedTokenId === token.id ? 'bg-emerald-50 ring-1 ring-inset ring-emerald-200' : ''"
            >
              <span>
                <span class="block text-sm font-black text-slate-900">{{ token.reference }}</span>
                <span class="mt-0.5 block max-w-52 truncate text-xs text-slate-500">
                  {{ token.assigned_queue_ticket?.token_number || token.assigned_student?.name || 'Not currently assigned' }}
                </span>
              </span>
              <span
                class="shrink-0 rounded-full px-2 py-1 text-[10px] font-bold uppercase"
                :class="tokenStatusClass(token.status)"
              >{{ token.status }}</span>
            </button>
          </div>
        </div>

        <div v-if="selectedToken" class="space-y-4">
          <div class="rounded-2xl border border-slate-200 bg-white p-5">
            <div class="flex flex-wrap items-start justify-between gap-3">
              <div>
                <p class="text-xs font-bold uppercase tracking-wider text-emerald-800">Selected physical token</p>
                <h4 class="mt-1 text-2xl font-black text-slate-900">{{ selectedToken.reference }}</h4>
                <p class="mt-1 text-xs text-slate-500">For {{ stage }} service · current status: <strong class="uppercase">{{ selectedToken.status }}</strong></p>
              </div>
              <div class="flex flex-wrap gap-2">
                <button
                  @click="loadSelectedTokenQr"
                  :disabled="actionLoading"
                  class="rounded-xl bg-emerald-700 px-3 py-2 text-xs font-bold text-white hover:bg-emerald-600 disabled:opacity-50"
                >
                  Preview QR
                </button>
                <button
                  @click="printSelectedToken"
                  :disabled="actionLoading"
                  class="rounded-xl border border-emerald-300 px-3 py-2 text-xs font-bold text-emerald-800 hover:bg-emerald-50 disabled:opacity-50"
                >
                  Print / Reprint QR
                </button>
                <button
                  v-if="selectedToken.status !== 'missing'"
                  @click="markSelectedTokenMissing"
                  :disabled="actionLoading"
                  class="rounded-xl border border-red-200 px-3 py-2 text-xs font-bold text-red-700 hover:bg-red-50 disabled:opacity-50"
                >
                  Mark Missing
                </button>
                <button
                  v-if="selectedToken.status !== 'available'"
                  @click="resetSelectedToken"
                  :disabled="actionLoading || hasActiveTicket(selectedToken)"
                  :title="hasActiveTicket(selectedToken) ? 'Resolve the linked active queue ticket before resetting this token.' : 'Reset after physically verifying that the token has been returned.'"
                  class="rounded-xl border border-amber-300 px-3 py-2 text-xs font-bold text-amber-900 hover:bg-amber-50 disabled:cursor-not-allowed disabled:opacity-40"
                >
                  Verify Return & Reset
                </button>
              </div>
            </div>

            <div v-if="qrPreview?.tokenId === selectedToken.id" class="mt-4 flex flex-col items-center rounded-xl border border-slate-200 bg-white p-4">
              <img :src="qrPreview.dataUrl" :alt="`QR code for ${selectedToken.reference}`" class="h-64 w-64" />
              <p class="mt-2 text-xs font-bold text-slate-700">{{ selectedToken.reference }} · {{ stage }} check-in</p>
              <p class="mt-1 max-w-full break-all text-center text-[10px] text-slate-500">Scan opens: {{ qrPreview.scanAddress }}</p>
            </div>

            <div class="mt-4 grid gap-3 rounded-xl bg-slate-50 p-4 text-sm sm:grid-cols-2">
              <div>
                <span class="text-xs font-semibold text-slate-500">Assigned student</span>
                <p class="font-bold text-slate-900">{{ selectedToken.assigned_student?.user?.name || selectedToken.assigned_student?.name || '—' }}</p>
                <p class="text-xs text-slate-500">{{ selectedToken.assigned_student?.student_number || 'No current assignment' }}</p>
              </div>
              <div>
                <span class="text-xs font-semibold text-slate-500">Queue ticket / request</span>
                <p class="font-bold text-slate-900">
                  {{ selectedToken.assigned_queue_ticket?.token_number || '—' }}
                  <span v-if="selectedToken.assigned_queue_ticket?.document_request">· Request #{{ selectedToken.assigned_queue_ticket.document_request.id }}</span>
                </p>
                <p class="text-xs text-slate-500">{{ selectedToken.assigned_queue_ticket?.document_request?.document_type || selectedToken.assigned_queue_ticket?.document_request?.type || 'No current request' }}</p>
              </div>
            </div>
            <p v-if="hasActiveTicket(selectedToken)" class="mt-3 rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-900">
              This token is linked to a live ticket. Resolve that queue ticket before resetting it.
            </p>
            <p v-else-if="selectedToken.last_reset_at" class="mt-3 text-xs text-slate-500">
              Last verified reset: {{ formatDate(selectedToken.last_reset_at) }} by {{ selectedToken.last_reset_by_staff?.name || 'staff' }}
            </p>
          </div>

          <div class="grid gap-4 lg:grid-cols-2">
            <div class="rounded-2xl border border-slate-200 bg-white p-4">
              <h4 class="mb-3 text-sm font-bold text-slate-900">Token history</h4>
              <p v-if="historyLoading" class="text-xs text-slate-500">Loading token history…</p>
              <div v-else-if="selectedTokenDetails?.events?.length" class="max-h-72 space-y-3 overflow-y-auto">
                <article v-for="event in selectedTokenDetails.events" :key="event.id" class="border-l-2 border-emerald-300 pl-3">
                  <p class="text-xs font-bold uppercase text-slate-800">{{ event.event_type.replaceAll('_', ' ') }} <span class="font-normal normal-case text-slate-500">· {{ formatDate(event.created_at) }}</span></p>
                  <p v-if="event.student" class="mt-0.5 text-xs text-slate-600">Student: {{ event.student.user?.name || event.student.name }} ({{ event.student.student_number }})</p>
                  <p v-if="event.staff" class="text-xs text-slate-600">Staff: {{ event.staff.name }}</p>
                  <p v-if="event.queue_ticket" class="text-xs text-slate-600">
                    Ticket {{ event.queue_ticket.token_number }} · Request #{{ event.queue_ticket.document_request_id }}
                    {{ event.queue_ticket.document_request?.document_type || event.queue_ticket.document_request?.type || '' }}
                  </p>
                  <p v-if="event.notes" class="text-xs text-slate-500">{{ event.notes }}</p>
                </article>
              </div>
              <p v-else class="text-xs text-slate-500">No staff actions have been recorded for this token yet.</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4">
              <h4 class="mb-3 text-sm font-bold text-slate-900">Scan & check-in attempts</h4>
              <p v-if="historyLoading" class="text-xs text-slate-500">Loading attempts…</p>
              <div v-else-if="selectedTokenDetails?.attempts?.length" class="max-h-72 space-y-3 overflow-y-auto">
                <article v-for="attempt in selectedTokenDetails.attempts" :key="attempt.id" class="border-l-2 border-slate-300 pl-3">
                  <p class="text-xs font-bold uppercase text-slate-800">{{ attempt.result }} <span class="font-normal normal-case text-slate-500">· {{ formatDate(attempt.created_at) }}</span></p>
                  <p class="mt-0.5 text-xs text-slate-600">Student: {{ attempt.student?.user?.name || attempt.student?.name || 'Unknown' }} ({{ attempt.student?.student_number || '—' }})</p>
                  <p v-if="attempt.queue_ticket" class="text-xs text-slate-600">
                    Ticket {{ attempt.queue_ticket.token_number }} · Request #{{ attempt.queue_ticket.document_request_id }}
                    {{ attempt.queue_ticket.document_request?.document_type || attempt.queue_ticket.document_request?.type || '' }}
                  </p>
                  <p v-if="attempt.reason" class="text-xs text-slate-500">Reason: {{ attempt.reason.replaceAll('_', ' ') }}</p>
                </article>
              </div>
              <p v-else class="text-xs text-slate-500">No scans or check-in attempts have been recorded for this token.</p>
            </div>
          </div>
        </div>
        <div v-else class="flex min-h-64 items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-white p-5 text-sm text-slate-500">
          Select a token to inspect its QR, assignment, and history.
        </div>
      </div>
    </section>

    <div v-if="printCard" id="physical-token-print-area">
      <p class="text-center text-xs font-bold uppercase tracking-[0.2em] text-slate-600">PINACALL · {{ printCard.stage }} token</p>
      <h1 class="mt-3 text-center text-3xl font-black text-slate-900">{{ printCard.reference }}</h1>
      <img :src="printCard.dataUrl" :alt="`QR code for ${printCard.reference}`" class="mx-auto mt-5 h-80 w-80" />
      <p class="mt-4 text-center text-sm font-semibold text-slate-700">Scan to join the {{ printCard.stage }} queue</p>
      <p class="mt-1 text-center text-xs text-slate-500">Keep this reusable physical token at its assigned service counter.</p>
      <p class="mt-2 break-all text-center text-[10px] text-slate-500">Scan opens: {{ printCard.scanAddress }}</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import QRCode from 'qrcode';
import { portalApi, showToast } from '../../services/api';
import type { PhysicalQueueTokenRecord, StaffQueueData, QueueToken, ServiceStage } from '../../types';

const props = defineProps<{
  stage: ServiceStage;
}>();

const publicAppUrl = import.meta.env.VITE_PUBLIC_APP_URL?.trim();
const windowNumber = ref('Window 1');
const queueData = ref<StaffQueueData | null>(null);
const actionLoading = ref(false);
const tokenLoading = ref(false);
const historyLoading = ref(false);
const activePane = ref<'queue' | 'tokens'>('tokens');
const physicalTokens = ref<PhysicalQueueTokenRecord[]>([]);
const selectedTokenId = ref<number | null>(null);
const selectedTokenDetails = ref<PhysicalQueueTokenRecord | null>(null);
const qrPreview = ref<{ tokenId: number; reference: string; stage: ServiceStage; scanUrl: string; scanAddress: string; dataUrl: string } | null>(null);
const printCard = ref<{ reference: string; stage: ServiceStage; scanUrl: string; scanAddress: string; dataUrl: string } | null>(null);

let refreshInterval: ReturnType<typeof setInterval>;

const selectedToken = computed(() => physicalTokens.value.find((token) => token.id === selectedTokenId.value) || null);

const currentlyServing = computed<QueueToken | null>(() => {
  if (!queueData.value) return null;
  return queueData.value.serving.find((token) => token.window_number === windowNumber.value) || null;
});

async function loadQueue() {
  try {
    const res = await portalApi.getStaffQueue(props.stage);
    queueData.value = res;
  } catch {
    // retry on next interval
  }
}

async function openPhysicalTokens() {
  activePane.value = 'tokens';
  await loadPhysicalTokens();
}

async function loadPhysicalTokens() {
  tokenLoading.value = true;
  try {
    const response = await portalApi.getPhysicalTokens(props.stage);
    physicalTokens.value = response.tokens;
    if (!physicalTokens.value.some((token) => token.id === selectedTokenId.value)) {
      selectedTokenId.value = physicalTokens.value[0]?.id ?? null;
    }
  } catch (err: any) {
    showToast('error', 'Token inventory unavailable', err.message || 'Could not load physical tokens.');
  } finally {
    tokenLoading.value = false;
  }
}

async function loadPhysicalTokenHistory(tokenId: number) {
  historyLoading.value = true;
  try {
    const response = await portalApi.getPhysicalTokenHistory(tokenId);
    if (selectedTokenId.value === tokenId) selectedTokenDetails.value = response.token;
  } catch (err: any) {
    showToast('error', 'Token history unavailable', err.message || 'Could not load token history.');
  } finally {
    if (selectedTokenId.value === tokenId) historyLoading.value = false;
  }
}

async function loadSelectedTokenQr() {
  if (!selectedToken.value) return;
  actionLoading.value = true;
  try {
    const qr = await portalApi.getPhysicalTokenQr(selectedToken.value.id);
    const appOrigin = publicAppUrl || window.location.origin;
    const checkInUrl = new URL('/queue/check-in', appOrigin);
    if (!['http:', 'https:'].includes(checkInUrl.protocol)) {
      throw new Error('The configured public app URL must use HTTP or HTTPS.');
    }
    if (checkInUrl.origin !== appOrigin.replace(/\/+$/, '')) {
      throw new Error('VITE_PUBLIC_APP_URL must be an app origin only, without a path, query, or fragment.');
    }
    if (import.meta.env.PROD && checkInUrl.protocol !== 'https:') {
      throw new Error('Production queue QR codes require an HTTPS VITE_PUBLIC_APP_URL.');
    }
    if (
      import.meta.env.PROD
      && (/(\.|^)example\.com$/.test(checkInUrl.hostname) || /\.(example|invalid|test)$/.test(checkInUrl.hostname))
    ) {
      throw new Error('Replace the placeholder VITE_PUBLIC_APP_URL with the real production app origin before building.');
    }
    if (['localhost', '127.0.0.1', '::1'].includes(checkInUrl.hostname)) {
      throw new Error('This address only works on the cashier computer. Configure VITE_PUBLIC_APP_URL to the app address students can reach, then restart the frontend.');
    }
    checkInUrl.searchParams.set('queue_token', qr.qr_secret);
    const scanUrl = checkInUrl.toString();
    const scanAddress = `${checkInUrl.origin}${checkInUrl.pathname}`;
    const dataUrl = await QRCode.toDataURL(scanUrl, { errorCorrectionLevel: 'M', margin: 4, width: 768 });
    qrPreview.value = { tokenId: selectedToken.value.id, reference: qr.reference, stage: qr.service_stage, scanUrl, scanAddress, dataUrl };
  } catch (err: any) {
    showToast('error', 'QR unavailable', err.message || 'Could not load this token QR.');
  } finally {
    actionLoading.value = false;
  }
}

async function printSelectedToken() {
  if (!selectedToken.value) return;
  if (qrPreview.value?.tokenId !== selectedToken.value.id) await loadSelectedTokenQr();
  if (qrPreview.value?.tokenId !== selectedToken.value.id) return;
  printCard.value = {
    reference: qrPreview.value.reference,
    stage: qrPreview.value.stage,
    scanUrl: qrPreview.value.scanUrl,
    scanAddress: qrPreview.value.scanAddress,
    dataUrl: qrPreview.value.dataUrl,
  };
  window.setTimeout(() => {
    window.print();
    printCard.value = null;
  }, 50);
}

async function markSelectedTokenMissing() {
  if (!selectedToken.value || !window.confirm(`Mark ${selectedToken.value.reference} missing? It will no longer be usable until staff verify its return.`)) return;
  actionLoading.value = true;
  try {
    const response = await portalApi.markPhysicalTokenMissing(selectedToken.value.id);
    showToast('warning', 'Token marked missing', response.message);
    await refreshTokenManagement(selectedToken.value.id);
  } catch (err: any) {
    showToast('error', 'Could not mark token missing', err.message || 'Please try again.');
  } finally {
    actionLoading.value = false;
  }
}

async function resetSelectedToken() {
  if (!selectedToken.value || hasActiveTicket(selectedToken.value)) return;
  if (!window.confirm(`Confirm that ${selectedToken.value.reference} is physically back at the counter and reset it for reuse?`)) return;
  actionLoading.value = true;
  try {
    const response = await portalApi.resetPhysicalToken(selectedToken.value.id);
    showToast('success', 'Token reset', response.message);
    await refreshTokenManagement(selectedToken.value.id);
  } catch (err: any) {
    showToast('error', 'Could not reset token', err.message || 'Please try again.');
  } finally {
    actionLoading.value = false;
  }
}

async function refreshTokenManagement(tokenId: number) {
  await loadPhysicalTokens();
  if (selectedTokenId.value === tokenId) await loadPhysicalTokenHistory(tokenId);
}

async function refreshSelectedTokenManagement() {
  const tokenId = selectedTokenId.value;
  await loadPhysicalTokens();
  if (tokenId !== null && selectedTokenId.value === tokenId) await loadPhysicalTokenHistory(tokenId);
}

function hasActiveTicket(token: PhysicalQueueTokenRecord): boolean {
  return ['waiting', 'serving'].includes(token.assigned_queue_ticket?.status || '');
}

function tokenStatusClass(status: PhysicalQueueTokenRecord['status']): string {
  if (status === 'available') return 'bg-emerald-100 text-emerald-800';
  if (status === 'missing' || status === 'disabled' || status === 'locked') return 'bg-red-100 text-red-800';
  return 'bg-amber-100 text-amber-900';
}

function formatDate(value: string): string {
  return new Date(value).toLocaleString();
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
  const physicalTokenReference = currentlyServing.value.physical_queue_token?.reference;
  if (!physicalTokenReference) {
    showToast('error', 'Physical Token Required', 'The matching physical token must be returned before service can be completed.');
    return;
  }
  actionLoading.value = true;
  try {
    const res = await portalApi.completeTransaction(currentlyServing.value.id, action, physicalTokenReference);
    showToast('success', 'Transaction Complete', res.message);
    await loadQueue();
  } catch (err: any) {
    showToast('error', 'Error', err.message);
  } finally {
    actionLoading.value = false;
  }
}

watch(() => props.stage, () => { void loadQueue(); });
watch(selectedTokenId, (tokenId) => {
  selectedTokenDetails.value = null;
  qrPreview.value = null;
  if (tokenId !== null) void loadPhysicalTokenHistory(tokenId);
});
watch(() => props.stage, () => {
  physicalTokens.value = [];
  selectedTokenId.value = null;
  selectedTokenDetails.value = null;
  qrPreview.value = null;
  if (activePane.value === 'tokens') void loadPhysicalTokens();
});

onMounted(() => {
  void loadQueue();
  void loadPhysicalTokens();
  refreshInterval = setInterval(loadQueue, 8000);
});

onUnmounted(() => {
  clearInterval(refreshInterval);
});
</script>

<style scoped>
#physical-token-print-area {
  display: none;
}

@media print {
  body * {
    visibility: hidden !important;
  }

  #physical-token-print-area,
  #physical-token-print-area * {
    visibility: visible !important;
  }

  #physical-token-print-area {
    display: block !important;
    position: fixed;
    inset: 0;
    width: 100%;
    padding: 2rem;
    background: white;
    color: black;
  }
}
</style>
