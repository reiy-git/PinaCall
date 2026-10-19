<template>
  <div class="pointer-events-none absolute inset-0 overflow-hidden" :style="{ opacity }" aria-hidden="true">
    <svg
      class="absolute bottom-14 right-4 h-[66%] w-auto max-w-none sm:right-8"
      viewBox="0 0 640 520"
      fill="none"
      stroke="currentColor"
      stroke-width="3"
      stroke-linecap="round"
      stroke-linejoin="round"
      xmlns="http://www.w3.org/2000/svg"
    >
      <!-- Main building: shared across every slide, drawn large -->
      <g transform="translate(104 64) scale(1.12)">
        <path d="M0 380H480" />
        <path d="M70 380V362H410V380M90 362V346H390V362" />
        <!-- wings -->
        <rect x="8" y="262" width="84" height="84" />
        <rect x="388" y="262" width="84" height="84" />
        <g fill="currentColor" fill-opacity="0.45" stroke="none">
          <rect v-for="w in wingWindows" :key="w[0] + '-' + w[1]" :x="w[0]" :y="w[1]" width="16" height="24" />
        </g>
        <!-- body, entablature, pediment -->
        <rect x="96" y="206" width="288" height="140" />
        <rect x="84" y="190" width="312" height="16" fill="currentColor" fill-opacity="0.3" />
        <path d="M72 190L240 118L408 190Z" />
        <path d="M112 182L240 128L368 182" />
        <circle cx="240" cy="160" r="14" />
        <!-- columns -->
        <rect v-for="x in columns" :key="x" :x="x" y="206" width="16" height="140" fill="currentColor" fill-opacity="0.3" />
        <!-- door -->
        <path d="M222 346V302Q240 282 258 302V346" />
        <!-- clock tower + dome + flag -->
        <path d="M208 122V80H272V122" />
        <path d="M200 80Q240 22 280 80Z" fill="currentColor" fill-opacity="0.3" />
        <circle cx="240" cy="102" r="10" />
        <path d="M240 96V102L245 105" />
        <path d="M240 34V6M240 6H266L258 13L266 20H240" />
      </g>

      <!-- Hero: cap, open book, sparkles, orbit -->
      <g v-if="variant === 'hero'">
        <circle cx="130" cy="150" r="104" stroke-dasharray="3 12" />
        <circle cx="130" cy="150" r="64" stroke-dasharray="3 12" />
        <g transform="translate(60 112) scale(1.5)">
          <path d="M0 20L40 0L80 20L40 40Z" />
          <path d="M16 30V50Q40 62 64 50V30" />
          <path d="M80 20V46" />
          <circle cx="80" cy="50" r="4" fill="currentColor" />
        </g>
        <g transform="translate(40 300) scale(1.1)">
          <path d="M0 10Q30 0 60 10V70Q30 60 0 70Z" />
          <path d="M60 10Q90 0 120 10V70Q90 60 60 70Z" />
          <path d="M14 26Q30 21 46 26M14 40Q30 35 46 40M74 26Q90 21 106 26M74 40Q90 35 106 40" />
        </g>
        <path v-for="s in sparkles" :key="s[0] + '-' + s[1]" :d="`M${s[0]} ${s[1] - s[2]}V${s[1] + s[2]}M${s[0] - s[2]} ${s[1]}H${s[0] + s[2]}`" />
      </g>

      <!-- Enrollment: checklist + four-stage path -->
      <g v-else-if="variant === 'enroll'">
        <g transform="translate(36 40) scale(1.2)">
          <rect width="96" height="124" rx="8" />
          <path d="M16 28l5 5 9-10M40 28H80" />
          <path d="M16 56l5 5 9-10M40 56H80" />
          <path d="M16 84l5 5 9-10M40 84H66" />
        </g>
        <path d="M60 300C110 250 150 340 200 290S270 250 300 290" stroke-dasharray="3 12" />
        <circle v-for="c in stages" :key="c[0]" :cx="c[0]" :cy="c[1]" r="14" fill="currentColor" fill-opacity="0.3" />
        <path d="M46 300l6 6 10-12M104 276l6 6 10-12" />
        <path v-for="s in sparkles.slice(0, 2)" :key="s[0] + '-' + s[1]" :d="`M${s[0]} ${s[1] - s[2]}V${s[1] + s[2]}M${s[0] - s[2]} ${s[1]}H${s[0] + s[2]}`" />
      </g>

      <!-- Records: bar chart, trend line, medal -->
      <g v-else-if="variant === 'records'">
        <g transform="translate(30 80) scale(1.2)">
          <path d="M0 0V130H170" />
          <rect x="18" y="80" width="22" height="50" fill="currentColor" fill-opacity="0.3" />
          <rect x="54" y="56" width="22" height="74" fill="currentColor" fill-opacity="0.3" />
          <rect x="90" y="30" width="22" height="100" fill="currentColor" fill-opacity="0.3" />
          <rect x="126" y="8" width="22" height="122" fill="currentColor" fill-opacity="0.3" />
          <path d="M10 66L64 42L100 18L140 -6" stroke-dasharray="3 8" />
        </g>
        <g transform="translate(248 40) scale(1.3)">
          <circle cx="24" cy="24" r="22" />
          <circle cx="24" cy="24" r="15" stroke-dasharray="2 6" />
          <path d="M12 44L4 78L24 68L44 78L36 44" />
          <text x="24" y="30" text-anchor="middle" font-size="16" font-weight="700" fill="currentColor" stroke="none" font-family="system-ui, sans-serif">A+</text>
        </g>
        <path v-for="s in sparkles.slice(1, 3)" :key="s[0] + '-' + s[1]" :d="`M${s[0]} ${s[1] - s[2]}V${s[1] + s[2]}M${s[0] - s[2]} ${s[1]}H${s[0] + s[2]}`" />
      </g>

      <!-- Services: document stack, clock, seal -->
      <g v-else-if="variant === 'services'">
        <g transform="translate(36 70) scale(1.2)">
          <rect x="16" y="0" width="96" height="124" rx="8" stroke-dasharray="3 8" />
          <rect x="8" y="10" width="96" height="124" rx="8" />
          <rect x="0" y="20" width="96" height="124" rx="8" fill="currentColor" fill-opacity="0.12" />
          <path d="M16 48H80M16 66H80M16 84H56" />
          <circle cx="68" cy="116" r="12" />
          <path d="M62 116l4 4 8-9" />
        </g>
        <g transform="translate(250 60)">
          <circle cx="44" cy="44" r="42" />
          <circle cx="44" cy="44" r="3" fill="currentColor" />
          <path d="M44 16V44L62 54" />
          <path d="M44 6V12M44 76V82M6 44H12M76 44H82" />
        </g>
        <path d="M70 300H220M70 330H170" stroke-dasharray="3 10" />
        <path v-for="s in sparkles.slice(0, 2)" :key="s[0] + '-' + s[1]" :d="`M${s[0]} ${s[1] - s[2]}V${s[1] + s[2]}M${s[0] - s[2]} ${s[1]}H${s[0] + s[2]}`" />
      </g>

      <!-- Join: pencil, new-student badge, cap -->
      <g v-else-if="variant === 'join'">
        <g transform="translate(40 60) rotate(-35 60 60) scale(1.3)">
          <path d="M20 20H100L112 34L100 48H20Z" />
          <path d="M100 20V48" />
          <path d="M112 34H128" />
          <path d="M20 20L8 34L20 48" fill="currentColor" fill-opacity="0.3" />
        </g>
        <g transform="translate(230 60) scale(1.2)">
          <circle cx="30" cy="22" r="16" />
          <path d="M0 80Q0 50 30 50Q60 50 60 80" />
          <path d="M78 34V62M64 48H92" />
        </g>
        <g transform="translate(60 260) scale(1)">
          <path d="M0 20L40 0L80 20L40 40Z" />
          <path d="M16 30V50Q40 62 64 50V30" />
        </g>
        <path v-for="s in sparkles" :key="s[0] + '-' + s[1]" :d="`M${s[0]} ${s[1] - s[2]}V${s[1] + s[2]}M${s[0] - s[2]} ${s[1]}H${s[0] + s[2]}`" />
      </g>
    </svg>
  </div>
</template>

<script setup lang="ts">
withDefaults(defineProps<{
  variant: 'hero' | 'enroll' | 'records' | 'services' | 'journey' | 'join';
  opacity?: number;
}>(), { opacity: 0.07 });

const columns = [108, 152, 196, 268, 312, 356];
const wingWindows = [[24, 280], [56, 280], [24, 312], [56, 312], [404, 280], [436, 280], [404, 312], [436, 312]];
const sparkles = [[250, 70, 8], [170, 250, 6], [300, 200, 5], [20, 230, 6]];
const stages = [[46, 300], [104, 276], [200, 290], [300, 290]];
</script>