<script setup lang="ts">
import { ref } from 'vue'
import { bookingsApi } from '@/services/api'

const props = defineProps<{
  asset: {
    id: number
    codice_univoco: string
    codice_tipo: string
    tipo_descrizione: string
  }
  date: string
}>()

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'created'): void
}>()

const oraInizio = ref('09:00')
const oraFine = ref('18:00')
const loading = ref(false)
const error = ref<string | null>(null)
const success = ref(false)

async function handleSubmit() {
  loading.value = true
  error.value = null
  try {
    await bookingsApi.create({
      id_asset: props.asset.id,
      data_prenotazione: props.date,
      ora_inizio: oraInizio.value,
      ora_fine: oraFine.value,
    })
    success.value = true
    setTimeout(() => emit('created'), 1200)
  } catch (err: any) {
    error.value = err.response?.data?.message || 'Errore nella prenotazione'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <!-- Overlay -->
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="emit('close')">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>

    <!-- Modal -->
    <div class="relative bg-gray-900 rounded-2xl border border-gray-800/50 shadow-2xl shadow-black/40 w-full max-w-md">
      <!-- Header -->
      <div class="flex items-center justify-between p-6 border-b border-gray-800/50">
        <div>
          <h2 class="text-lg font-semibold text-gray-100">Nuova Prenotazione</h2>
          <p class="text-sm text-gray-500 mt-0.5">{{ asset.codice_univoco }} – {{ asset.tipo_descrizione }}</p>
        </div>
        <button @click="emit('close')" class="text-gray-500 hover:text-gray-300 transition-colors text-xl cursor-pointer">✕</button>
      </div>

      <!-- Success -->
      <div v-if="success" class="p-8 text-center">
        <div class="text-5xl mb-3">✅</div>
        <p class="text-lg font-semibold text-emerald-400">Prenotazione confermata!</p>
      </div>

      <!-- Form -->
      <form v-else @submit.prevent="handleSubmit" class="p-6 space-y-5">
        <!-- Error -->
        <div v-if="error" class="px-4 py-3 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 text-sm">
          {{ error }}
        </div>

        <!-- Asset Info -->
        <div class="bg-gray-800/30 rounded-xl p-4 flex items-center gap-3">
          <div class="w-12 h-12 rounded-xl flex items-center justify-center text-lg font-bold"
            :class="{
              'bg-sky-500/10 text-sky-400': asset.codice_tipo === 'A',
              'bg-indigo-500/10 text-indigo-400': asset.codice_tipo === 'A2',
              'bg-emerald-500/10 text-emerald-400': asset.codice_tipo === 'B',
              'bg-amber-500/10 text-amber-400': asset.codice_tipo === 'C',
            }">
            {{ asset.codice_tipo }}
          </div>
          <div>
            <p class="text-sm font-semibold text-gray-200">{{ asset.codice_univoco }}</p>
            <p class="text-xs text-gray-500">{{ asset.tipo_descrizione }}</p>
          </div>
        </div>

        <!-- Date -->
        <div>
          <label class="block text-sm font-medium text-gray-400 mb-2">Data</label>
          <p class="px-4 py-3 bg-gray-800/50 border border-gray-700/50 rounded-xl text-gray-300 text-sm">
            📅 {{ date }}
          </p>
        </div>

        <!-- Time -->
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-400 mb-2">Ora inizio</label>
            <input
              v-model="oraInizio"
              type="time"
              required
              class="w-full px-4 py-3 bg-gray-800/50 border border-gray-700/50 rounded-xl text-gray-200 focus:outline-none focus:ring-2 focus:ring-sky-500/50 text-sm"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-400 mb-2">Ora fine</label>
            <input
              v-model="oraFine"
              type="time"
              required
              class="w-full px-4 py-3 bg-gray-800/50 border border-gray-700/50 rounded-xl text-gray-200 focus:outline-none focus:ring-2 focus:ring-sky-500/50 text-sm"
            />
          </div>
        </div>

        <!-- Submit -->
        <button
          type="submit"
          :disabled="loading"
          class="w-full py-3 bg-gradient-to-r from-sky-500 to-indigo-600 text-white font-semibold rounded-xl hover:from-sky-400 hover:to-indigo-500 transition-all duration-200 disabled:opacity-50 shadow-lg shadow-sky-500/20 cursor-pointer"
        >
          <span v-if="loading" class="animate-pulse">Prenotazione in corso...</span>
          <span v-else>Conferma Prenotazione</span>
        </button>
      </form>
    </div>
  </div>
</template>
