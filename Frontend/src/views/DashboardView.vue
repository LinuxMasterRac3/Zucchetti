<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()
const currentTime = ref(new Date())
let timer: any = null

onMounted(async () => {
  await authStore.fetchDashboard()
  timer = setInterval(() => {
    currentTime.value = new Date()
  }, 1000)
})

onUnmounted(() => {
  if (timer) clearInterval(timer)
})
</script>

<template>
  <div class="min-h-screen bg-black p-4 md:p-8 font-sans selection:bg-[#ff4500]/30 text-gray-300">
    
    <div class="mb-10 flex flex-col md:flex-row md:items-end justify-between gap-6">
      <div>
        <div class="flex items-center gap-2 mb-2">
          <span class="w-2 h-2 bg-[#ff4500] rounded-full shadow-[0_0_8px_#ff4500]"></span>
          <span class="text-[10px] font-black text-[#ff4500] uppercase tracking-[0.2em]">Controllo Asset Z-Volta</span>
        </div>
        <h1 class="text-4xl font-extrabold text-white tracking-tight">Dashboard</h1>
        <p class="text-gray-500 text-lg mt-1 font-medium">Benvenuto nel portale gestionale</p>
      </div>
      
      <div class="hidden md:flex items-center gap-4 bg-zinc-900/50 p-3 rounded-2xl border border-white/5 backdrop-blur-md">
        <div class="text-right border-r border-white/10 pr-4">
          <p class="text-[10px] uppercase tracking-widest text-gray-500 font-bold">DATE AND CLOCK</p>
          <p class="text-xs font-mono text-emerald-400 font-bold uppercase italic">Online / {{ currentTime.toLocaleDateString() }}</p>
        </div>
        <div class="text-gray-200 font-mono text-sm font-bold min-w-[45px] text-center">
          {{ currentTime.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) }}
        </div>
      </div>
    </div>

    <div v-if="authStore.dashboard" class="bg-zinc-800/40 backdrop-blur-2xl rounded-[2rem] border border-white/10 p-8 mb-8 shadow-2xl relative overflow-hidden">
      <div class="absolute -top-24 -right-24 w-64 h-64 bg-[#ff4500]/5 rounded-full blur-[80px]"></div>
      
      <div class="relative z-10 flex flex-col md:flex-row md:items-center gap-8">
        <div class="flex items-center gap-6">
          <div class="w-20 h-20 rounded-3xl bg-zinc-900 flex items-center justify-center text-white font-black text-3xl border border-white/10 shadow-inner">
            {{ authStore.dashboard.profile.nome?.charAt(0) }}{{ authStore.dashboard.profile.cognome?.charAt(0) }}
          </div>
          <div>
            <h2 class="text-3xl font-extrabold text-white tracking-tight leading-none">
              {{ authStore.dashboard.profile.nome }} {{ authStore.dashboard.profile.cognome }}
            </h2>
            <p class="text-[#ff4500]/70 font-mono text-sm mt-2 font-bold tracking-tighter">@{{ authStore.dashboard.profile.username }}</p>
          </div>
        </div>

        <div class="flex flex-wrap gap-4 md:ml-auto">
          <div class="px-6 py-3 rounded-2xl bg-black/40 border border-white/5 backdrop-blur-sm text-center">
             <p class="text-[10px] uppercase text-gray-500 mb-1 font-black tracking-widest">Livello Accesso</p>
             <span class="text-sm font-black uppercase tracking-widest text-red-500">
               ADMIN
             </span>
          </div>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
      <div class="bg-zinc-800/40 backdrop-blur-xl rounded-[2rem] border border-white/10 p-7">
        <p class="text-gray-500 text-[11px] uppercase tracking-[0.2em] font-black mb-6">Prenotazioni</p>
        <div class="flex items-baseline gap-2 mb-4">
          <p class="text-5xl font-black text-white tracking-tighter">{{ authStore.dashboard?.prenotazioni_attive ?? 0 }}</p>
          <p class="text-gray-600 text-lg font-mono font-bold">/ {{ authStore.dashboard?.profile.max_prenotazioni }}</p>
        </div>
        <div class="h-1.5 w-full bg-black/50 rounded-full overflow-hidden border border-white/5 shadow-inner">
          <div class="h-full bg-gradient-to-r from-[#ff4500]/50 to-[#ff4500]" :style="{ width: ((authStore.dashboard?.prenotazioni_attive / authStore.dashboard?.profile.max_prenotazioni) * 100) + '%' }"></div>
        </div>
      </div>

      <div class="bg-zinc-800/40 backdrop-blur-xl rounded-[2rem] border border-white/10 p-7">
        <p class="text-gray-500 text-[11px] uppercase tracking-[0.2em] font-black mb-6">Prenotazioni del giorno</p>
        <p class="text-5xl font-black text-emerald-400 tracking-tighter">{{ authStore.dashboard?.statistiche?.prenotazioni_oggi ?? 0 }}</p>
        <p class="text-gray-500 text-[10px] mt-4 flex items-center gap-2 font-bold uppercase tracking-widest">
          <span class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></span> Registrazioni attive
        </p>
      </div>

      <div class="bg-zinc-800/40 backdrop-blur-xl rounded-[2rem] border border-white/10 p-7">
        <p class="text-gray-500 text-[11px] uppercase tracking-[0.2em] font-black mb-6">Organico Totale</p>
        <p class="text-5xl font-black text-white tracking-tighter">6</p>
        <div class="flex gap-4 mt-4">
            <div class="flex flex-col"><span class="text-[9px] uppercase text-[#ff4500]/60 font-black">Admin</span><span class="text-xs text-white font-mono">1</span></div>
            <div class="flex flex-col"><span class="text-[9px] uppercase text-[#ff4500]/60 font-black">Coord.</span><span class="text-xs text-white font-mono">2</span></div>
            <div class="flex flex-col"><span class="text-[9px] uppercase text-[#ff4500]/60 font-black">Dipend.</span><span class="text-xs text-white font-mono">3</span></div>
        </div>
      </div>
    </div>

    <div class="bg-zinc-800/20 backdrop-blur-3xl rounded-[2.5rem] border border-white/10 p-10 shadow-3xl">
      <div class="flex items-center justify-between mb-10">
        <div class="flex items-center gap-4">
          <div class="w-2 h-8 bg-[#ff4500] rounded-full shadow-[0_0_15px_rgba(255,69,0,0.4)]"></div>
          <h3 class="text-2xl font-extrabold text-white tracking-tight text-uppercase">Prossime Prenotazioni</h3>
        </div>
        <RouterLink to="/mappa" class="px-6 py-3 bg-white text-black rounded-2xl text-[10px] font-black shadow-xl hover:scale-105 transition-transform uppercase italic flex items-center gap-2">
          Nuova Prenotazione <span>→</span>
        </RouterLink>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div v-for="i in 5" :key="i" class="flex items-center gap-6 bg-zinc-800/40 p-6 rounded-3xl border border-white/5 relative overflow-hidden">
          <div class="absolute left-0 top-1/2 -translate-y-1/2 h-12 w-1 bg-[#ff4500]/40 rounded-r-full"></div>
          <div class="w-16 h-16 rounded-2xl bg-zinc-900 border border-white/10 flex flex-col items-center justify-center">
            <span class="text-[9px] text-zinc-600 font-black uppercase tracking-tighter">Tipo</span>
            <span class="text-xl font-black text-white">A2</span>
          </div>
          <div class="flex-1">
            <p class="text-xl font-black text-white tracking-tighter">A2-12</p>
            <p class="text-[9px] font-black text-zinc-500 uppercase mt-1">Scrivania con monitor</p>
          </div>
          <div class="text-right border-l border-white/10 pl-6">
            <p class="text-white font-mono font-black text-sm">00:00:00</p>
            <p class="text-[9px] font-black text-zinc-500 uppercase">2026-03-07</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>