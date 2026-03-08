<script setup lang="ts">
import { useAuthStore } from '@/stores/auth'
import { computed } from 'vue'
import { useRoute } from 'vue-router'

const authStore = useAuthStore()
const route = useRoute()

const navItems = computed(() => {
  const items = [
    { path: '/dashboard', label: 'Dashboard', icon: '📊' },
    { path: '/mappa', label: 'Mappa Sede', icon: '🏢' },
    { path: '/prenotazioni', label: 'Prenotazioni', icon: '📋' }
  ]
  if (authStore.isGestore) {
    items.push({ path: '/admin', label: 'Admin', icon: '⚙️' })
  }
  return items
})

const roleBadgeClass = computed(() => {
  switch (authStore.user?.ruolo) {
    case 'gestore': return 'bg-zinc-100/10 text-zinc-100 border-zinc-100/20'
    case 'coordinatore': return 'bg-zinc-500/10 text-zinc-400 border-zinc-500/20'
    default: return 'bg-zinc-800/50 text-zinc-500 border-zinc-800'
  }
})
</script>

<template>
  <div class="flex h-screen bg-black overflow-hidden font-sans">
    <aside class="w-64 bg-zinc-950/80 backdrop-blur-2xl border-r border-white/5 flex flex-col relative z-20">
      <div class="absolute right-0 top-0 w-[1px] h-full bg-gradient-to-b from-transparent via-zinc-500/20 to-transparent"></div>

      <div class="p-8 border-b border-white/5">
        <div class="flex items-center gap-3">
          <div class="w-1.5 h-6 bg-zinc-300 rounded-full shadow-[0_0_10px_rgba(212,212,216,0.3)]"></div>
          <h1 class="text-xl font-black tracking-tighter text-white uppercase italic">Z-BOOK</h1>
        </div>
        <p class="text-[9px] text-zinc-500 mt-2 tracking-[0.3em] font-bold uppercase">Asset Control</p>
      </div>

      <nav class="flex-1 p-5 space-y-2">
        <RouterLink
          v-for="item in navItems"
          :key="item.path"
          :to="item.path"
          class="group flex items-center gap-4 px-4 py-3.5 rounded-2xl text-sm font-bold transition-all duration-300 border border-transparent"
          :class="route.path === item.path
            ? 'bg-white/10 text-white border-white/10 shadow-[0_0_20px_rgba(255,255,255,0.05)]'
            : 'text-zinc-500 hover:text-zinc-200 hover:bg-white/5'"
        >
          <span class="text-lg" :class="route.path === item.path ? '' : 'grayscale opacity-70'">{{ item.icon }}</span>
          <span class="tracking-tight">{{ item.label }}</span>
          <div v-if="route.path === item.path" class="ml-auto w-1.5 h-1.5 rounded-full bg-zinc-300 shadow-[0_0_8px_#fff]"></div>
        </RouterLink>
      </nav>

      <div class="p-5 border-t border-white/5 bg-zinc-900/10">
        <div class="bg-zinc-900/40 rounded-[1.8rem] border border-white/5 p-4 shadow-inner">
          <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-zinc-400 to-zinc-600 flex items-center justify-center text-black font-black text-sm border border-white/10">
              {{ authStore.user?.username?.charAt(0).toUpperCase() }}
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-xs font-black text-white truncate uppercase">{{ authStore.user?.username }}</p>
              <span class="inline-block px-2 py-0.5 text-[9px] font-black uppercase tracking-widest rounded-md border mt-1" :class="roleBadgeClass">
                {{ authStore.user?.ruolo === 'gestore' ? 'Admin' : authStore.user?.ruolo }}
              </span>
            </div>
          </div>
          <button @click="authStore.logout()" class="w-full flex items-center justify-center gap-2 py-2 text-[10px] font-black uppercase text-zinc-500 hover:text-red-400 transition-colors">
            🚪 Logout
          </button>
        </div>
      </div>
    </aside>

    <main class="flex-1 overflow-auto bg-black relative">
      <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(60,60,60,0.15),transparent_50%)] pointer-events-none"></div>
      <div class="relative z-10 w-full min-h-full">
        <slot />
      </div>
    </main>
  </div>
</template>