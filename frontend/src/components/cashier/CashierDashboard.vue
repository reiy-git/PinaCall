<template>
  <div class="space-y-6">
    <!-- Header Banner -->
    <div class="bg-gradient-to-r from-[#161f3d] via-[#222f5a] to-[#2a3b6f] rounded-3xl p-6 text-white shadow-lg border border-emerald-900/60">
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <div class="text-emerald-400 text-xs font-bold uppercase tracking-wider mb-1">
            Office of the University Cashier
          </div>
          <h2 class="text-2xl font-bold tracking-tight text-white">Student Financial Assessment & Clearance Desk</h2>
          <p class="text-xs text-slate-300 mt-1">Manage tuition assessments, sign off cashier clearance holds, and verify document request fee settlements.</p>
        </div>
        <button
          @click="loadData"
          class="px-4 py-2 bg-white/10 hover:bg-white/20 text-white font-semibold text-xs rounded-2xl border border-white/20 transition"
        >
          Refresh Cashier Queue
        </button>
      </div>

      <!-- Quick Metrics -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-6 pt-5 border-t border-emerald-800/30">
        <div class="bg-black/30 rounded-2xl p-3 border border-emerald-500/10">
          <span class="text-xs text-emerald-300/80 uppercase tracking-wider font-semibold">Outstanding Document Fees</span>
          <p class="text-2xl font-bold text-emerald-300 mt-0.5">{{ formatCurrency(outstandingDocumentFees) }}</p>
        </div>
        <div class="bg-black/30 rounded-2xl p-3 border border-emerald-500/10">
          <span class="text-xs text-emerald-300/80 uppercase tracking-wider font-semibold">Financial Clearances</span>
          <p class="text-2xl font-bold text-emerald-400 mt-0.5">{{ clearedCount }} Cleared</p>
        </div>
        <div class="bg-black/30 rounded-2xl p-3 border border-emerald-500/10">
          <span class="text-xs text-emerald-300/80 uppercase tracking-wider font-semibold">Pending Balances</span>
          <p class="text-2xl font-bold text-amber-300 mt-0.5">{{ pendingClearanceCount }} Students</p>
        </div>
        <div class="bg-black/30 rounded-2xl p-3 border border-emerald-500/10">
          <span class="text-xs text-emerald-300/80 uppercase tracking-wider font-semibold">Unpaid Doc Requests</span>
          <p class="text-2xl font-bold text-white mt-0.5">{{ unpaidDocsCount }} Requests</p>
        </div>
      </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="flex items-center gap-2 border-b border-slate-200 pb-1 overflow-x-auto">
      <button
        @click="activeTab = 'clearances'"
        class="px-4 py-2.5 rounded-2xl text-xs font-bold transition flex items-center gap-2 whitespace-nowrap"
        :class="activeTab === 'clearances' ? 'bg-[#2a3b6f] text-white shadow-md' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'"
      >
        <span>Financial Clearance Ledger</span>
        <span class="px-1.5 py-0.5 rounded-full text-xs font-bold bg-amber-400 text-slate-950">{{ pendingClearanceCount }}</span>
      </button>

      <button
        @click="activeTab = 'documents'"
        class="px-4 py-2.5 rounded-2xl text-xs font-bold transition flex items-center gap-2 whitespace-nowrap"
        :class="activeTab === 'documents' ? 'bg-[#2a3b6f] text-white shadow-md' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'"
      >
        <span>Document Request Payments</span>
        <span class="px-1.5 py-0.5 rounded-full text-xs font-bold bg-emerald-400 text-slate-950">{{ unpaidDocsCount }}</span>
      </button>
    </div>

    <!-- TAB 1: CLEARANCE LEDGER -->
    <div v-if="activeTab === 'clearances'" class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
      <div class="p-4 border-b border-slate-100 flex items-center justify-between">
        <h3 class="text-sm font-bold text-slate-900">Student Financial Clearance & Account Standing</h3>
        <span class="text-xs text-slate-500">Toggle sign-off upon payment verification</span>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-slate-50 border-b border-slate-200 text-xs font-bold text-slate-500 uppercase tracking-wider">
              <th class="py-3.5 px-4">Student ID & Name</th>
              <th class="py-3.5 px-4">Program & Year</th>
              <th class="py-3.5 px-4">Cashier Status</th>
              <th class="py-3.5 px-4">Cashier Remarks</th>
              <th class="py-3.5 px-4 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 text-xs text-slate-700">
            <tr v-for="cl in clearancesList" :key="cl.id" class="hover:bg-slate-50/70">
              <td class="py-3.5 px-4">
                <p class="font-bold text-slate-900">{{ cl.student?.name || cl.student?.user?.name }}</p>
                <p class="font-mono text-xs text-blue-900">{{ cl.student?.student_number }}</p>
              </td>
              <td class="py-3.5 px-4 text-slate-600">
                {{ cl.student?.course_program }} (Year {{ cl.student?.year_level }})
              </td>
              <td class="py-3.5 px-4">
                <span
                  class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase"
                  :class="cl.cashier_status ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800'"
                >
                  {{ cl.cashier_status ? '✓ CLEARED' : 'HOLD / UNPAID' }}
                </span>
              </td>
              <td class="py-3.5 px-4 text-slate-600">
                {{ cl.cashier_remarks || (cl.cashier_status ? 'Assessment paid in full' : 'Pending balance settlement') }}
              </td>
              <td class="py-3.5 px-4 text-right space-x-1">
                <button
                  v-if="!cl.cashier_status"
                  @click="toggleCashierClearance(cl.id, true)"
                  class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-2xl text-xs font-bold shadow-sm transition"
                >
                  Clear Hold (Paid)
                </button>
                <button
                  v-else
                  @click="toggleCashierClearance(cl.id, false)"
                  class="px-3 py-1.5 bg-red-50 text-red-700 hover:bg-red-100 rounded-2xl text-xs font-bold border border-red-200 transition"
                >
                  Revert to Hold
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- TAB 2: DOCUMENT PAYMENTS -->
    <div v-if="activeTab === 'documents'" class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
      <div class="p-4 border-b border-slate-100 flex items-center justify-between">
        <h3 class="text-sm font-bold text-slate-900">Document Request Fee Collection</h3>
        <span class="text-xs text-slate-500">Collect fees and issue official receipt confirmation</span>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-slate-50 border-b border-slate-200 text-xs font-bold text-slate-500 uppercase tracking-wider">
              <th class="py-3.5 px-4">Student</th>
              <th class="py-3.5 px-4">Document Type</th>
              <th class="py-3.5 px-4 text-center">Copies</th>
              <th class="py-3.5 px-4 text-center">Amount Due</th>
              <th class="py-3.5 px-4 text-center">Payment Status</th>
              <th class="py-3.5 px-4 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 text-xs text-slate-700">
            <tr v-for="doc in documentsList" :key="doc.id" class="hover:bg-slate-50/70">
              <td class="py-3.5 px-4 font-bold text-slate-900">{{ doc.student?.name }}</td>
              <td class="py-3.5 px-4 font-semibold text-blue-900">{{ doc.document_type || doc.type }}</td>
              <td class="py-3.5 px-4 text-center font-bold">{{ doc.copies }}</td>
              <td class="py-3.5 px-4 text-center font-bold text-slate-900">PHP {{ Number(doc.fee).toFixed(2) }}</td>
              <td class="py-3.5 px-4 text-center">
                <span
                  class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase"
                  :class="doc.payment_status === 'paid' ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800'"
                >
                  {{ doc.payment_status }}
                </span>
              </td>
              <td class="py-3.5 px-4 text-right">
                <button
                  v-if="doc.payment_status === 'unpaid'"
                  @click="markDocPaid(doc.id)"
                  class="px-3 py-1.5 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs rounded-2xl shadow-sm transition"
                >
                  Receive Payment (OR# {{ Math.floor(100000 + Math.random() * 900000) }})
                </button>
                <span v-else class="text-xs text-emerald-600 font-bold">✓ Settled</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import { portalApi } from '../../services/api';
import type { Clearance, DocumentRequest } from '../../types';
import { activeSidebarSection } from '../../services/navigation';

const activeTab = ref<'clearances' | 'documents'>('clearances');
watch(activeSidebarSection, (page) => {
  if (page === 'clearances' || page === 'documents') activeTab.value = page;
});
const clearancesList = ref<Clearance[]>([]);
const documentsList = ref<DocumentRequest[]>([]);

const clearedCount = computed(() => clearancesList.value.filter((c) => c.cashier_status).length);
const pendingClearanceCount = computed(() => clearancesList.value.filter((c) => !c.cashier_status).length);
const unpaidDocsCount = computed(() => documentsList.value.filter((d) => d.payment_status === 'unpaid').length);
const outstandingDocumentFees = computed(() => documentsList.value
  .filter((document) => document.payment_status === 'unpaid')
  .reduce((total, document) => total + Number(document.fee), 0));

function formatCurrency(amount: number) {
  return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(amount);
}

async function loadData() {
  try {
    const [clearances, docs] = await Promise.all([
      portalApi.getClearances(),
      portalApi.getDocuments(),
    ]);
    clearancesList.value = clearances;
    documentsList.value = docs;
  } catch (err) {
    console.error(err);
  }
}

async function toggleCashierClearance(id: number, approved: boolean) {
  try {
    const remarks = approved ? 'Payment verified by cashier' : 'Outstanding balance requires settlement';
    await portalApi.updateClearanceStep(id, 'cashier', approved, remarks);
    await loadData();
  } catch (err) {
    console.error(err);
  }
}

async function markDocPaid(id: number) {
  try {
    await portalApi.updateDocumentStatus(id, {
      payment_status: 'paid',
      remarks: 'Payment recorded by cashier; external receipt reference is not configured.',
    });
    await loadData();
  } catch (err) {
    console.error(err);
  }
}

onMounted(() => {
  loadData();
});
</script>
