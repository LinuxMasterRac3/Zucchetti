<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { usersApi, assetsApi, bookingsApi, rolesApi } from '@/services/api'

// =====================================================================
// State & Types
// =====================================================================
const activeTab = ref<'users' | 'assets' | 'bookings'>('users')

interface User {
  id: number; nome: string; cognome: string; username: string; id_ruolo: number; ruolo_nome: string; id_coordinatore: number | null
}
interface Asset {
  id: number; codice_univoco: string; id_tipo: number; codice_tipo: string; tipo_descrizione: string; stato_attuale: string
}
interface Booking {
  id: number; id_utente: number; codice_univoco: string; data_prenotazione: string;
  ora_inizio: string; ora_fine: string; stato_prenotazione: string;
  utente_nome: string; utente_cognome: string; username: string
}
interface Role { id: number; nome: string; descrizione: string; max_prenotazioni: number }

const users = ref<User[]>([])
const assets = ref<Asset[]>([])
const allBookings = ref<Booking[]>([])
const roles = ref<Role[]>([])
const loading = ref(false)
const msg = ref<{ type: 'ok' | 'err'; text: string } | null>(null)

// Forms
const showUserForm = ref(false)
const userForm = ref({ nome: '', cognome: '', username: '', password: '', id_ruolo: 3, id_coordinatore: null as number | null })
const editUserId = ref<number | null>(null)

// =====================================================================
// Loaders
// =====================================================================
async function loadAll() {
  loading.value = true
  try {
    const [u, a, b, r] = await Promise.all([
      usersApi.getAll(), assetsApi.getAll(), bookingsApi.getAll(), rolesApi.getAll()
    ])
    users.value = u.data
    assets.value = a.data
    allBookings.value = b.data
    roles.value = r.data
  } catch { /* ignore */ } finally { loading.value = false }
}

// =====================================================================
// User Management
// =====================================================================
function openNewUser() {
  editUserId.value = null
  userForm.value = { nome: '', cognome: '', username: '', password: '', id_ruolo: 3, id_coordinatore: null }
  showUserForm.value = true
}

function openEditUser(u: User) {
  editUserId.value = u.id
  userForm.value = { nome: u.nome, cognome: u.cognome, username: u.username, password: '', id_ruolo: u.id_ruolo, id_coordinatore: u.id_coordinatore }
  showUserForm.value = true
}

async function saveUser() {
  msg.value = null
  try {
    if (editUserId.value) {
      const data: Record<string, unknown> = { ...userForm.value }
      if (!data.password) delete data.password
      await usersApi.update(editUserId.value, data)
      msg.value = { type: 'ok', text: 'Profilo utente aggiornato correttamente' }
    } else {
      await usersApi.create(userForm.value)
      msg.value = { type: 'ok', text: 'Nuovo utente registrato nel sistema' }
    }
    showUserForm.value = false
    await loadAll()
  } catch (err: any) {
    msg.value = { type: 'err', text: err.response?.data?.message || 'Errore durante il salvataggio' }
  }
}

async function deleteUser(id: number) {
  if (!confirm('Eliminare definitivamente questo utente?')) return
  try {
    await usersApi.delete(id)
    msg.value = { type: 'ok', text: 'Utente rimosso dal database' }
    await loadAll()
  } catch (err: any) {
    msg.value = { type: 'err', text: err.response?.data?.message || 'Errore di eliminazione' }
  }
}

// =====================================================================
// Asset Management
// =====================================================================
async function toggleAssetStatus(asset: Asset) {
  const newStatus = asset.stato_attuale === 'non_prenotabile' ? 'disponibile' : 'non_prenotabile'
  try {
    await assetsApi.update(asset.id, { stato_attuale: newStatus })
    msg.value = { type: 'ok', text: `Status ${asset.codice_univoco} modificato in: ${newStatus}` }
    await loadAll()
  } catch (err: any) {
    msg.value = { type: 'err', text: err.response?.data?.message || 'Errore' }
  }
}

// =====================================================================
// Booking Revoke
// =====================================================================
async function revokeBooking(id: number) {
  if (!confirm('Eseguire la revoca forzata di questa prenotazione?')) return
  try {
    await bookingsApi.cancel(id)
    msg.value = { type: 'ok', text: 'Prenotazione revocata con successo' }
    await loadAll()
  } catch (err: any) {
    msg.value = { type: 'err', text: err.response?.data?.message || 'Errore' }
  }
}

function getRoleBadge(nome: string) {
  switch (nome) {
    case 'gestore': return 'bg-red-500/10 text-red-500 border-red-500/20'
    case 'coordinatore': return 'bg-amber-500/10 text-amber-500 border-amber-500/20'
    default: return 'bg-sky-500/10 text-sky-500 border-sky-500/20'
  }
}

onMounted(loadAll)
</script>

<template>
  <div class="min-h-screen bg-black p-6 md:p-10 font-sans text-gray-300">
    
    <div class="mb-10 flex flex-col md:flex-row md:items-end justify-between gap-6">
      <div>
        <div class="flex items-center gap-2 mb-2">
          <span class="w-2 h-2 bg-red-600 rounded-full shadow-[0_0_8px_#dc2626]"></span>
          <span class="text-[10px] font-black text-red-600 uppercase tracking-[0.2em]">Z-Volta Admin System</span>
        </div>
        <h1 class="text-4xl font-extrabold text-white tracking-tight italic uppercase">Amministrazione</h1>
        <p class="text-gray-500 text-lg mt-1 font-medium italic">Gestione centralizzata dell'organico e degli asset</p>
      </div>

      <div class="flex bg-zinc-900/50 p-1.5 rounded-2xl border border-white/5 backdrop-blur-md shrink-0">
        <button
          v-for="tab in [{ key: 'users', label: 'Utenti' }, { key: 'assets', label: 'Asset' }, { key: 'bookings', label: 'Prenotazioni' }] as const"
          :key="tab.key"
          @click="activeTab = tab.key"
          class="px-6 py-2.5 text-[10px] font-black uppercase tracking-[0.15em] rounded-xl transition-all cursor-pointer"
          :class="activeTab === tab.key
            ? 'bg-white text-black shadow-lg shadow-white/5 scale-100'
            : 'text-zinc-500 hover:text-zinc-200'"
        >
          {{ tab.label }}
        </button>
      </div>
    </div>

    <div v-if="msg" class="mb-8 p-5 rounded-[1.5rem] border backdrop-blur-xl animate-in fade-in slide-in-from-top-4 duration-300"
      :class="msg.type === 'ok' ? 'bg-emerald-500/5 border-emerald-500/20 text-emerald-400' : 'bg-red-500/5 border-red-500/20 text-red-400'">
      <div class="flex items-center gap-3">
        <span class="text-lg">{{ msg.type === 'ok' ? '✓' : '⚠️' }}</span>
        <span class="text-[11px] font-black uppercase tracking-widest">{{ msg.text }}</span>
      </div>
    </div>

    <div v-if="loading" class="flex flex-col items-center justify-center py-32 space-y-4 opacity-50">
      <div class="w-8 h-8 border-2 border-white/5 border-t-white rounded-full animate-spin"></div>
      <p class="text-[10px] font-black uppercase tracking-[0.3em]">Querying Database...</p>
    </div>

    <div v-else-if="activeTab === 'users'" class="space-y-6">
      <div class="flex justify-between items-center mb-2">
        <h2 class="text-xl font-black text-white italic uppercase tracking-tight tracking-tighter">Directory Personale ({{ users.length }})</h2>
        <button @click="openNewUser" class="px-6 py-3 bg-sky-600 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-sky-500 transition-all shadow-lg shadow-sky-600/20 cursor-pointer">
          + Registra Utente
        </button>
      </div>

      <div v-if="showUserForm" class="bg-zinc-800/40 backdrop-blur-3xl rounded-[2.5rem] border border-[#ff4500]/30 p-8 mb-8 shadow-2xl">
        <h3 class="text-lg font-black text-white uppercase italic mb-6 tracking-tighter">
          {{ editUserId ? 'Aggiornamento Account' : 'Nuova Registrazione Sistema' }}
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
          <div class="space-y-1.5">
            <label class="text-[9px] font-black text-zinc-500 uppercase ml-2 tracking-widest">Nome</label>
            <input v-model="userForm.nome" class="w-full px-5 py-4 bg-black border border-white/10 rounded-2xl text-white font-bold focus:border-[#ff4500] outline-none transition-all" />
          </div>
          <div class="space-y-1.5">
            <label class="text-[9px] font-black text-zinc-500 uppercase ml-2 tracking-widest">Cognome</label>
            <input v-model="userForm.cognome" class="w-full px-5 py-4 bg-black border border-white/10 rounded-2xl text-white font-bold focus:border-[#ff4500] outline-none transition-all" />
          </div>
          <div class="space-y-1.5">
            <label class="text-[9px] font-black text-zinc-500 uppercase ml-2 tracking-widest">ID Utente (@)</label>
            <input v-model="userForm.username" class="w-full px-5 py-4 bg-black border border-white/10 rounded-2xl text-[#ff4500] font-mono font-black focus:border-[#ff4500] outline-none transition-all" />
          </div>
          <div class="space-y-1.5">
            <label class="text-[9px] font-black text-zinc-500 uppercase ml-2 tracking-widest">Security Key (Password)</label>
            <input v-model="userForm.password" type="password" class="w-full px-5 py-4 bg-black border border-white/10 rounded-2xl text-white font-bold focus:border-[#ff4500] outline-none transition-all" />
          </div>
          <div class="space-y-1.5">
            <label class="text-[9px] font-black text-zinc-500 uppercase ml-2 tracking-widest">Livello Ruolo</label>
            <select v-model="userForm.id_ruolo" class="w-full px-5 py-4 bg-black border border-white/10 rounded-2xl text-white font-bold focus:border-[#ff4500] outline-none transition-all appearance-none">
              <option v-for="role in roles" :key="role.id" :value="role.id">{{ role.nome.toUpperCase() }}</option>
            </select>
          </div>
          <div class="space-y-1.5">
            <label class="text-[9px] font-black text-zinc-500 uppercase ml-2 tracking-widest">Gerarchia (Coordinatore)</label>
            <select v-model="userForm.id_coordinatore" class="w-full px-5 py-4 bg-black border border-white/10 rounded-2xl text-white font-bold focus:border-[#ff4500] outline-none transition-all appearance-none">
              <option :value="null">AUTONOMO (NESSUNO)</option>
              <option v-for="u in users.filter(u => u.ruolo_nome === 'coordinatore')" :key="u.id" :value="u.id">{{ u.nome.toUpperCase() }} {{ u.cognome.toUpperCase() }}</option>
            </select>
          </div>
        </div>
        <div class="flex gap-4 mt-8 justify-end">
          <button @click="showUserForm = false" class="px-8 py-4 text-[10px] font-black uppercase text-zinc-500 hover:text-white transition-colors">Annulla</button>
          <button @click="saveUser" class="px-10 py-4 bg-white text-black text-[10px] font-black uppercase tracking-widest rounded-2xl hover:bg-zinc-200 transition-all shadow-xl">
            Salva Record
          </button>
        </div>
      </div>

      <div class="bg-zinc-800/20 backdrop-blur-3xl rounded-[2.5rem] border border-white/5 overflow-hidden shadow-2xl">
        <table class="w-full border-collapse">
          <thead>
            <tr class="border-b border-white/5 bg-zinc-950/50">
              <th class="text-left px-8 py-6 text-[10px] font-black text-zinc-500 uppercase tracking-[0.2em]">Soggetto</th>
              <th class="text-left px-8 py-6 text-[10px] font-black text-zinc-500 uppercase tracking-[0.2em]">Identificativo</th>
              <th class="text-left px-8 py-6 text-[10px] font-black text-zinc-500 uppercase tracking-[0.2em]">Permessi</th>
              <th class="text-right px-8 py-6 text-[10px] font-black text-zinc-500 uppercase tracking-[0.2em]">Operazioni</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-white/[0.02]">
            <tr v-for="u in users" :key="u.id" class="group hover:bg-white/[0.02] transition-colors">
              <td class="px-8 py-6">
                <div class="flex items-center gap-4">
                  <div class="w-10 h-10 rounded-xl bg-zinc-900 border border-white/5 flex items-center justify-center text-xs font-black text-zinc-400 group-hover:text-white group-hover:border-white/20 transition-all uppercase">
                    {{ u.nome.charAt(0) }}{{ u.cognome.charAt(0) }}
                  </div>
                  <span class="text-sm font-bold text-gray-200 group-hover:text-white transition-colors">{{ u.nome }} {{ u.cognome }}</span>
                </div>
              </td>
              <td class="px-8 py-6 font-mono text-xs text-[#ff4500] font-black italic">@{{ u.username }}</td>
              <td class="px-8 py-6">
                <span class="px-3 py-1 text-[9px] font-black uppercase tracking-widest rounded-lg border inline-block" :class="getRoleBadge(u.ruolo_nome)">
                  {{ u.ruolo_nome }}
                </span>
              </td>
              <td class="px-8 py-6 text-right">
                <div class="flex justify-end gap-3 opacity-0 group-hover:opacity-100 transition-opacity">
                  <button @click="openEditUser(u)" class="p-2 text-sky-400 hover:bg-sky-400/10 rounded-lg transition-all cursor-pointer">✏️</button>
                  <button @click="deleteUser(u.id)" class="p-2 text-red-500 hover:bg-red-500/10 rounded-lg transition-all cursor-pointer">🗑️</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div v-else-if="activeTab === 'assets'" class="space-y-6">
      <h2 class="text-xl font-black text-white italic uppercase tracking-tighter">Inventario Asset ({{ assets.length }})</h2>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div v-for="a in assets" :key="a.id" 
          class="bg-zinc-800/30 backdrop-blur-xl border border-white/5 p-6 rounded-[2rem] hover:border-white/20 transition-all group">
          <div class="flex justify-between items-start mb-6">
            <div class="w-14 h-14 rounded-2xl bg-black border border-white/10 flex items-center justify-center font-black text-xl italic"
              :class="a.stato_attuale === 'disponibile' ? 'text-emerald-400' : 'text-zinc-600'">
              {{ a.codice_tipo }}
            </div>
            <span class="px-3 py-1 text-[9px] font-black uppercase tracking-widest rounded-lg border shadow-inner"
              :class="a.stato_attuale === 'disponibile' ? 'bg-emerald-500/5 text-emerald-400 border-emerald-500/20' : a.stato_attuale === 'occupato' ? 'bg-red-500/5 text-red-400 border-red-500/20' : 'bg-zinc-900 text-zinc-500 border-zinc-800'">
              {{ a.stato_attuale }}
            </span>
          </div>
          <div class="mb-6">
            <p class="text-2xl font-black text-white italic tracking-tighter uppercase leading-none mb-2">{{ a.codice_univoco }}</p>
            <p class="text-[10px] font-black text-zinc-500 uppercase tracking-widest">{{ a.tipo_descrizione }}</p>
          </div>
          <button @click="toggleAssetStatus(a)" 
            class="w-full py-3 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all cursor-pointer border"
            :class="a.stato_attuale === 'non_prenotabile' 
              ? 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20 hover:bg-emerald-500 hover:text-black shadow-lg shadow-emerald-500/10' 
              : 'bg-amber-500/5 text-amber-500 border-amber-500/20 hover:bg-amber-500 hover:text-black shadow-lg shadow-amber-500/10'">
            {{ a.stato_attuale === 'non_prenotabile' ? 'Abilita Asset' : 'Sospendi Asset' }}
          </button>
        </div>
      </div>
    </div>

    <div v-else-if="activeTab === 'bookings'" class="space-y-6">
      <h2 class="text-xl font-black text-white italic uppercase tracking-tighter">Log Prenotazioni Totali ({{ allBookings.length }})</h2>
      <div class="bg-zinc-800/20 backdrop-blur-3xl rounded-[2.5rem] border border-white/5 overflow-hidden shadow-2xl">
        <table class="w-full border-collapse">
          <thead>
            <tr class="border-b border-white/5 bg-zinc-950/50">
              <th class="text-left px-8 py-6 text-[10px] font-black text-zinc-500 uppercase tracking-[0.2em]">Asset ID</th>
              <th class="text-left px-8 py-6 text-[10px] font-black text-zinc-500 uppercase tracking-[0.2em]">Assegnatario</th>
              <th class="text-left px-8 py-6 text-[10px] font-black text-zinc-500 uppercase tracking-[0.2em]">Periodo</th>
              <th class="text-left px-8 py-6 text-[10px] font-black text-zinc-500 uppercase tracking-[0.2em]">Stato</th>
              <th class="text-right px-8 py-6 text-[10px] font-black text-zinc-500 uppercase tracking-[0.2em]">Controllo</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-white/[0.02]">
            <tr v-for="b in allBookings" :key="b.id" class="group hover:bg-white/[0.02] transition-colors">
              <td class="px-8 py-6 font-mono text-xs text-white font-black italic">{{ b.codice_univoco }}</td>
              <td class="px-8 py-6">
                <div class="text-sm font-bold text-gray-200">{{ b.utente_nome }} {{ b.utente_cognome }}</div>
                <div class="text-[10px] font-mono text-[#ff4500] font-black">@{{ b.username }}</div>
              </td>
              <td class="px-8 py-6 font-mono text-xs text-zinc-400">
                <div class="font-black text-zinc-300">{{ b.data_prenotazione }}</div>
                <div class="text-[10px] opacity-60 italic">{{ b.ora_inizio?.substring(0, 5) }} – {{ b.ora_fine?.substring(0, 5) }}</div>
              </td>
              <td class="px-8 py-6">
                <span class="px-3 py-1 text-[9px] font-black uppercase tracking-widest rounded-lg border inline-block"
                  :class="b.stato_prenotazione === 'attiva' ? 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20' : 'bg-red-500/10 text-red-500 border-red-500/20'">
                  {{ b.stato_prenotazione }}
                </span>
              </td>
              <td class="px-8 py-6 text-right">
                <button
                  v-if="b.stato_prenotazione === 'attiva'"
                  @click="revokeBooking(b.id)"
                  class="px-4 py-2 bg-red-600/10 text-red-500 text-[10px] font-black uppercase tracking-widest border border-red-600/20 rounded-xl hover:bg-red-600 hover:text-white transition-all cursor-pointer"
                >
                  Revoca
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<style scoped>
.font-mono { font-variant-numeric: tabular-nums; }
select {
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%23ffffff' stroke-width='2'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M19 9l-7 7-7-7' /%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 1.25rem center;
  background-size: 1rem;
}
</style>