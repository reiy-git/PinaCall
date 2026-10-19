<template>
  <svg
    class="sprite"
    :class="{ run, idle, flip }"
    viewBox="0 0 32 56"
    :style="{ '--skin': skin, '--hair': hair, '--shirt': shirt, '--pants': pants, '--bag': bag }"
    aria-hidden="true"
  >
    <g class="bob">
      <!-- far arm + far leg -->
      <rect class="limb arm a shirt-dark" x="14" y="19" width="4" height="13" rx="2" />
      <g class="limb leg a">
        <rect class="pants-dark" x="13.5" y="34" width="5" height="16" rx="2.5" />
        <rect class="shoe" x="13" y="48.5" width="8.5" height="4" rx="2" />
      </g>

      <!-- backpack, torso -->
      <rect v-if="!apron" class="bag" x="5.5" y="19" width="9.5" height="14" rx="3.5" />
      <rect class="shirt" x="10" y="17" width="13" height="19" rx="4" />
      <path v-if="apron" d="M12 21h9v15h-9z" fill="#fff" opacity=".95" />
      <path v-if="skirt" class="pants" d="M10 33h13l2.4 11H7.6z" />

      <!-- head -->
      <rect v-if="longHair" class="hair" x="9.2" y="8" width="5.5" height="14" rx="2.7" />
      <circle class="skin" cx="17" cy="10.5" r="6.6" />
      <ellipse class="hair" cx="16" cy="7.6" rx="7.2" ry="5" />
      <circle cx="20.8" cy="11" r="0.95" fill="#1f2128" />
      <path d="M19.8 14.2q1.6 1 3 0" stroke="#1f2128" stroke-width=".8" stroke-linecap="round" fill="none" />

      <!-- near leg + near arm -->
      <g class="limb leg b">
        <rect class="pants" x="13.5" y="34" width="5" height="16" rx="2.5" />
        <rect class="shoe" x="13" y="48.5" width="8.5" height="4" rx="2" />
      </g>
      <rect class="limb arm b shirt" x="15" y="19" width="4" height="13" rx="2" />
    </g>
  </svg>
</template>

<script setup lang="ts">
withDefaults(defineProps<{
  skin?: string; hair?: string; shirt?: string; pants?: string; bag?: string;
  skirt?: boolean; longHair?: boolean; apron?: boolean;
  run?: boolean; idle?: boolean; flip?: boolean;
}>(), {
  skin: '#e8b992', hair: '#2b2118', shirt: '#f5f6f8', pants: '#2a3b6f', bag: '#f5543d',
  skirt: false, longHair: false, apron: false, run: false, idle: false, flip: false,
});
</script>

<style scoped>
.sprite { height: 40px; width: auto; overflow: visible; display: block; --amp: 26deg; --step: 0.34s; }
.sprite.run { --amp: 46deg; --step: 0.22s; }
.flip { transform: scaleX(-1); }

.skin { fill: var(--skin); }
.hair { fill: var(--hair); }
.shirt { fill: var(--shirt); }
.shirt-dark { fill: color-mix(in srgb, var(--shirt), #000 24%); }
.pants { fill: var(--pants); }
.pants-dark { fill: color-mix(in srgb, var(--pants), #000 26%); }
.bag { fill: var(--bag); }
.shoe { fill: #1f2128; }

.bob { animation: bob var(--step) ease-in-out infinite; }
.limb { transform-box: fill-box; transform-origin: 50% 6%; }
.leg.a, .arm.b { animation: swing var(--step) ease-in-out infinite alternate; }
.leg.b, .arm.a { animation: swing var(--step) ease-in-out infinite alternate-reverse; }

.idle .limb { animation: none; }
.idle .bob { animation: breathe 2.8s ease-in-out infinite; }

@keyframes swing { from { transform: rotate(calc(var(--amp) * -1)); } to { transform: rotate(var(--amp)); } }
@keyframes bob { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-1.4px); } }
@keyframes breathe { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-0.6px); } }

@media (prefers-reduced-motion: reduce) {
  .bob, .limb { animation: none !important; }
}
</style>
