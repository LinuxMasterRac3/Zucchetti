<template>
  <div class="captcha-space">
    <label class="captcha-label">Verifica di sicurezza</label>
    
    <div class="captcha-container">
      <div class="canvas-wrapper">
        <div class="bg-decoration">
          <div class="glow glow-1"></div>
          <div class="glow glow-2"></div>
        </div>

        <canvas
          ref="canvasRef"
          width="180" 
          height="65"
          class="captcha-canvas"
        />
      </div>
      
      <button
        type="button"
        @click="generateCaptcha"
        class="btn-refresh"
        title="Genera nuovo codice"
      >
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-9-9c2.52 0 4.93 1 6.74 2.74L21 8"></path><path d="M21 3v5h-5"></path></svg>
      </button>
    </div>

    <div class="input-wrapper">
      <input
        v-model="userAnswer"
        type="text"
        placeholder="Inserire codice"
        class="captcha-input"
        @keyup.enter="$emit('validate', isValidated)"
      />
    </div>

    <div v-if="userAnswer" :class="['captcha-feedback', { success: isValidated }]">
      <span class="feedback-dot" :class="{ validated: isValidated }"></span>
      <span>{{ isValidated ? 'Verifica completata' : 'Codice errato' }}</span>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, onMounted } from 'vue'

const props = defineProps<{ onValidate: (isValid: boolean) => void }>()
const canvasRef = ref<HTMLCanvasElement | null>(null)
const captchaText = ref('')
const userAnswer = ref('')
const isValidated = ref(false)

const generateRandomString = () => {
  const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'
  let result = ''
  for (let i = 0; i < 6; i++) result += chars.charAt(Math.floor(Math.random() * chars.length))
  return result
}

const drawCaptcha = (text: string) => {
  const canvas = canvasRef.value
  if (!canvas) return
  const ctx = canvas.getContext('2d')
  if (!ctx) return

  ctx.clearRect(0, 0, canvas.width, canvas.height)

  // 1. Disturbo RETRO
  for (let i = 0; i < 4; i++) {
    ctx.strokeStyle = `rgba(255, 69, 0, ${0.1 + Math.random() * 0.15})`
    ctx.lineWidth = 1.2
    ctx.beginPath()
    ctx.moveTo(Math.random() * canvas.width, Math.random() * canvas.height)
    ctx.lineTo(Math.random() * canvas.width, Math.random() * canvas.height)
    ctx.stroke()
  }

  // 2. Disegno TESTO (Ridimensionato per stare nei 65px)
  const chars = text.split('')
  chars.forEach((char, index) => {
    ctx.save()
    const fontSize = 24 + Math.random() * 4 
    ctx.font = `bold ${fontSize}px sans-serif`
    
    const x = 18 + index * 25 // Spaziatura ridotta
    const y = 42 + (Math.random() - 0.5) * 8
    
    ctx.translate(x, y)
    ctx.rotate((Math.random() - 0.5) * 0.2)
    
    ctx.shadowColor = 'rgba(0, 0, 0, 0.6)'
    ctx.shadowBlur = 3
    
    ctx.fillStyle = index % 2 === 0 ? '#e2e8f0' : '#ff4500'
    ctx.fillText(char, 0, 0)
    ctx.restore()
  })

  // 3. Disturbo SOPRA
  for (let i = 0; i < 30; i++) {
    ctx.fillStyle = `rgba(255, 255, 255, ${Math.random() * 0.15})`
    ctx.beginPath()
    ctx.arc(Math.random() * canvas.width, Math.random() * canvas.height, 0.7, 0, Math.PI * 2)
    ctx.fill()
  }
}

const generateCaptcha = () => {
  const newText = generateRandomString()
  captchaText.value = newText
  userAnswer.value = ''
  isValidated.value = false
  props.onValidate(false)
  setTimeout(() => drawCaptcha(newText), 0)
}

onMounted(() => generateCaptcha())

watch(userAnswer, (newValue) => {
  const isCorrect = newValue.toUpperCase() === captchaText.value
  isValidated.value = isCorrect && newValue !== ''
  props.onValidate(isValidated.value)
})
</script>

<style scoped>
.captcha-space {
  display: flex;
  flex-direction: column;
  gap: 10px;
  max-width: 280px; /* Ridotto leggermente */
}

.captcha-label {
  font-size: 12px;
  color: #64748b;
  font-weight: 500;
  padding-left: 2px;
}

.captcha-container {
  display: flex;
  gap: 8px;
  align-items: stretch;
}

.canvas-wrapper {
  position: relative;
  flex: 1;
  height: 65px; /* Ridotto da 80px */
  background-color: #0b0e14;
  border-radius: 12px;
  overflow: hidden;
  border: 1px solid rgba(255, 255, 255, 0.05);
  display: flex;
  align-items: center;
  justify-content: center;
}

.bg-decoration {
  position: absolute;
  inset: 0;
  pointer-events: none;
}

.glow {
  position: absolute;
  width: 80px;
  height: 80px;
  background: radial-gradient(circle, rgba(255, 69, 0, 0.12) 0%, transparent 70%);
  filter: blur(20px);
}

.glow-1 { top: -25px; right: -25px; }
.glow-2 { bottom: -25px; left: -25px; }

.captcha-canvas {
  position: relative;
  z-index: 2;
  background: transparent;
}

.btn-refresh {
  width: 44px; /* Ridotto da 52px */
  background: #0f1117;
  border: 1.5px solid rgba(255, 255, 255, 0.08);
  border-radius: 12px;
  color: #4b5563;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
}

.btn-refresh:hover {
  color: #ff4500;
  border-color: rgba(255, 69, 0, 0.3);
}

.captcha-input {
  width: 100%;
  padding: 10px 14px; /* Ridotto */
  background-color: #0f1117;
  border: 1.5px solid rgba(255, 255, 255, 0.08);
  border-radius: 12px;
  color: #ffffff;
  font-size: 14px;
  box-sizing: border-box;
  transition: all 0.2s ease;
}

.captcha-input::placeholder { color: #4b5563; }

.captcha-input:focus {
  outline: none;
  border-color: rgba(255, 69, 0, 0.4);
  background-color: #0b0e14;
}

.captcha-feedback {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 11px;
  color: #64748b;
}

.captcha-feedback.success { color: #10b981; }

.feedback-dot {
  width: 5px;
  height: 5px;
  border-radius: 50%;
  background: #334155;
}
.feedback-dot.validated { background: #10b981; }
</style>