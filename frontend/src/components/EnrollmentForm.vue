<template>
  <div class="max-w-md mx-auto p-6 bg-white rounded-2xl shadow-xl border border-slate-200">
    <h2 class="text-2xl font-bold mb-6 text-slate-900">PINACALL Enrollment Application</h2>
    <form @submit.prevent="submit" class="space-y-4">
      <div>
        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Student Category</label>
        <select v-model="form.type" required class="w-full text-xs bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 font-medium text-slate-800 focus:ring-2 focus:ring-emerald-600 focus:outline-none">
          <option value="new">New Student</option>
          <option value="transferee">Transferee</option>
          <option value="irregular">Irregular</option>
          <option value="old">Old / Continuing</option>
          <option value="shiftee">Shiftee</option>
          <option value="returnee">Returnee</option>
        </select>
      </div>
      <div>
        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Educational Background</label>
        <textarea v-model="form.educational_background" rows="3" required class="w-full text-xs bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 font-medium text-slate-800 focus:ring-2 focus:ring-emerald-600 focus:outline-none"></textarea>
      </div>
      <button type="submit" class="w-full py-2.5 bg-[#064e3b] hover:bg-[#022c22] text-white font-bold text-xs rounded-xl shadow transition">Submit to Admissions</button>
    </form>
    <p v-if="error" class="mt-4 text-red-500 text-xs font-semibold">{{ error }}</p>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';

const form = ref({
  type: 'new',
  educational_background: '',
});

const error = ref('');

const submit = () => {
  console.log('Submitting:', form.value);
  alert('Application Submitted to PINACALL Admissions Queue!');
};
</script>

