<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { assetsApi } from '@/services/api'
import BookingModal from '@/components/BookingModal.vue'
import OfficeMap from '@/components/OfficeMap.vue'
import CalendarWidget from '@/components/CalendarWidget.vue'

const authStore = useAuthStore()

interface Asset {
  id: number
  codice_univoco: string
  id_tipo: number
  codice_tipo: string
  tipo_descrizione: string
  stato_attuale: string
  stato_effettivo?: string
}

const assets = ref<Asset[]>([])
const selectedDate = ref<string>(new Date().toISOString().split('T')[0] as string)
const loading = ref(true)
const selectedAsset = ref<Asset | null>(null)
const showBookingModal = ref(false)

async function loadAssets() {
  loading.value = true
  try {
    const response = await assetsApi.getAll({ data: selectedDate.value })
    assets.value = response.data
  } catch (err) {
    console.error(err)
  } finally {
    loading.value = false
  }
}

watch(selectedDate, () => {
  loadAssets()
})

function openBooking(asset: Asset) {
  selectedAsset.value = asset
  showBookingModal.value = true
}

async function onBookingCreated() {
  showBookingModal.value = false
  selectedAsset.value = null
  await loadAssets()
}

onMounted(loadAssets)
</script>

<template>
  <div class="min-h-screen bg-black p-6 md:p-10 font-sans text-gray-300">
    
    <div class="mb-10">
      <div class="flex items-center gap-2 mb-2">
        <span class="w-2 h-2 bg-emerald-500 rounded-full shadow-[0_0_8px_#10b981] animate-pulse"></span>
        <span class="text-[10px] font-black text-emerald-500 uppercase tracking-[0.2em]">Live Asset Mapping</span>
      </div>
      <h1 class="text-4xl font-extrabold text-white tracking-tight italic uppercase">Mappa Interattiva</h1>
      <p class="text-gray-500 text-lg mt-1 font-medium italic">Seleziona una postazione e verifica la disponibilità in tempo reale.</p>
    </div>

    <div class="flex flex-col lg:flex-row gap-10 items-start">
      
      <div class="w-full lg:w-[350px] shrink-0 lg:sticky lg:top-10 z-20 space-y-6">
        
        <div class="bg-zinc-900/40 backdrop-blur-xl border border-white/5 rounded-[2rem] p-2 shadow-2xl">
          <CalendarWidget v-model="selectedDate" />
        </div>
        
        <div class="bg-zinc-800/20 backdrop-blur-2xl border border-white/10 rounded-[2rem] p-8 shadow-3xl relative overflow-hidden">
          <div class="absolute -right-8 -top-8 w-24 h-24 bg-sky-500/5 rounded-full blur-3xl"></div>
          
          <h3 class="text-[11px] font-black text-zinc-500 uppercase tracking-[0.2em] mb-6 flex items-center gap-2">
            <span class="w-1 h-4 bg-sky-500 rounded-full"></span>
            Parametri Sessione
          </h3>
          
          <div class="space-y-4">
            <div class="p-4 bg-black/40 rounded-2xl border border-white/5 group hover:border-white/10 transition-colors">
              <p class="text-[9px] font-black text-zinc-600 uppercase tracking-widest mb-1">Data Operativa</p>
              <p class="text-xl font-black text-sky-400 font-mono italic">
                {{ selectedDate.split('-').reverse().join(' / ') }}
              </p>
            </div>
            
            <div class="p-4 bg-black/40 rounded-2xl border border-white/5">
              <p class="text-[9px] font-black text-zinc-600 uppercase tracking-widest mb-3">Legenda Stato</p>
              <div class="space-y-2">
                <div class="flex items-center gap-3">
                  <span class="w-2 h-2 rounded-full bg-emerald-500 shadow-[0_0_5px_#10b981]"></span>
                  <span class="text-[10px] font-bold text-zinc-400 uppercase tracking-tighter">Disponibile</span>
                </div>
                <div class="flex items-center gap-3">
                  <span class="w-2 h-2 rounded-full bg-red-600 shadow-[0_0_5px_#dc2626]"></span>
                  <span class="text-[10px] font-bold text-zinc-400 uppercase tracking-tighter">Occupato / Riservato</span>
                </div>
                <div class="flex items-center gap-3">
                  <span class="w-2 h-2 rounded-full bg-zinc-700"></span>
                  <span class="text-[10px] font-bold text-zinc-400 uppercase tracking-tighter">Non Prenotabile</span>
                </div>
              </div>
            </div>
          </div>

          <p class="mt-6 text-[10px] text-zinc-600 italic font-medium leading-relaxed px-2">
            * Clicca su un elemento della mappa per inizializzare la procedura di prenotazione.
          </p>
        </div>
      </div>
      
      <div class="flex-1 min-w-0 w-full relative">
        <div class="bg-zinc-900/30 backdrop-blur-sm border border-white/5 rounded-[3rem] overflow-hidden shadow-3xl min-h-[700px] flex items-center justify-center relative">
          
          <div v-if="loading" class="absolute inset-0 z-30 flex flex-col items-center justify-center bg-black/60 backdrop-blur-md">
            <div class="relative w-16 h-16">
              <div class="absolute inset-0 border-4 border-emerald-500/10 rounded-full"></div>
              <div class="absolute inset-0 border-4 border-t-emerald-500 rounded-full animate-spin"></div>
            </div>
            <p class="mt-6 text-[10px] font-black text-emerald-500 uppercase tracking-[0.4em] animate-pulse">Syncing Vector Data...</p>
          </div>
          
          <div class="w-full h-full p-4 md:p-8 group">
            <OfficeMap 
              v-if="!loading || assets.length > 0" 
              :assets="assets" 
              @select="openBooking" 
            />
          </div>

          <div class="absolute bottom-6 right-10 pointer-events-none opacity-20">
            <p class="text-[40px] font-black italic text-white/10 select-none uppercase tracking-tighter text-right leading-none">
              Z-Volta<br/>HQ Layout
            </p>
          </div>
        </div>
      </div>
    </div>

    <BookingModal
      v-if="showBookingModal && selectedAsset"
      :asset="selectedAsset"
      :date="selectedDate"
      @close="showBookingModal = false"
      @created="onBookingCreated"
    />
  </div>
</template>

<style scoped>
/* Rende il calendario più coerente con lo stile dark se non ha già i suoi stili */
:deep(.calendar-widget-container) {
  background: transparent !important;
  color: white !important;
}

/* Scrollbar invisibile per aree mappa se necessario */
::-webkit-scrollbar {
  width: 0px;
}
</style>