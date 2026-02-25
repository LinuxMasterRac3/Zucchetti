<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'

const props = defineProps<{
  assets: any[]
}>()

const emit = defineEmits<{
  (e: 'select', asset: any): void
}>()

// State per Zoom & Pan
const svgContainer = ref<HTMLElement | null>(null)
const transform = ref({ x: 0, y: 0, scale: 0.8 }) // Initial scale
const isDragging = ref(false)
const dragStart = ref({ x: 0, y: 0 })

// State per Selezione Piano
const floors = [
  { id: 1, name: 'Piano Terra', desc: 'Reception & Sale Riunioni' },
  { id: 2, name: 'Piano 1', desc: 'Area Operativa' },
  { id: 3, name: 'Parcheggio Esterno', desc: 'Posti Auto' }
]
const activeFloor = ref(1)

// Split assets by floor for logic
const floorAssets = computed(() => {
  if (activeFloor.value === 1) {
    return props.assets.filter(a => a.codice_tipo === 'B') // Piano Terra = Sale Riunioni B
  } else if (activeFloor.value === 2) {
    return props.assets.filter(a => ['A', 'A2', 'C'].includes(a.codice_tipo)) // Piano 1 = Scrivanie
  } else {
    return props.assets.filter(a => a.codice_tipo === 'C') // Parcheggio = Auto C
  }
})

const desksA = computed(() => floorAssets.value.filter(a => a.codice_tipo === 'A'))
const desksA2 = computed(() => floorAssets.value.filter(a => a.codice_tipo === 'A2'))
const roomsB = computed(() => floorAssets.value.filter(a => a.codice_tipo === 'B'))
const spotsC = computed(() => floorAssets.value.filter(a => a.codice_tipo === 'C'))

const positionedDesksA = computed(() => {
  return desksA.value.map((asset, i) => {
    let x = 0, y = 0;
    if (i < 8) { // Room 1 (NW)
      x = 120 + (i % 2) * 180;
      y = 120 + Math.floor(i / 2) * 110;
    } else if (i < 16) { // Room 2 (NC)
      let j = i - 8;
      x = 550 + (j % 2) * 180;
      y = 120 + Math.floor(j / 2) * 110;
    } else { // Room 3 (NE)
      let j = i - 16;
      x = 1000 + (j % 2) * 180;
      y = 120 + Math.floor(j / 2) * 110;
    }
    return { ...asset, x, y };
  });
});

const positionedDesksA2 = computed(() => {
  return desksA2.value.map((asset, i) => {
    let x = 0, y = 0;
    if (i < 10) { // Room 4 (SW)
      x = 110 + (i % 2) * 200;
      y = 750 + Math.floor(i / 2) * 95;
    } else if (i < 20) { // Room 5 (SC)
      let j = i - 10;
      x = 530 + (j % 2) * 200;
      y = 750 + Math.floor(j / 2) * 95;
    } else { // Room 6 (SE)
      let j = i - 20;
      x = 960 + (j % 2) * 200;
      y = 750 + Math.floor(j / 2) * 95;
    }
    return { ...asset, x, y };
  });
});

const positionedRoomsB = computed(() => {
  return roomsB.value.map((asset, i) => {
    let x = 0, y = 0;
    if (i === 0) { x = 130; y = 140; }
    else if (i === 1) { x = 130; y = 540; }
    else if (i === 2) { x = 130; y = 940; }
    else if (i === 3) { x = 880; y = 240; }
    else if (i === 4) { x = 880; y = 840; }
    return { ...asset, x, y };
  });
});

function isBookable(asset: any) {
  const status = asset.stato_effettivo || asset.stato_attuale
  return status === 'disponibile'
}

function getAssetColor(asset: any) {
  const status = asset.stato_effettivo || asset.stato_attuale
  switch (status) {
    case 'disponibile': return { fill: 'rgba(16, 185, 129, 0.15)', stroke: 'rgba(16, 185, 129, 0.8)', text: '#34d399' }
    case 'occupato': return { fill: 'rgba(239, 68, 68, 0.15)', stroke: 'rgba(239, 68, 68, 0.8)', text: '#f87171' }
    case 'non_prenotabile': return { fill: 'rgba(55, 65, 81, 0.4)', stroke: 'rgba(75, 85, 99, 0.8)', text: '#9ca3af' }
    default: return { fill: 'rgba(55, 65, 81, 0.2)', stroke: 'rgba(75, 85, 99, 0.5)', text: '#6b7280' }
  }
}

function onClick(asset: any) {
  if (isBookable(asset)) {
    emit('select', asset)
  }
}

// --- Zoom & Pan Logic ---
function onWheel(e: WheelEvent) {
  e.preventDefault()
  const zoomDirection = e.deltaY < 0 ? 1 : -1
  const zoomFactor = 1.1
  let newScale = transform.value.scale * (zoomDirection > 0 ? zoomFactor : 1 / zoomFactor)
  
  // Clamping
  newScale = Math.max(0.3, Math.min(newScale, 3))
  
  transform.value = {
    ...transform.value,
    scale: newScale
  }
}

function onMouseDown(e: MouseEvent) {
  isDragging.value = true
  dragStart.value = { x: e.clientX - transform.value.x, y: e.clientY - transform.value.y }
  document.body.style.cursor = 'grabbing'
}

function onMouseMove(e: MouseEvent) {
  if (!isDragging.value) return
  transform.value = {
    ...transform.value,
    x: e.clientX - dragStart.value.x,
    y: e.clientY - dragStart.value.y
  }
}

function onMouseUp() {
  isDragging.value = false
  document.body.style.cursor = 'default'
}

function resetView() {
  transform.value = { x: 0, y: 0, scale: 0.8 }
}

onMounted(() => {
  window.addEventListener('mouseup', onMouseUp)
  window.addEventListener('mousemove', onMouseMove)
})

onUnmounted(() => {
  window.removeEventListener('mouseup', onMouseUp)
  window.removeEventListener('mousemove', onMouseMove)
})
</script>

<template>
  <div class="relative w-full h-[800px] flex flex-col bg-gray-900/60 backdrop-blur-2xl rounded-3xl border border-gray-800 shadow-[0_0_50px_rgba(0,0,0,0.4)] overflow-hidden">
    
    <!-- Top Bar Controls -->
    <div class="absolute top-0 left-0 right-0 z-20 flex justify-between items-center p-5 bg-gradient-to-b from-gray-900/90 to-transparent pointer-events-none">
      
      <!-- Title -->
      <h3 class="text-white font-bold text-xl tracking-wide flex items-center gap-3 drop-shadow-lg opacity-90 pointer-events-auto">
        <div class="w-10 h-10 rounded-xl bg-sky-500/20 flex items-center justify-center border border-sky-500/30">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-sky-400"><path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14"/><circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/></svg>
        </div>
        Virtual Office Explorer
      </h3>

      <!-- Controls Right -->
      <div class="flex items-center gap-4 pointer-events-auto">
        <!-- Floor Selector -->
        <div class="flex bg-gray-800/80 p-1 rounded-xl border border-gray-700/50 backdrop-blur-md">
          <button 
            v-for="floor in floors" 
            :key="floor.id"
            @click="activeFloor = floor.id; resetView()"
            class="px-4 py-2 text-sm font-semibold rounded-lg transition-all duration-300"
            :class="activeFloor === floor.id ? 'bg-sky-500 text-white shadow-lg' : 'text-gray-400 hover:text-white hover:bg-gray-700/50'"
          >
            {{ floor.name }}
          </button>
        </div>

        <button @click="resetView" class="p-3 bg-gray-800/80 hover:bg-gray-700/80 rounded-xl border border-gray-700/50 text-gray-300 transition-colors tooltip backdrop-blur-md" title="Centra Mappa">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
        </button>
      </div>
    </div>

    <!-- Mini Legend -->
    <div class="absolute bottom-6 left-6 z-20 flex items-center gap-4 bg-gray-800/80 border border-gray-700/50 px-4 py-3 rounded-2xl backdrop-blur-xl shadow-xl">
      <div class="flex items-center gap-2 text-xs font-semibold text-gray-300 uppercase tracking-wider">
        <span class="w-3 h-3 rounded-full bg-emerald-500/30 border-2 border-emerald-500/80 animate-pulse"></span> Disponibile
      </div>
      <div class="w-px h-4 bg-gray-700"></div>
      <div class="flex items-center gap-2 text-xs font-semibold text-gray-300 uppercase tracking-wider">
        <span class="w-3 h-3 rounded-full bg-red-500/30 border-2 border-red-500/80"></span> Occupata
      </div>
    </div>

    <div class="absolute bottom-6 right-6 z-20 text-gray-500 text-xs font-medium">
      Istruzioni: Scrolla per zoomare, clicca e trascina per spostare la mappa
    </div>

    <!-- Interactive SVG Container area -->
    <div 
      ref="svgContainer"
      class="flex-1 w-full h-full cursor-grab active:cursor-grabbing overflow-hidden relative"
      @wheel.prevent="onWheel"
      @mousedown="onMouseDown"
    >
      <!-- Background Grid Pattern -->
      <div class="absolute inset-0 z-0 opacity-10 pointer-events-none" style="background-image: radial-gradient(circle at center, #94a3b8 1px, transparent 1px); background-size: 40px 40px;"></div>

      <div 
        class="w-full h-full flex items-center justify-center transition-transform duration-75 ease-out"
        :style="{ transform: `translate(${transform.x}px, ${transform.y}px) scale(${transform.scale})` }"
      >
        <svg viewBox="0 0 1400 1300" class="w-[1400px] h-[1300px] drop-shadow-2xl origin-center overflow-visible">
          <!-- Floor Base Plate -->
          <rect x="50" y="50" width="1300" height="1200" rx="30" fill="#0f172a" stroke="#1e293b" stroke-width="8" class="shadow-2xl" />
          
          <!-- Entrance -->
          <path d="M 600 50 L 600 20 L 800 20 L 800 50" fill="none" stroke="#334155" stroke-width="4" />
          <text x="700" y="35" fill="#475569" font-size="14" font-weight="bold" font-family="sans-serif" text-anchor="middle" letter-spacing="2">INGRESSO</text>
          
          <!-- Dynamic Layers based on Active Floor -->
          
          <g v-if="activeFloor === 1" id="floor-1-rooms">
             <!-- Muri Ufficio - Piano Terra -->
             <g id="walls-floor-0" opacity="0.8">
               <!-- Outer walls -->
               <rect x="50" y="50" width="1300" height="1200" fill="none" stroke="#64748b" stroke-width="14" rx="20" />
               
               <!-- Central Corridor Vertical -->
               <line x1="600" y1="50" x2="600" y2="1250" stroke="#64748b" stroke-width="8" />
               <line x1="800" y1="50" x2="800" y2="1250" stroke="#64748b" stroke-width="8" />

               <!-- Meeting Room Splits Left -->
               <line x1="50" y1="450" x2="600" y2="450" stroke="#64748b" stroke-width="8" />
               <line x1="50" y1="850" x2="600" y2="850" stroke="#64748b" stroke-width="8" />

               <!-- Meeting Room Splits Right -->
               <line x1="800" y1="650" x2="1350" y2="650" stroke="#64748b" stroke-width="8" />

               <!-- Ingresso -->
               <rect x="650" y="1240" width="100" height="20" fill="#0f172a" />
               <text x="700" y="1225" fill="#38bdf8" font-size="28" font-weight="900" font-family="sans-serif" text-anchor="middle" letter-spacing="4">RECEPTION</text>
               
               <!-- Desk Reception -->
               <rect x="640" y="1000" width="120" height="40" rx="10" fill="#1e293b" stroke="#38bdf8" stroke-width="3" />
               <text x="700" y="1025" fill="#38bdf8" font-size="14" font-weight="bold" font-family="sans-serif" text-anchor="middle">INFO POINT</text>
               
               <!-- Porte Stanze Sx-->
               <rect x="596" y="250" width="16" height="80" fill="#0f172a" />
               <rect x="596" y="650" width="16" height="80" fill="#0f172a" />
               <rect x="596" y="1050" width="16" height="80" fill="#0f172a" />
               <!-- Porte Stanze Dx-->
               <rect x="788" y="350" width="16" height="80" fill="#0f172a" />
               <rect x="788" y="950" width="16" height="80" fill="#0f172a" />
               
               <!-- Titoli Sale -->
               <text x="325" y="120" fill="#64748b" font-size="24" font-weight="bold" font-family="sans-serif" text-anchor="middle" letter-spacing="2">SALA CONGRESSI A</text>
               <text x="325" y="520" fill="#64748b" font-size="24" font-weight="bold" font-family="sans-serif" text-anchor="middle" letter-spacing="2">SALA CONGRESSI B</text>
               <text x="325" y="920" fill="#64748b" font-size="24" font-weight="bold" font-family="sans-serif" text-anchor="middle" letter-spacing="2">SALA EXECUTIVE</text>
               <text x="1075" y="120" fill="#64748b" font-size="24" font-weight="bold" font-family="sans-serif" text-anchor="middle" letter-spacing="2">SALA FORMAZIONE 1</text>
               <text x="1075" y="720" fill="#64748b" font-size="24" font-weight="bold" font-family="sans-serif" text-anchor="middle" letter-spacing="2">SALA FORMAZIONE 2</text>
             </g>

             <!-- Meeting Rooms Render -->
             <g v-for="(asset, i) in positionedRoomsB" :key="asset.id" 
                :transform="`translate(${asset.x}, ${asset.y})`"
                @click.stop="onClick(asset)"
                class="transition-all duration-300 hover:scale-[1.05] origin-center group"
                :class="{'cursor-pointer': isBookable(asset), 'cursor-not-allowed opacity-80': !isBookable(asset)}"
             >
                <!-- Glass Reflection Effect Layer -->
                <rect x="0" y="0" width="340" height="220" rx="20" fill="#1e293b" :stroke="getAssetColor(asset).stroke" stroke-width="4" />
                <rect x="5" y="5" width="160" height="100" rx="15" fill="rgba(255,255,255,0.02)" pointer-events="none"/>
                
                <!-- Label (Centered) -->
                <text x="170" y="105" font-family="sans-serif" font-size="28" font-weight="900" text-anchor="middle" :fill="getAssetColor(asset).text">
                  {{ asset.codice_univoco }}
                </text>
                <text x="170" y="130" font-family="sans-serif" font-size="16" font-weight="600" text-anchor="middle" fill="#fff" opacity="0.8">
                  {{ isBookable(asset) ? 'SALA DISPONIBILE' : 'OCCUPATA' }}
                </text>
             </g>
          </g>

          <g v-if="activeFloor === 2" id="floor-2-desks">
            <!-- Muri Ufficio - Piano 1 -->
            <g id="walls-floor-1" opacity="0.8">
              <rect x="50" y="50" width="1300" height="1200" fill="none" stroke="#64748b" stroke-width="14" rx="20" />
              <!-- Central Corridor -->
              <line x1="50" y1="600" x2="1350" y2="600" stroke="#64748b" stroke-width="8" />
              <line x1="50" y1="700" x2="1350" y2="700" stroke="#64748b" stroke-width="8" />
              <!-- Vertical Walls (Nord e Sud) -->
              <line x1="480" y1="50" x2="480" y2="600" stroke="#64748b" stroke-width="8" />
              <line x1="910" y1="50" x2="910" y2="600" stroke="#64748b" stroke-width="8" />
              <line x1="480" y1="700" x2="480" y2="1250" stroke="#64748b" stroke-width="8" />
              <line x1="910" y1="700" x2="910" y2="1250" stroke="#64748b" stroke-width="8" />
              
              <!-- Porte sul corridoio -->
              <rect x="230" y="596" width="80" height="16" fill="#0f172a" />
              <rect x="660" y="596" width="80" height="16" fill="#0f172a" />
              <rect x="1090" y="596" width="80" height="16" fill="#0f172a" />
              <rect x="230" y="696" width="80" height="16" fill="#0f172a" />
              <rect x="660" y="696" width="80" height="16" fill="#0f172a" />
              <rect x="1090" y="696" width="80" height="16" fill="#0f172a" />

              <!-- Testi Stanze -->
              <text x="265" y="550" fill="#334155" opacity="0.6" font-size="28" font-weight="900" font-family="sans-serif" text-anchor="middle" letter-spacing="4">UFFICIO HR</text>
              <text x="695" y="550" fill="#334155" opacity="0.6" font-size="28" font-weight="900" font-family="sans-serif" text-anchor="middle" letter-spacing="4">AMMINISTRAZIONE</text>
              <text x="1125" y="550" fill="#334155" opacity="0.6" font-size="28" font-weight="900" font-family="sans-serif" text-anchor="middle" letter-spacing="4">MARKETING</text>
              <text x="265" y="1180" fill="#334155" opacity="0.6" font-size="28" font-weight="900" font-family="sans-serif" text-anchor="middle" letter-spacing="4">SVILUPPO IT A</text>
              <text x="695" y="1180" fill="#334155" opacity="0.6" font-size="28" font-weight="900" font-family="sans-serif" text-anchor="middle" letter-spacing="4">SVILUPPO IT B</text>
              <text x="1125" y="1180" fill="#334155" opacity="0.6" font-size="28" font-weight="900" font-family="sans-serif" text-anchor="middle" letter-spacing="4">DESIGN & UI/UX</text>
            </g>

            <text x="700" y="658" fill="#475569" font-size="20" font-weight="bold" font-family="sans-serif" text-anchor="middle" letter-spacing="8">CORRIDOIO PRINCIPALE / ASCENSORI</text>

            <!-- Regular Desks (A) Layout: Top Half -->
            <g v-for="(asset, i) in positionedDesksA" :key="asset.id" 
               :transform="`translate(${asset.x}, ${asset.y})`"
               @click.stop="onClick(asset)"
               class="transition-all duration-300 hover:scale-[1.1] origin-center group"
               :class="{'cursor-pointer': isBookable(asset), 'cursor-not-allowed opacity-80': !isBookable(asset)}"
            >
              <rect x="4" y="4" width="102" height="62" rx="8" fill="rgba(0,0,0,0.4)" filter="blur(4px)" class="group-hover:opacity-100 opacity-60 transition-opacity" />
              <rect x="0" y="0" width="110" height="70" rx="12" :fill="getAssetColor(asset).fill" :stroke="getAssetColor(asset).stroke" stroke-width="3" />
              
              <!-- Cleaned up labels -->
              <text x="55" y="32" font-family="sans-serif" font-size="18" font-weight="800" text-anchor="middle" :fill="getAssetColor(asset).text">{{ asset.codice_univoco }}</text>
              <text x="55" y="52" font-family="sans-serif" font-size="10" font-weight="600" text-anchor="middle" fill="#fff" opacity="0.9">{{ isBookable(asset) ? 'LIBERO' : 'OCCUPATO' }}</text>
            </g>

            <!-- Monitor Desks (A2) Layout: Bottom Half -->
            <g v-for="(asset, i) in positionedDesksA2" :key="asset.id" 
               :transform="`translate(${asset.x}, ${asset.y})`"
               @click.stop="onClick(asset)"
               class="transition-all duration-300 hover:scale-[1.1] origin-center group"
               :class="{'cursor-pointer': isBookable(asset), 'cursor-not-allowed opacity-80': !isBookable(asset)}"
            >
              <rect x="4" y="4" width="122" height="72" rx="10" fill="rgba(0,0,0,0.4)" filter="blur(6px)" class="group-hover:opacity-100 opacity-60 transition-opacity" />
              <rect x="0" y="0" width="130" height="80" rx="16" :fill="getAssetColor(asset).fill" :stroke="getAssetColor(asset).stroke" stroke-width="3" />
              
              <!-- Cleaned up labels -->
              <text x="65" y="38" font-family="sans-serif" font-size="20" font-weight="900" text-anchor="middle" :fill="getAssetColor(asset).text">{{ asset.codice_univoco }}</text>
              <text x="65" y="60" font-family="sans-serif" font-size="11" font-weight="600" text-anchor="middle" fill="#fff" opacity="0.9">{{ isBookable(asset) ? 'LIBERO' : 'OCCUPATO' }}</text>
            </g>
          </g>
          <g v-if="activeFloor === 3" id="floor-3-parking">
            <!-- Asphalt Base Plate overlay -->
            <rect x="50" y="50" width="1300" height="1200" rx="30" fill="#1e293b" />
            <!-- Parking stripes central lane -->
            <line x1="200" y1="650" x2="1200" y2="650" stroke="#fcd34d" stroke-width="6" stroke-dasharray="30 20"/>
            
            <text x="700" y="120" fill="#475569" font-size="32" font-weight="900" font-family="sans-serif" text-anchor="middle" letter-spacing="4">PARCHEGGIO AZIENDALE ESTERNO</text>

            <!-- Plants/Decorations Area -->
            <rect x="50" y="1180" width="1300" height="70" fill="#064e3b" opacity="0.6"/>
            <text x="700" y="1225" fill="#10b981" font-size="20" font-weight="bold" font-family="sans-serif" text-anchor="middle" letter-spacing="8">AREA VERDE ALBERATA</text>

            <!-- Parking Spots (C) Layout: Top and Bottom Rows -->
            <g v-for="(asset, i) in spotsC" :key="asset.id" 
               :transform="`translate(${240 + (i % 5) * 200}, ${i < 5 ? 350 : 750})`"
               @click.stop="onClick(asset)"
               class="transition-all duration-300 hover:scale-[1.05] origin-center group"
               :class="{'cursor-pointer': isBookable(asset), 'cursor-not-allowed opacity-80': !isBookable(asset)}"
            >
              <!-- Parking Lines Left/Right -->
              <line x1="0" y1="0" x2="0" y2="160" stroke="#cbd5e1" stroke-width="4" opacity="0.5"/>
              <line x1="120" y1="0" x2="120" y2="160" stroke="#cbd5e1" stroke-width="4" opacity="0.5"/>
              
              <!-- Back Wall Stopper -->
              <rect x="0" :y="i < 8 ? 0 : 155" width="120" height="5" fill="#fcd34d" opacity="0.8"/>
              
              <!-- Car Visual Status -->
              <g v-if="!isBookable(asset)" :transform="i < 8 ? 'translate(20, 30)' : 'translate(20, 10)'">
                <!-- Car SVG Drawing -->
                <rect x="0" y="0" width="80" height="120" rx="20" :fill="getAssetColor(asset).stroke" opacity="0.8" class="shadow-2xl"/>
                <!-- Windshields -->
                <rect x="10" y="25" width="60" height="20" rx="4" fill="#0f172a" opacity="0.9" />
                <rect x="10" y="90" width="60" height="15" rx="4" fill="#0f172a" opacity="0.9" />
              </g>

              <rect x="0" y="0" width="120" height="160" :fill="getAssetColor(asset).fill" />

              <text x="60" y="80" font-family="sans-serif" font-size="28" font-weight="900" text-anchor="middle" :fill="getAssetColor(asset).text">
                {{ asset.codice_univoco }}
              </text>
              <text x="60" y="100" font-family="sans-serif" font-size="12" font-weight="600" text-anchor="middle" fill="#fff" opacity="0.9">
                {{ isBookable(asset) ? 'LIBERO' : 'OCCUPATO' }}
              </text>
            </g>
          </g>
        </svg>
      </div>
    </div>
  </div>
</template>

<style scoped>
svg text {
  pointer-events: none;
  user-select: none;
}
.origin-center {
  transform-origin: center center;
}
/* Hide scrollbar for smooth visual map */
::-webkit-scrollbar {
  width: 0px;
  background: transparent;
}
</style>
