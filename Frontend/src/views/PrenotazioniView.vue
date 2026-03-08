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

const editingBooking = ref<Booking | null>(null)
const editData = ref({ data_prenotazione: '' })

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
    actionSuccess.value = 'Prenotazione terminata con successo'
    setTimeout(() => actionSuccess.value = null, 2000)
    await loadBookings()
  } catch (err: any) {
    actionError.value = err.response?.data?.message || 'Errore di sistema'
  } finally {
    actionLoading.value = null
  }
}

function startEdit(booking: Booking) {
  editingBooking.value = booking
  editData.value = { data_prenotazione: booking.data_prenotazione }
}

async function saveEdit() {
  if (!editingBooking.value) return
  actionLoading.value = editingBooking.value.id
  actionError.value = null
  try {
    const response = await bookingsApi.update(editingBooking.value.id, editData.value)
    actionSuccess.value = `Asset aggiornato! ${response.data.modifiche_rimanenti} modifiche restanti`
    setTimeout(() => actionSuccess.value = null, 3000)
    editingBooking.value = null
    await loadBookings()
  } catch (err: any) {
    actionError.value = err.response?.data?.message || 'Modifica non autorizzata'
  } finally {
    actionLoading.value = null
  }
}

function getStatoBadge(stato: string) {
  switch (stato) {
    case 'attiva': return 'text-emerald-400 border-emerald-500/30 bg-emerald-500/5'
    case 'cancellata': return 'text-zinc-500 border-zinc-700 bg-zinc-900/50'
    case 'revocata': return 'text-red-500 border-red-500/20 bg-red-500/5'
    default: return 'text-zinc-400 border-zinc-800'
  }
}

onMounted(loadBookings)
</script>

<template>
  <div class="min-h-screen bg-black p-6 md:p-10 font-sans text-gray-300">
    
    <div class="mb-10 flex flex-col md:flex-row md:items-end justify-between gap-6">
      <div>
        <div class="flex items-center gap-2 mb-2">
          <span class="w-2 h-2 bg-sky-500 rounded-full shadow-[0_0_8px_#0ea5e9]"></span>
          <span class="text-[10px] font-black text-sky-500 uppercase tracking-[0.2em]">Registro Attività Z-Volta</span>
        </div>
        <h1 class="text-4xl font-extrabold text-white tracking-tight">Prenotazioni</h1>
        <p class="text-gray-500 text-lg mt-1 font-medium">Gestione e storico degli asset riservati</p>
      </div>

      <div class="flex bg-zinc-900/50 p-1.5 rounded-2xl border border-white/5 backdrop-blur-md">
        <button
          v-for="stato in ['attiva', 'cancellata', 'revocata', 'tutte']"
          :key="stato"
          @click="filterStato = stato"
          class="px-5 py-2 text-[10px] font-black uppercase tracking-widest rounded-xl transition-all"
          :class="filterStato === stato
            ? 'bg-white text-black shadow-lg shadow-white/5'
            : 'text-zinc-500 hover:text-zinc-200'"
        >
          {{ stato }}
        </button>
      </div>
    </div>

    <TransitionGroup name="fade">
      <div v-if="actionError" class="mb-6 p-4 rounded-2xl bg-red-500/10 border border-red-500/20 text-red-400 text-xs font-bold uppercase tracking-wide">
        ⚠️ {{ actionError }}
      </div>
      <div v-if="actionSuccess" class="mb-6 p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-xs font-bold uppercase tracking-wide">
        ✅ {{ actionSuccess }}
      </div>
    </TransitionGroup>

    <div v-if="loading" class="flex flex-col items-center justify-center py-32 space-y-4">
      <div class="w-8 h-8 border-2 border-sky-500/20 border-t-sky-500 rounded-full animate-spin"></div>
      <p class="text-[10px] font-black text-zinc-600 uppercase tracking-[0.3em]">Sincronizzazione Database...</p>
    </div>

    <div v-else-if="filteredBookings.length === 0" class="bg-zinc-900/20 border-2 border-dashed border-white/5 rounded-[2.5rem] py-32 text-center">
      <p class="text-5xl mb-6 opacity-20">📋</p>
      <p class="text-xl font-bold text-zinc-500 italic">Nessun record trovato per questa categoria</p>
      <RouterLink to="/mappa" class="mt-8 inline-flex items-center gap-3 px-8 py-4 bg-white text-black rounded-2xl text-[10px] font-black uppercase tracking-widest hover:scale-105 transition-transform">
        Nuova Prenotazione <span>→</span>
      </RouterLink>
    </div>

    <div v-else class="grid grid-cols-1 gap-4">
      <div
        v-for="booking in filteredBookings"
        :key="booking.id"
        class="group bg-zinc-800/30 backdrop-blur-2xl rounded-[2rem] border border-white/5 p-6 hover:border-white/20 transition-all shadow-xl"
      >
        <div class="flex flex-col md:flex-row md:items-center gap-8">
          
          <div class="w-20 h-20 rounded-[1.5rem] flex flex-col items-center justify-center border border-white/10 shadow-inner shrink-0 group-hover:scale-105 transition-transform bg-zinc-950"
            :class="{
              'text-sky-400': booking.codice_tipo === 'A',
              'text-indigo-400': booking.codice_tipo === 'A2',
              'text-emerald-400': booking.codice_tipo === 'B',
              'text-amber-400': booking.codice_tipo === 'C',
            }">
            <span class="text-[9px] font-black uppercase opacity-40 mb-1">Asset</span>
            <span class="text-2xl font-black italic">{{ booking.codice_tipo }}</span>
          </div>

          <div class="flex-1">
            <div class="flex flex-wrap items-center gap-3 mb-3">
              <h3 class="text-2xl font-black text-white tracking-tighter leading-none italic uppercase">{{ booking.codice_univoco }}</h3>
              <span class="px-3 py-1 text-[9px] font-black uppercase tracking-widest rounded-lg border" :class="getStatoBadge(booking.stato_prenotazione)">
                {{ booking.stato_prenotazione }}
              </span>
            </div>
            
            <p class="text-sm font-bold text-zinc-500 uppercase tracking-wide mb-4">{{ booking.tipo_descrizione }}</p>
            
            <div class="flex flex-wrap gap-6 items-center">
              <div class="flex flex-col">
                <span class="text-[9px] font-black text-zinc-600 uppercase tracking-widest">Data Prenotazione</span>
                <span class="text-sm text-gray-200 font-mono font-bold">{{ booking.data_prenotazione }}</span>
              </div>
              <div class="flex flex-col">
                <span class="text-[9px] font-black text-zinc-600 uppercase tracking-widest">Modifiche</span>
                <span class="text-sm font-mono font-bold" :class="booking.modifiche_counter >= 2 ? 'text-red-500' : 'text-zinc-300'">
                  {{ booking.modifiche_counter }} / 2
                </span>
              </div>
              <div v-if="booking.id_utente !== authStore.user?.id" class="flex flex-col border-l border-white/10 pl-6">
                <span class="text-[9px] font-black text-[#ff4500]/60 uppercase tracking-widest">Proprietario</span>
                <span class="text-sm text-zinc-300 font-bold uppercase italic">{{ booking.utente_nome }} {{ booking.utente_cognome }}</span>
              </div>
            </div>
          </div>

          <div v-if="booking.stato_prenotazione === 'attiva'" class="flex md:flex-col gap-3 shrink-0">
            <button
              v-if="booking.modifiche_counter < 2"
              @click="startEdit(booking)"
              class="flex-1 md:flex-none px-6 py-3 text-[10px] font-black uppercase tracking-widest bg-white/5 text-white rounded-xl border border-white/10 hover:bg-white hover:text-black transition-all cursor-pointer"
            >
              Modifica
            </button>
            <button
              @click="cancelBooking(booking.id)"
              :disabled="actionLoading === booking.id"
              class="flex-1 md:flex-none px-6 py-3 text-[10px] font-black uppercase tracking-widest bg-red-500/10 text-red-500 rounded-xl border border-red-500/20 hover:bg-red-500 hover:text-white transition-all disabled:opacity-30 cursor-pointer shadow-lg shadow-red-500/5"
            >
              {{ authStore.isGestore ? 'Revoca' : 'Cancella' }}
            </button>
          </div>
        </div>

        <Transition name="slide">
          <div v-if="editingBooking?.id === booking.id" class="mt-8 pt-8 border-t border-white/5 bg-black/20 rounded-2xl p-6">
            <div class="flex flex-col md:flex-row items-end gap-6">
              <div class="flex-1 w-full">
                <label class="block text-[10px] font-black text-zinc-500 uppercase tracking-widest mb-3">Seleziona Nuova Data</label>
                <input v-model="editData.data_prenotazione" type="date"
                  class="w-full px-6 py-4 bg-zinc-900 border border-white/10 rounded-2xl text-white font-mono focus:ring-2 focus:ring-[#ff4500]/50 outline-none transition-all" />
              </div>
              <div class="flex gap-3 w-full md:w-auto">
                <button @click="editingBooking = null" class="flex-1 px-8 py-4 text-[10px] font-black uppercase text-zinc-500 hover:text-white transition-colors">Annulla</button>
                <button @click="saveEdit" :disabled="actionLoading === booking.id"
                  class="flex-1 px-8 py-4 bg-sky-500 text-black font-black text-[10px] uppercase tracking-widest rounded-2xl hover:bg-sky-400 transition-all shadow-lg shadow-sky-500/20">
                  Conferma Cambio
                </button>
              </div>
            </div>
          </div>
        </Transition>
      </div>
    </div>
  </div>
</template>

<style scoped>
.font-mono { font-variant-numeric: tabular-nums; }
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

.slide-enter-active, .slide-leave-active { transition: all 0.3s ease; max-height: 200px; }
.slide-enter-from, .slide-leave-to { opacity: 0; max-height: 0; transform: translateY(-10px); }
</style>