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
  <div class="min-h-screen">
    <!-- Header -->
    <div class="mb-8">
      <h1 class="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-emerald-400 via-sky-400 to-indigo-500 mb-2">
        Mappa e Prenotazioni Interattiva
      </h1>
      <p class="text-gray-400">Esplora l'ufficio virtuale, seleziona una data dal calendario e prenota la tua postazione ideale.</p>
    </div>

    <div class="flex flex-col lg:flex-row gap-8 items-start">
      <!-- Sidebar / Calendar -->
      <div class="w-full lg:w-[320px] shrink-0 sticky top-6 z-20">
        <CalendarWidget v-model="selectedDate" />
        
        <!-- Info Card -->
        <div class="mt-6 bg-gray-900/60 border border-gray-800 rounded-3xl p-5 backdrop-blur-xl shadow-2xl">
          <h3 class="text-sm font-semibold text-gray-300 mb-4 flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-sky-400"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
            Indicazioni
          </h3>
          <div class="flex justify-between items-center mb-3 p-3 bg-gray-800/50 rounded-xl">
            <span class="text-xs text-gray-400 uppercase font-medium">Data:</span>
            <span class="text-sm font-bold text-sky-400">{{ selectedDate.split('-').reverse().join('/') }}</span>
          </div>
          <p class="text-xs text-gray-500 leading-relaxed">
            Seleziona una postazione libera direttamente dalla mappa vettoriale qui a fianco.
          </p>
        </div>
      </div>
      
      <!-- Main Content / Vector Map -->
      <div class="flex-1 min-w-0 w-full z-10">
        <div v-if="loading" class="flex flex-col items-center justify-center py-32 bg-gray-900/40 border border-gray-800 rounded-3xl backdrop-blur-xl h-[800px]">
          <div class="w-12 h-12 border-4 border-sky-500/30 border-t-sky-500 rounded-full animate-spin mb-4"></div>
          <p class="text-sky-400 font-bold animate-pulse">Analisi della disponibilità...</p>
        </div>
        
        <OfficeMap 
          v-else 
          :assets="assets" 
          @select="openBooking" 
        />
      </div>
    </div>

    <!-- Booking Modal -->
    <BookingModal
      v-if="showBookingModal && selectedAsset"
      :asset="selectedAsset"
      :date="selectedDate"
      @close="showBookingModal = false"
      @created="onBookingCreated"
    />
  </div>
</template>
