<template>
  <main class="min-h-screen bg-mist px-4 py-5 text-slate-900 sm:px-8">
    <div class="mx-auto flex min-h-screen max-w-[100rem] flex-col">
      <section class="grid flex-1 gap-6 py-6 lg:grid-cols-2">
        <QueueColumn
          label="Cashier"
          description="Fee payment window"
          :data="lobbyData?.cashier"
          accent="lime"
          action="Fee payment"
        />
        <QueueColumn
          label="Registrar"
          description="Document release window"
          :data="lobbyData?.registrar"
          accent="coral"
          action="Document collection"
        />
      </section>
    </div>
  </main>
</template>

<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue';
import QueueColumn from './QueueColumn.vue';
import { portalApi } from '../../services/api';
import type { LobbyDisplayData } from '../../types';

const lobbyData = ref<LobbyDisplayData | null>(null);
let refreshInterval: ReturnType<typeof setInterval>;

async function loadLobbyData() {
  try {
    lobbyData.value = await portalApi.getLobbyDisplay();
  } catch {
    // Retry on the next polling interval.
  }
}

onMounted(() => {
  void loadLobbyData();
  refreshInterval = setInterval(loadLobbyData, 5000);
});

onUnmounted(() => {
  clearInterval(refreshInterval);
});
</script>
