<script setup lang="ts">
import { ref, watch, computed } from 'vue'

const props = defineProps<{
  modelValue: string
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', val: string): void
}>()

const currentMonth = ref(new Date(props.modelValue))

const daysOfWeek = ['Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab', 'Dom']

const monthName = computed(() => {
  return currentMonth.value.toLocaleString('it-IT', { month: 'long', year: 'numeric' })
})

const calendarDays = computed(() => {
  const year = currentMonth.value.getFullYear()
  const month = currentMonth.value.getMonth()
  
  const firstDay = new Date(year, month, 1)
  const lastDay = new Date(year, month + 1, 0)
  
  let firstDayIndex = firstDay.getDay() === 0 ? 6 : firstDay.getDay() - 1 // Start Monday
  
  const days = []
  
  // Previous month padded days
  const prevMonthLastDay = new Date(year, month, 0).getDate()
  for (let i = firstDayIndex - 1; i >= 0; i--) {
    const d = new Date(year, month - 1, prevMonthLastDay - i)
    days.push({
      date: d,
      dateStr: toYMD(d),
      isCurrentMonth: false,
      isToday: isToday(d),
      isSelected: toYMD(d) === props.modelValue,
      isPast: isPast(d)
    })
  }
  
  // Current month days
  for (let i = 1; i <= lastDay.getDate(); i++) {
    const d = new Date(year, month, i)
    days.push({
      date: d,
      dateStr: toYMD(d),
      isCurrentMonth: true,
      isToday: isToday(d),
      isSelected: toYMD(d) === props.modelValue,
      isPast: isPast(d)
    })
  }
  
  // Next month padded days
  const remainingCells = 42 - days.length // 6 rows of 7 days
  for (let i = 1; i <= remainingCells; i++) {
    const d = new Date(year, month + 1, i)
    days.push({
      date: d,
      dateStr: toYMD(d),
      isCurrentMonth: false,
      isToday: isToday(d),
      isSelected: toYMD(d) === props.modelValue,
      isPast: isPast(d)
    })
  }
  
  return days
})

function toYMD(d: Date) {
  const year = d.getFullYear()
  const month = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

function isToday(d: Date) {
  const today = new Date()
  return d.getDate() === today.getDate() &&
         d.getMonth() === today.getMonth() &&
         d.getFullYear() === today.getFullYear()
}

function isPast(d: Date) {
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  return d.getTime() < today.getTime()
}

function prevMonth() {
  currentMonth.value = new Date(currentMonth.value.getFullYear(), currentMonth.value.getMonth() - 1, 1)
}

function nextMonth() {
  currentMonth.value = new Date(currentMonth.value.getFullYear(), currentMonth.value.getMonth() + 1, 1)
}

function selectDate(day: any) {
  if (day.isPast && !day.isToday) return // Optional: prevent booking in the past
  emit('update:modelValue', day.dateStr)
  // Auto-switch to the selected date's month if it's outside current view
  if (!day.isCurrentMonth) {
    currentMonth.value = new Date(day.date)
  }
}

watch(() => props.modelValue, (newVal) => {
  const d = new Date(newVal)
  if (d.getMonth() !== currentMonth.value.getMonth() || d.getFullYear() !== currentMonth.value.getFullYear()) {
    currentMonth.value = new Date(d.getFullYear(), d.getMonth(), 1)
  }
})

</script>

<template>
  <div class="bg-gray-900/80 backdrop-blur-xl border border-gray-800 rounded-3xl p-5 shadow-2xl w-[320px]">
    <!-- Header -->
    <div class="flex items-center justify-between mb-4">
      <button @click="prevMonth" class="p-2 hover:bg-gray-800 rounded-full transition-colors text-gray-400 hover:text-white cursor-pointer">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
      </button>
      <h2 class="text-sm font-semibold capitalize text-gray-100 tracking-wide">{{ monthName }}</h2>
      <button @click="nextMonth" class="p-2 hover:bg-gray-800 rounded-full transition-colors text-gray-400 hover:text-white cursor-pointer">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
      </button>
    </div>

    <!-- Days Header -->
    <div class="grid grid-cols-7 gap-1 mb-2">
      <div v-for="day in daysOfWeek" :key="day" class="text-center text-[10px] font-medium text-gray-500 uppercase">
        {{ day }}
      </div>
    </div>

    <!-- Calendar Grid -->
    <div class="grid grid-cols-7 gap-1">
      <button
        v-for="day in calendarDays"
        :key="day.dateStr"
        @click="selectDate(day)"
        :disabled="day.isPast && !day.isToday"
        class="aspect-square flex items-center justify-center rounded-xl text-xs relative transition-all duration-200 cursor-pointer disabled:cursor-not-allowed group"
        :class="[
          !day.isCurrentMonth ? 'text-gray-600' : 'text-gray-300',
          day.isSelected ? 'bg-sky-500 text-white font-bold shadow-lg shadow-sky-500/30' : 'hover:bg-gray-800',
          day.isToday && !day.isSelected ? 'border border-sky-500/50 text-sky-400' : '',
          (day.isPast && !day.isToday) ? 'opacity-30' : ''
        ]"
      >
        <span>{{ day.date.getDate() }}</span>
        <!-- Optional: indicator dot for availability could go here in a full app -->
        <span v-if="day.isSelected" class="absolute bottom-1 w-1 h-1 bg-white rounded-full"></span>
      </button>
    </div>
  </div>
</template>
