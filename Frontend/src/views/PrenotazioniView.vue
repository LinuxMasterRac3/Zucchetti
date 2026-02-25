<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { bookingsApi } from '@/services/api'

const authStore = useAuthStore()

interface Booking {
  id: number
  id_utente: number
  id_asset: number
  data_prenotazione: string
  ora_inizio: string
  ora_fine: string
  timestamp_creazione: string
  modifiche_counter: number
  stato_prenotazione: string
  utente_nome: string
  utente_cognome: string
  username: string
  codice_univoco: string
  codice_tipo: string
  tipo_descrizione: string
}

const bookings = ref<Booking[]>([])
const loading = ref(true)
const filterStato = ref('attiva')
const actionLoading = ref<number | null>(null)
const actionError = ref<string | null>(null)
const actionSuccess = ref<string | null>(null)

// Edit state
const editingBooking = ref<Booking | null>(null)
const editData = ref({ data_prenotazione: '', ora_inizio: '', ora_fine: '' })

const filteredBookings = computed(() => {
  if (filterStato.value === 'tutte') return bookings.value
  return bookings.value.filter(b => b.stato_prenotazione === filterStato.value)
})

async function loadBookings() {
  loading.value = true
  try {
    const response = await bookingsApi.getAll()
    bookings.value = response.data
  } catch (err) {
    console.error(err)
  } finally {
    loading.value = false
  }
}

async function cancelBooking(id: number) {
  if (!confirm('Sei sicuro di voler cancellare questa prenotazione?')) return
  actionLoading.value = id
  actionError.value = null
  try {
    await bookingsApi.cancel(id)
    actionSuccess.value = 'Prenotazione cancellata'
    setTimeout(() => actionSuccess.value = null, 2000)
    await loadBookings()
  } catch (err: any) {
    actionError.value = err.response?.data?.message || 'Errore'
  } finally {
    actionLoading.value = null
  }
}

function startEdit(booking: Booking) {
  editingBooking.value = booking
  editData.value = {
    data_prenotazione: booking.data_prenotazione,
    ora_inizio: booking.ora_inizio.substring(0, 5),
    ora_fine: booking.ora_fine.substring(0, 5),
  }
}

async function saveEdit() {
  if (!editingBooking.value) return
  actionLoading.value = editingBooking.value.id
  actionError.value = null
  try {
    const response = await bookingsApi.update(editingBooking.value.id, editData.value)
    actionSuccess.value = `Modificata! ${response.data.modifiche_rimanenti} modifiche rimanenti`
    setTimeout(() => actionSuccess.value = null, 3000)
    editingBooking.value = null
    await loadBookings()
  } catch (err: any) {
    actionError.value = err.response?.data?.message || 'Errore nella modifica'
  } finally {
    actionLoading.value = null
  }
}

function getStatoBadge(stato: string) {
  switch (stato) {
    case 'attiva': return 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30'
    case 'cancellata': return 'bg-gray-500/20 text-gray-400 border-gray-500/30'
    case 'revocata': return 'bg-red-500/20 text-red-400 border-red-500/30'
    default: return 'bg-gray-500/20 text-gray-400'
  }
}

onMounted(loadBookings)
</script>

<template>
  <div>
    <!-- Header -->
    <div class="flex items-center justify-between mb-8">
      <div>
        <h1 class="text-2xl font-bold text-gray-100">📋 Prenotazioni</h1>
        <p class="text-gray-500 text-sm mt-1">Gestisci le tue prenotazioni</p>
      </div>
      <!-- Filter -->
      <div class="flex gap-2">
        <button
          v-for="stato in ['attiva', 'cancellata', 'revocata', 'tutte']"
          :key="stato"
          @click="filterStato = stato"
          class="px-3 py-1.5 text-xs font-semibold rounded-lg transition-all cursor-pointer capitalize"
          :class="filterStato === stato
            ? 'bg-sky-500/20 text-sky-400 border border-sky-500/30'
            : 'text-gray-500 hover:text-gray-300 border border-gray-800'"
        >
          {{ stato }}
        </button>
      </div>
    </div>

    <!-- Messages -->
    <div v-if="actionError" class="mb-4 px-4 py-3 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 text-sm">
      {{ actionError }}
    </div>
    <div v-if="actionSuccess" class="mb-4 px-4 py-3 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-sm">
      {{ actionSuccess }}
    </div>

    <div v-if="loading" class="text-center py-20 text-gray-500 animate-pulse">Caricamento...</div>

    <!-- Empty State -->
    <div v-else-if="filteredBookings.length === 0" class="text-center py-20 text-gray-600">
      <p class="text-4xl mb-2">📭</p>
      <p>Nessuna prenotazione trovata</p>
      <RouterLink to="/mappa" class="inline-block mt-3 px-4 py-2 bg-sky-500/10 text-sky-400 rounded-xl text-sm font-medium hover:bg-sky-500/20 transition-colors">
        Vai alla mappa →
      </RouterLink>
    </div>

    <!-- Bookings List -->
    <div v-else class="space-y-3">
      <div
        v-for="booking in filteredBookings"
        :key="booking.id"
        class="bg-gray-900/60 backdrop-blur-xl rounded-2xl border border-gray-800/50 p-5"
      >
        <div class="flex items-start gap-4">
          <!-- Asset Type Icon -->
          <div class="w-12 h-12 rounded-xl flex items-center justify-center text-lg font-bold shrink-0"
            :class="{
              'bg-sky-500/10 text-sky-400': booking.codice_tipo === 'A',
              'bg-indigo-500/10 text-indigo-400': booking.codice_tipo === 'A2',
              'bg-emerald-500/10 text-emerald-400': booking.codice_tipo === 'B',
              'bg-amber-500/10 text-amber-400': booking.codice_tipo === 'C',
            }">
            {{ booking.codice_tipo }}
          </div>

          <!-- Info -->
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 mb-1">
              <h3 class="text-sm font-semibold text-gray-200">{{ booking.codice_univoco }}</h3>
              <span class="px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider rounded-full border" :class="getStatoBadge(booking.stato_prenotazione)">
                {{ booking.stato_prenotazione }}
              </span>
            </div>
            <p class="text-xs text-gray-500">{{ booking.tipo_descrizione }}</p>
            <div class="flex gap-4 mt-2 text-xs text-gray-400">
              <span>📅 {{ booking.data_prenotazione }}</span>
              <span>🕐 {{ booking.ora_inizio?.substring(0, 5) }} – {{ booking.ora_fine?.substring(0, 5) }}</span>
              <span>✏️ Mod: {{ booking.modifiche_counter }}/2</span>
            </div>
            <p v-if="booking.id_utente !== authStore.user?.id" class="text-xs text-gray-500 mt-1">
              👤 {{ booking.utente_nome }} {{ booking.utente_cognome }} (@{{ booking.username }})
            </p>
          </div>

          <!-- Actions -->
          <div v-if="booking.stato_prenotazione === 'attiva'" class="flex gap-2 shrink-0">
            <button
              v-if="booking.modifiche_counter < 2"
              @click="startEdit(booking)"
              class="px-3 py-1.5 text-xs font-medium bg-sky-500/10 text-sky-400 rounded-lg hover:bg-sky-500/20 transition-colors cursor-pointer"
            >
              ✏️ Modifica
            </button>
            <button
              @click="cancelBooking(booking.id)"
              :disabled="actionLoading === booking.id"
              class="px-3 py-1.5 text-xs font-medium bg-red-500/10 text-red-400 rounded-lg hover:bg-red-500/20 transition-colors disabled:opacity-50 cursor-pointer"
            >
              {{ authStore.isGestore ? '🚫 Revoca' : '❌ Cancella' }}
            </button>
          </div>
        </div>

        <!-- Inline Edit Form -->
        <div v-if="editingBooking?.id === booking.id" class="mt-4 pt-4 border-t border-gray-800/50">
          <div class="grid grid-cols-3 gap-3">
            <div>
              <label class="block text-xs text-gray-500 mb-1">Data</label>
              <input v-model="editData.data_prenotazione" type="date"
                class="w-full px-3 py-2 bg-gray-800/50 border border-gray-700/50 rounded-lg text-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500/50" />
            </div>
            <div>
              <label class="block text-xs text-gray-500 mb-1">Inizio</label>
              <input v-model="editData.ora_inizio" type="time"
                class="w-full px-3 py-2 bg-gray-800/50 border border-gray-700/50 rounded-lg text-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500/50" />
            </div>
            <div>
              <label class="block text-xs text-gray-500 mb-1">Fine</label>
              <input v-model="editData.ora_fine" type="time"
                class="w-full px-3 py-2 bg-gray-800/50 border border-gray-700/50 rounded-lg text-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500/50" />
            </div>
          </div>
          <div class="flex gap-2 mt-3 justify-end">
            <button @click="editingBooking = null" class="px-3 py-1.5 text-xs text-gray-400 hover:text-gray-200 cursor-pointer">Annulla</button>
            <button @click="saveEdit" :disabled="actionLoading === booking.id"
              class="px-4 py-1.5 text-xs font-medium bg-sky-500 text-white rounded-lg hover:bg-sky-400 transition-colors disabled:opacity-50 cursor-pointer">
              Salva Modifica
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
