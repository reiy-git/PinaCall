<template>
  <!-- Background layer: sky, drifting clouds, the university facade -->
  <div class="scene-bg" aria-hidden="true">
    <div class="sky"></div>

    <!-- Distant mountains + city skyline, peeking above the roofline -->
    <svg class="skyline" viewBox="0 0 1600 260" preserveAspectRatio="xMidYMax slice">
      <defs>
        <pattern id="pc-wins" width="14" height="16" patternUnits="userSpaceOnUse">
          <rect x="3" y="4" width="6" height="7" style="fill:var(--sky-win)" />
        </pattern>
      </defs>
      <path d="M0 260V150L150 70L260 130L400 40L560 140L700 90L860 150L1000 60L1150 140L1300 80L1450 150L1600 100V260Z" style="fill:var(--mt-a)" />
      <path d="M0 260V190L120 140L240 190L380 120L520 200L700 150L880 210L1050 140L1200 200L1380 130L1500 190L1600 160V260Z" style="fill:var(--mt-b)" />
      <g v-for="b in towers" :key="b.x">
        <rect :x="b.x" :y="260 - b.h" :width="b.w" :height="b.h" style="fill:var(--sky-bldg)" />
        <rect :x="b.x" :y="260 - b.h" :width="b.w" :height="b.h" fill="url(#pc-wins)" />
        <rect :x="b.x - 4" :y="260 - b.h - 5" :width="b.w + 8" height="5" style="fill:var(--sky-bldg)" />
        <path v-if="b.ant" :d="`M${b.x + b.w / 2} ${260 - b.h - 5}v-26`" style="stroke:var(--sky-bldg)" stroke-width="3" />
      </g>
    </svg>

    <div class="clouds">
      <svg v-for="(c, i) in clouds" :key="i" class="cloud" viewBox="0 0 120 46" :style="{ top: c.top, width: c.w + 'px', '--dur': c.dur + 's', '--delay': c.delay + 's' }">
        <g><ellipse cx="36" cy="32" rx="30" ry="12" /><ellipse cx="62" cy="23" rx="26" ry="17" /><ellipse cx="88" cy="32" rx="26" ry="12" /></g>
      </svg>
    </div>

    <svg class="campus" viewBox="0 0 1600 300" preserveAspectRatio="xMidYMax meet">
      <defs>
        <symbol id="pc-win" viewBox="0 0 84 36">
          <rect width="84" height="36" rx="2" style="fill:var(--frame)" />
          <rect x="3" y="3" width="78" height="30" style="fill:var(--win)" />
          <path d="M23 3v30M42 3v30M61 3v30M3 14h78" style="stroke:var(--frame)" stroke-width="2" fill="none" />
        </symbol>
      </defs>

      <!-- roof water tank -->
      <g style="fill:var(--tank)"><rect x="572" y="-22" width="40" height="20" rx="10" /><rect x="580" y="-4" width="3" height="8" /><rect x="601" y="-4" width="3" height="8" /></g>

      <!-- wall + dark horizontal bands -->
      <rect x="-1000" y="40" width="3600" height="228" style="fill:var(--wall)" />
      <rect x="-1000" y="40" width="3600" height="6" style="fill:var(--frame)" />
      <rect x="74" y="-4" width="1452" height="6" style="fill:var(--frame)" />
      <rect x="74" y="2" width="1452" height="44" style="fill:var(--panel)" />
      <rect v-for="y in [112, 186]" :key="y" x="-1000" :y="y" width="3600" height="8" style="fill:var(--panel)" />

      <!-- pilasters -->
      <rect v-for="k in pilasters" :key="'p' + k" :x="74 + 132 * k - 13" y="46" width="26" height="222" style="fill:var(--panel)" />

      <!-- windows -->
      <template v-for="f in floors" :key="f.id">
        <template v-for="i in bays" :key="f.id + i">
          <use v-if="!(f.id === 1 && i === 5)" href="#pc-win" :x="140 + 132 * i - 42" :y="f.wy" width="84" height="36" :class="{ lit: (i * 7 + f.id * 3 + 20) % 5 === 0 }" />
        </template>
      </template>

      <!-- sign -->
      <g>
        <circle cx="588" cy="24" r="14" style="fill:#2f8f4e" /><circle cx="588" cy="24" r="9" style="fill:#e8d58a" />
        <circle cx="1012" cy="24" r="14" style="fill:#2f8f4e" /><circle cx="1012" cy="24" r="9" style="fill:#e8d58a" />
        <text x="800" y="21" text-anchor="middle" class="sign">PAMANTASAN NG CABUYAO</text>
        <text x="800" y="40" text-anchor="middle" class="sign">BAGONG CABUYAO HALL</text>
      </g>

      <!-- entrance: canopy, doors, steps -->
      <rect x="726" y="190" width="148" height="14" rx="2" style="fill:var(--frame)" />
      <text x="800" y="200.5" text-anchor="middle" class="canopy">UNIVERSITY OF CABUYAO</text>
      <rect x="748" y="206" width="104" height="62" style="fill:var(--door)" />
      <path d="M800 206v62M748 222h104" style="stroke:var(--frame)" stroke-width="2" fill="none" />
      <rect x="730" y="266" width="140" height="6" style="fill:var(--step)" />
      <rect x="722" y="272" width="156" height="6" style="fill:var(--step)" />
      <rect x="880" y="286" width="90" height="6" rx="1" style="fill:#e0553b" />

      <!-- pavement -->
      <rect x="-1000" y="268" width="3600" height="40" style="fill:var(--ground)" />
      <rect x="722" y="272" width="156" height="6" style="fill:var(--step)" />
      <rect v-for="x in tiles" :key="'tl' + x" :x="x" y="268" width="2" height="32" style="fill:var(--tile)" />

      <!-- street lamps -->
      <g v-for="x in lamps" :key="'lp' + x" style="fill:var(--pole)">
        <rect :x="x - 8" y="262" width="16" height="8" rx="2" />
        <rect :x="x - 2.5" y="146" width="5" height="120" />
        <path :d="`M${x} 150q0 -16 18 -16h14`" style="stroke:var(--pole)" stroke-width="4" fill="none" stroke-linecap="round" />
        <rect :x="x + 28" y="130" width="24" height="9" rx="4" />
        <rect :x="x + 31" y="138" width="18" height="3" rx="1.5" style="fill:var(--lamp)" />
      </g>

      <!-- trees -->
      <g v-for="(t, i) in trees" :key="'t' + i">
        <ellipse :cx="t.x" cy="270" :rx="70 * t.s" ry="9" style="fill:var(--lawn)" />
        <rect :x="t.x - 5 * t.s" :y="268 - 62 * t.s" :width="10 * t.s" :height="62 * t.s" style="fill:var(--trunk)" />
        <circle :cx="t.x" :cy="268 - 104 * t.s" :r="38 * t.s" style="fill:var(--leaf-a)" />
        <circle :cx="t.x - 28 * t.s" :cy="268 - 78 * t.s" :r="30 * t.s" style="fill:var(--leaf-b)" />
        <circle :cx="t.x + 28 * t.s" :cy="268 - 80 * t.s" :r="31 * t.s" style="fill:var(--leaf-b)" />
        <circle :cx="t.x + 6 * t.s" :cy="268 - 88 * t.s" :r="26 * t.s" style="fill:var(--leaf-a)" />
      </g>
    </svg>

    <!-- Road: curb, lane dashes, zebra crossing in front of the main entrance -->
    <div class="street">
      <div class="lane"></div>
      <svg class="zebra" viewBox="0 0 120 100" preserveAspectRatio="none">
        <rect v-for="i in 7" :key="i" :x="24 - (i - 1) * 2.4" :y="4 + (i - 1) * 13.5" :width="72 + (i - 1) * 4.8" height="8" rx="1" style="fill:var(--lane)" />
      </svg>
    </div>
  </div>

  <!-- Foreground layer: students and the egg waffle cart (floats above the cards, never blocks clicks) -->
  <div class="scene-fg" aria-hidden="true">
    <div class="cart" style="left: 25cqw">
      <span class="cart-label">EGG WAFFLES</span>
      <span class="steam s1"></span><span class="steam s2"></span>
      <svg class="cart-svg" viewBox="0 0 64 46">
        <path d="M6 14h52l-5-12H11z" style="fill:#f5543d" />
        <path d="M6 14h52v4H6z" style="fill:#fff" opacity=".85" />
        <rect x="9" y="18" width="46" height="18" rx="2" style="fill:#d7cd9b" />
        <rect x="9" y="30" width="46" height="6" style="fill:#b59a58" />
        <circle cx="16" cy="40" r="5" style="fill:#2b2d31" /><circle cx="48" cy="40" r="5" style="fill:#2b2d31" />
        <circle cx="16" cy="40" r="1.8" style="fill:#b5bac1" /><circle cx="48" cy="40" r="1.8" style="fill:#b5bac1" />
        <ellipse cx="32" cy="23" rx="9" ry="3" style="fill:#f1c06a" />
      </svg>
      <StudentSprite class="vendor" idle skin="#b9805a" hair="#1f1a14" shirt="#3b4f8f" pants="#3b4048" apron flip />
    </div>

    <!-- Traffic -->
    <div v-for="v in vehicles" :key="v.id" class="vehicle" :style="v.style">
      <svg v-if="v.kind === 'jeep'" class="v-jeep" viewBox="0 0 200 76">
        <ellipse cx="100" cy="72" rx="90" ry="3" fill="#000" opacity=".18" />
        <rect x="16" y="12" width="158" height="24" rx="6" fill="#fff" />
        <rect x="12" y="6" width="156" height="9" rx="4" fill="#f1c33f" />
        <text x="90" y="13.2" text-anchor="middle" font-size="6.6" font-weight="800" fill="#7a5a10" font-family="system-ui, sans-serif" letter-spacing=".6">PNC EXPRESS</text>
        <rect v-for="x in [24, 52, 80, 108, 136]" :key="x" :x="x" y="17" width="22" height="14" rx="2.5" fill="#1f2a3a" />
        <path d="M166 16L182 32H162V16Z" fill="#1f2a3a" />
        <rect x="6" y="30" width="188" height="34" rx="7" fill="#fff" />
        <rect x="6" y="46" width="188" height="5" fill="#e0553b" />
        <rect x="6" y="53" width="188" height="4" fill="#2a3b6f" />
        <rect x="188" y="52" width="9" height="9" rx="2" fill="#b9bec7" />
        <circle cx="189" cy="42" r="3.2" fill="#ffe9a0" />
        <circle cx="46" cy="64" r="10" fill="#23252a" /><circle cx="46" cy="64" r="4" fill="#b9bec7" />
        <circle cx="152" cy="64" r="10" fill="#23252a" /><circle cx="152" cy="64" r="4" fill="#b9bec7" />
      </svg>
      <svg v-else-if="v.kind === 'trike'" class="v-trike flip" viewBox="0 0 100 70">
        <ellipse cx="50" cy="67" rx="44" ry="2.6" fill="#000" opacity=".18" />
        <rect x="2" y="22" width="50" height="5" rx="2" fill="#2a3b6f" />
        <rect x="4" y="26" width="44" height="26" rx="6" fill="#f5543d" />
        <rect x="10" y="31" width="22" height="12" rx="2" fill="#a9c7e0" />
        <path d="M52 52L58 34H78L90 46V52Z" fill="#3b4f8f" />
        <rect x="58" y="14" width="28" height="4" rx="2" fill="#2a3b6f" />
        <rect x="60" y="18" width="2" height="16" fill="#2a3b6f" /><rect x="84" y="18" width="2" height="14" fill="#2a3b6f" />
        <circle cx="72" cy="23" r="5" fill="#e0ac82" />
        <rect x="66" y="28" width="12" height="14" rx="4" fill="#c3eb4d" />
        <circle cx="22" cy="58" r="8" fill="#23252a" /><circle cx="22" cy="58" r="3" fill="#b9bec7" />
        <circle cx="76" cy="58" r="9" fill="#23252a" /><circle cx="76" cy="58" r="3.4" fill="#b9bec7" />
      </svg>
      <svg v-else class="v-car flip" viewBox="0 0 160 56">
        <ellipse cx="80" cy="53" rx="72" ry="3" fill="#000" opacity=".18" />
        <path d="M6 40Q6 30 18 28L40 14H100Q112 14 120 26L148 30Q156 32 156 40V46H6Z" fill="#2f8f9d" />
        <path d="M44 18H66V28H36ZM72 18H98Q106 20 110 28H72Z" fill="#cfe3f1" />
        <rect x="146" y="34" width="9" height="5" rx="2" fill="#ffe9a0" />
        <circle cx="38" cy="46" r="9" fill="#23252a" /><circle cx="38" cy="46" r="3.6" fill="#b9bec7" />
        <circle cx="122" cy="46" r="9" fill="#23252a" /><circle cx="122" cy="46" r="3.6" fill="#b9bec7" />
      </svg>
    </div>

    <div v-for="w in walkers" :key="w.id" class="walker" :class="{ pausing: w.pauseAt }" :style="w.style">
      <span v-if="w.say" class="bubble" :class="{ shout: w.shout, high: w.high, paused: w.pauseAt }">{{ w.say }}</span>
      <!-- Walkers that stop mid-route swap between a walking and an idle sprite so the legs stop moving while they talk -->
      <template v-if="w.pauseAt">
        <StudentSprite class="pw-walk" v-bind="w.look" :flip="w.dir === 'rtl'" />
        <StudentSprite class="pw-idle" v-bind="w.look" :flip="w.dir === 'rtl'" idle />
      </template>
      <StudentSprite v-else v-bind="w.look" :flip="w.dir === 'rtl'" :run="w.run" />
    </div>
  </div>
</template>

<script setup lang="ts">
import StudentSprite from './StudentSprite.vue';

const bays = Array.from({ length: 27 }, (_, i) => i - 8);
const pilasters = Array.from({ length: 29 }, (_, i) => i - 9);
const floors = [
  { id: 3, wy: 62 },
  { id: 2, wy: 136 },
  { id: 1, wy: 210 },
];
const trees = [
  { x: -620, s: 1.05 }, { x: -330, s: 1 }, { x: -60, s: 1.1 }, { x: 210, s: 0.9 },
  { x: 1420, s: 1 }, { x: 1650, s: 1.15 }, { x: 1920, s: 1 }, { x: 2200, s: 0.95 },
];
const tiles = Array.from({ length: 40 }, (_, i) => -1000 + i * 90);
const lamps = [440, 1170];
const towers = [
  { x: 60, w: 64, h: 120 }, { x: 210, w: 78, h: 170, ant: true }, { x: 350, w: 56, h: 100 },
  { x: 1040, w: 70, h: 130 }, { x: 1180, w: 84, h: 190, ant: true }, { x: 1330, w: 60, h: 110 }, { x: 1470, w: 76, h: 150 },
];
const clouds = [
  { top: '6%', w: 280, dur: 120, delay: -30 },
  { top: '20%', w: 210, dur: 170, delay: -110 },
  { top: '12%', w: 350, dur: 210, delay: -60 },
];

type Dir = 'ltr' | 'rtl';
interface Walker { id: string; dir: Dir; dur: number; delay: number; lane: number; say?: string; pauseAt?: string; shout?: boolean; high?: boolean; run?: boolean; look: Record<string, unknown>; style?: Record<string, string> }

const raw: Omit<Walker, 'style'>[] = [
  { id: 'dangal', dir: 'ltr', dur: 22, delay: -2.2, lane: 1, say: 'Dangal greetings!', look: { skin: '#f1c9a5', hair: '#2b2118', shirt: '#f5f6f8', pants: '#2a3b6f', bag: '#f5543d' } },
  { id: 'waffle', dir: 'ltr', dur: 27, delay: -10.3, lane: 0, say: 'Kuya, pabili nga po egg waffles!', high: true, look: { skin: '#d9a074', hair: '#1f1a14', shirt: '#c3eb4d', pants: '#3b4048', bag: '#2a3b6f', skirt: true, longHair: true } },
  { id: 'blood', dir: 'rtl', dur: 25, delay: -17.5, lane: 2, say: 'I love blood cubes!', look: { skin: '#b9805a', hair: '#15110d', shirt: '#f5f6f8', pants: '#1f2937', bag: '#c3eb4d' } },
  { id: 'mama', dir: 'rtl', dur: 12, delay: -1.8, lane: 1, say: 'MAMAAAA', shout: true, run: true, look: { skin: '#8d5a3b', hair: '#15110d', shirt: '#f5543d', pants: '#2a3b6f', bag: '#d7cd9b' } },
  { id: 'q1', dir: 'ltr', dur: 19, delay: -1, lane: 2, look: { skin: '#e8b992', hair: '#5a3b22', shirt: '#3b4f8f', pants: '#252f4d', bag: '#f5543d', longHair: true, skirt: true } },
  { id: 'q2', dir: 'rtl', dur: 29, delay: -21, lane: 0, look: { skin: '#f1c9a5', hair: '#2b2118', shirt: '#d5988a', pants: '#3b4048', bag: '#2a3b6f' } },
  { id: 'josh', dir: 'ltr', dur: 34, delay: -6, lane: 1, say: "I'm Josh btw", pauseAt: '30cqw', look: { skin: '#e0ac82', hair: '#3a2a1c', shirt: '#2f8f9d', pants: '#2b2f3a', bag: '#c3eb4d' } },
];

const LANE_BOTTOM = [5, 11, 17];

const vehicles = [
  { id: 'jeep', kind: 'jeep', dir: 'ltr', dur: 15, delay: -4, bottom: 4, z: 3 },
  { id: 'trike', kind: 'trike', dir: 'rtl', dur: 18, delay: -11, bottom: 36, z: 2 },
  { id: 'car', kind: 'car', dir: 'rtl', dur: 23, delay: -2, bottom: 32, z: 2 },
].map((v) => ({
  ...v,
  style: {
    '--from': v.dir === 'ltr' ? '-300px' : 'calc(100cqw + 40px)',
    '--to': v.dir === 'ltr' ? 'calc(100cqw + 40px)' : '-300px',
    '--dur': `${v.dur}s`,
    '--delay': `${v.delay}s`,
    bottom: `${v.bottom}px`,
    zIndex: String(v.z),
  },
}));
const walkers: Walker[] = raw.map((w) => ({
  ...w,
  style: {
    '--from': w.dir === 'ltr' ? '-6cqw' : '106cqw',
    '--to': '50cqw',
    ...(w.pauseAt ? { '--mid': w.pauseAt } : {}),
    '--dur': `${w.dur}s`,
    '--delay': `${w.delay}s`,
    bottom: `calc(var(--road-h) + ${LANE_BOTTOM[w.lane]}px * var(--s))`,
    zIndex: String(10 - w.lane),
  },
}));
</script>

<style scoped>
.scene-bg, .scene-fg {
  --sky-top: #bcd8ee; --sky-bot: #edeef2;
  --wall: #f3f4f6; --panel: #3d4349; --frame: #ffffff; --win: #a9c7e0; --win-lit: #a9c7e0;
  --door: #46515c; --step: #dfe2e7; --ground: #d9dce1; --lawn: #86c473; --trunk: #7a5c3e;
  --leaf-a: #3f9b4a; --leaf-b: #2f7d3b; --tank: #c9ced6; --cloud: #fff; --cloud-o: .95;
  --bubble-bg: #fff; --bubble-fg: #1a2342;
  --campus-h: 26rem; --s: 1.85; --road-h: 7rem;
  --mt-a: #bccadf; --mt-b: #a7b6d1; --sky-bldg: #adbbd5; --sky-win: #d3dcec;
  --road: #43464c; --curb: #cfd2d8; --lane: #f3f4f6; --pole: #6a707b; --lamp: #fff3b8; --tile: #c9cdd4;
}


.scene-bg { position: absolute; inset: 0; z-index: 0; overflow: hidden; pointer-events: none; }
.sky { position: absolute; inset: 0; background: linear-gradient(to bottom, var(--sky-top), var(--sky-bot) 75%); }

.clouds { position: absolute; inset: 0; container-type: inline-size; }
.cloud { position: absolute; left: 0; fill: var(--cloud); opacity: var(--cloud-o); animation: drift var(--dur) linear var(--delay) infinite; }
@keyframes drift { from { transform: translateX(-20cqw); } to { transform: translateX(110cqw); } }

/* Big backdrop: width tracks height (viewBox is 16:3) so the building scales up instead of letterboxing. --s scales the other scene pieces (clouds, students, cart) to match. */
.campus { position: absolute; bottom: var(--road-h); left: 50%; width: max(100%, calc(var(--campus-h) * 16 / 3)); height: var(--campus-h); transform: translateX(-50%); overflow: visible; }
.lit { --win: var(--win-lit); }
.sign { font-family: var(--font-sans); font-weight: 800; font-size: 16px; letter-spacing: .5px; fill: #e9eef3; }
.canopy { font-family: var(--font-sans); font-weight: 800; font-size: 9.5px; letter-spacing: .3px; fill: #2b2d31; }

/* Skyline sits behind the facade; its lower edge is hidden by the wall */
.skyline { position: absolute; left: 0; width: 100%; height: 15rem; bottom: calc(var(--road-h) + var(--campus-h) - 3rem); }

/* Road */
.street { position: absolute; left: 0; right: 0; bottom: 0; height: var(--road-h); box-sizing: border-box; background: var(--road); border-top: 6px solid var(--curb); box-shadow: inset 0 3px 0 rgb(0 0 0 / .14); }
.lane { position: absolute; left: 0; right: 0; top: 58%; height: 4px; background: repeating-linear-gradient(90deg, var(--lane) 0 48px, transparent 48px 96px); opacity: .85; }
.zebra { position: absolute; left: 50%; top: 0; bottom: 0; width: 150px; height: 100%; transform: translateX(-50%); opacity: .92; }

/* Traffic */
.vehicle { position: absolute; left: 0; animation: drive var(--dur) linear var(--delay) infinite; will-change: transform; }
.vehicle svg { display: block; overflow: visible; }
.v-jeep { width: 250px; } .v-trike { width: 118px; } .v-car { width: 168px; }
.vehicle .flip { transform: scaleX(-1); }
@keyframes drive { from { transform: translateX(var(--from)); } to { transform: translateX(var(--to)); } }

.scene-fg { position: absolute; inset: 0 0 0 0; z-index: 20; pointer-events: none; overflow: hidden; container-type: inline-size; }

.walker { position: absolute; left: 0; margin-left: calc(-11px * var(--s)); animation: go var(--dur) linear var(--delay) infinite; will-change: transform, opacity; }
/* Sprites are drawn at 40px; scale them with the building */
.walker :deep(.sprite), .pw-idle { height: calc(40px * var(--s)); }
@keyframes go {
  0% { transform: translateX(var(--from)); opacity: 1; }
  80% { transform: translateX(var(--to)); opacity: 1; }
  86%, 100% { transform: translateX(var(--to)); opacity: 0; }
}

.bubble {
  position: absolute; bottom: calc(100% + 7px); left: 50%; translate: -50% 0; white-space: nowrap;
  background: var(--bubble-bg); color: var(--bubble-fg);
  font: 600 calc(12px * 1.2)/1 var(--font-sans); padding: 9px 13px; border-radius: 14px;
  box-shadow: 0 4px 14px rgb(0 0 0 / .18); opacity: 0; animation: say var(--dur) ease-out var(--delay) infinite;
}
.bubble::after { content: ''; position: absolute; left: 50%; top: 100%; margin-left: -5px; border: 5px solid transparent; border-top-color: var(--bubble-bg); }
.bubble.high { bottom: calc(100% + 34px); }
.bubble.shout { font-weight: 900; font-size: 18px; letter-spacing: .5px; color: #f5543d; }
/* Josh: walks in, stops for a while to introduce himself, then carries on to the entrance */
.walker.pausing { animation-name: go-pause; }
@keyframes go-pause {
  0% { transform: translateX(var(--from)); opacity: 1; }
  38%, 62% { transform: translateX(var(--mid)); opacity: 1; }
  86% { transform: translateX(var(--to)); opacity: 1; }
  91%, 100% { transform: translateX(var(--to)); opacity: 0; }
}
.pw-walk, .pw-idle { animation: var(--dur) linear var(--delay) infinite; }
.pw-walk { animation-name: pw-walk; }
.pw-idle { position: absolute; left: 0; bottom: 0; animation-name: pw-idle; }
@keyframes pw-walk { 0%, 37.5% { opacity: 1; } 38%, 62% { opacity: 0; } 62.5%, 100% { opacity: 1; } }
@keyframes pw-idle { 0%, 37.5% { opacity: 0; } 38%, 62% { opacity: 1; } 62.5%, 100% { opacity: 0; } }
.bubble.paused { animation-name: say-pause; }
@keyframes say-pause {
  0%, 39% { opacity: 0; transform: translateY(4px) scale(.9); }
  42%, 59% { opacity: 1; transform: translateY(0) scale(1); }
  63%, 100% { opacity: 0; transform: translateY(-3px) scale(.95); }
}
@keyframes say {
  0%, 29% { opacity: 0; transform: translateY(4px) scale(.9); }
  33%, 55% { opacity: 1; transform: translateY(0) scale(1); }
  59%, 100% { opacity: 0; transform: translateY(-3px) scale(.95); }
}

.cart { position: absolute; bottom: calc(var(--road-h) + 4px * var(--s)); margin-left: calc(-34px * var(--s)); width: calc(64px * var(--s)); z-index: 5; }
.cart-svg { width: calc(64px * var(--s)); display: block; }
.cart > .vendor { position: absolute; right: calc(-16px * var(--s)); bottom: calc(2px * var(--s)); z-index: -1; height: calc(38px * var(--s)); }
.cart-label {
  position: absolute; left: 50%; bottom: calc(100% + 4px); translate: -50% 0; white-space: nowrap;
  font: 800 12px/1 var(--font-sans); letter-spacing: .6px; color: #fff; background: #f5543d; padding: 5px 9px; border-radius: 999px;
}
.steam { position: absolute; left: calc(28px * var(--s)); top: calc(8px * var(--s)); width: calc(6px * var(--s)); height: calc(6px * var(--s)); border-radius: 50%; background: #fff; opacity: 0; animation: steam 2.8s ease-out infinite; }
.steam.s2 { left: calc(36px * var(--s)); animation-delay: -1.4s; }
@keyframes steam { 0% { opacity: .7; transform: translateY(0) scale(.6); } 100% { opacity: 0; transform: translateY(calc(-16px * var(--s))) scale(1.6); } }

@media (prefers-reduced-motion: reduce) {
  .cloud, .steam { animation: none; }
  .walker, .vehicle { display: none; }
}
</style>

<!-- Unscoped: theme overrides keyed off the global .dark class -->
<style>
.dark .scene-bg,
.dark .scene-fg {
  --sky-top: #131416; --sky-bot: #1e1f22;
  --wall: #3b3d43; --panel: #232428; --frame: #4a4c53; --win: #34506b; --win-lit: #e8d58a;
  --door: #1b1c1f; --step: #4e5058; --ground: #2b2d31; --lawn: #2f5a38; --trunk: #5b4630;
  --leaf-a: #2f6a3a; --leaf-b: #25552e; --tank: #6c7078; --cloud: #fff; --cloud-o: .07;
  --bubble-bg: #313338; --bubble-fg: #f2f3f5;
  --mt-a: #1d2030; --mt-b: #262b3c; --sky-bldg: #272c3e; --sky-win: #3b4466;
  --road: #17181b; --curb: #3d3f46; --lane: #5c5f68; --pole: #8a8f99; --lamp: #f5e6a0; --tile: #34363c;
}
.dark .scene-bg .canopy { fill: #dbdee1; }
</style>
