<template>
  <div class="space-y-6">
    <nav class="flex flex-wrap gap-2 border-b border-slate-200 pb-3" aria-label="Faculty workspace">
      <button type="button" @click="setSidebarSection('sections', 'faculty')" class="rounded-lg px-3 py-2 text-sm font-semibold" :class="activeSidebarSection === 'sections' ? 'bg-emerald-900 text-white' : 'text-slate-700 hover:bg-slate-100'">My sections</button>
      <button type="button" @click="setSidebarSection('gradebook', 'faculty')" class="rounded-lg px-3 py-2 text-sm font-semibold" :class="activeSidebarSection === 'gradebook' ? 'bg-emerald-900 text-white' : 'text-slate-700 hover:bg-slate-100'">Gradebook</button>
    </nav>
    <SectionWorkspace mode="faculty" :gradebook="activeSidebarSection === 'gradebook'" />
  </div>
</template>

<script setup lang="ts">
import { activeSidebarSection, setSidebarSection } from '../../services/navigation';
import SectionWorkspace from '../sections/SectionWorkspace.vue';
</script>
