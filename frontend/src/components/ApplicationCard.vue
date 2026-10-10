<template>
  <div class="p-6 bg-white rounded-2xl shadow-sm border border-slate-200 hover:border-emerald-500 transition">
    <h2 class="text-xl font-bold text-slate-900 mb-2">{{ title }}</h2>
    <p class="text-slate-600 mb-6 text-xs leading-relaxed">{{ description }}</p>
    <button 
      @click="$emit('apply')"
      class="px-4 py-2 bg-[#064e3b] hover:bg-[#022c22] text-white font-bold text-xs rounded-xl shadow transition duration-200"
    >
      Apply Now
    </button>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  title: string;
  description: string;
}>();

defineEmits(['apply']);
</script>

