<template>
  <header class="bg-[#09090b] border-b border-emerald-950/80 text-white sticky top-0 z-40 shadow-xl">
    <div class="w-full px-4 sm:px-8 lg:px-12">
      <div class="flex items-center justify-between h-16">
        <!-- Brand & University Crest -->
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-emerald-500 via-emerald-600 to-teal-700 flex items-center justify-center text-slate-950 font-black text-xl shadow-lg border border-emerald-400/50 ring-2 ring-emerald-500/20">
            P
          </div>
          <div>
            <div class="flex items-center gap-2">
              <span class="font-black text-lg tracking-tight text-white flex items-center gap-1.5">
                <span>PINACALL</span>
                <span class="text-emerald-400 font-extrabold text-sm">UNIVERSITY PORTAL</span>
              </span>
            </div>
            <p class="text-[11px] text-slate-400 font-medium tracking-wide">Integrated Academic & Administrative Operations</p>
          </div>
        </div>

        <!-- Academic Term & Role Switcher -->
        <div class="flex items-center gap-3 sm:gap-4">
          <div class="hidden md:block text-right">
            <p class="text-[10px] uppercase tracking-widest text-emerald-500">Current academic term</p>
            <p class="mt-0.5 text-xs font-semibold text-slate-200">First Semester · AY 2026–2027</p>
          </div>

          <!-- Active User Profile & Sign Out -->
          <div v-if="authState.user" class="flex items-center gap-3">
            <div class="hidden lg:flex flex-col text-right">
              <span class="text-xs font-bold text-white truncate max-w-[140px]">{{ displayName }}</span>
              <span class="text-[10px] font-semibold uppercase tracking-wider text-emerald-400">{{ roleLabel(authState.user.role) }}</span>
            </div>

            <button
              @click="portalApi.logout()"
              class="text-xs font-semibold px-2.5 py-1.5 rounded-lg bg-red-500/20 text-red-300 border border-red-500/30 hover:bg-red-500/30 transition flex items-center gap-1"
              title="Sign Out"
            >
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
              </svg>
              <span class="hidden sm:inline">Logout</span>
            </button>
          </div>

          <a
            v-else
            href="#sign-in"
            class="text-xs font-bold px-3 py-2 rounded-lg bg-emerald-700 hover:bg-emerald-600 text-white transition shadow"
          >
            Sign in
          </a>
        </div>
      </div>
    </div>
  </header>
</template>

<script setup lang="ts">
import { authState, formatDisplayName, portalApi } from '../services/api';
import { computed } from 'vue';
import type { UserRole } from '../types';

const displayName = computed(() => formatDisplayName(authState.user?.name));

function roleLabel(role: UserRole) {
  switch (role) {
    case 'student': return 'Student';
    case 'registrar': return 'Registrar';
    case 'cashier': return 'Cashier';
    case 'department_head': return 'Dean / Dept Head';
    case 'faculty': return 'Faculty';
    case 'admin': return 'System Admin';
    default: return role;
  }
}
</script>
