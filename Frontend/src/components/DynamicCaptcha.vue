<template>
  <div class="captcha-space">
    <label class="captcha-label">
      <span class="captcha-icon">🛡️</span>
      Verifica di sicurezza
    </label>
    
    <div class="captcha-container">
      <div class="canvas-wrapper" :class="{ 'hover-effect': true }">
        <canvas
          ref="canvasRef"
          width="220"
          height="80"
          class="captcha-canvas"
        />
      </div>
      
      <button
        type="button"
        @click="generateCaptcha"
        class="btn-refresh"
        title="Genera nuovo codice"
      >
        ⟳
      </button>
    </div>

    <input
      v-model="userAnswer"
      type="text"
      placeholder="Inserisci il codice qui sopra"
      class="captcha-input"
      @keyup.enter="$emit('validate', isValidated)"
    />

    <div v-if="userAnswer" :class="['captcha-feedback', { success: isValidated }]">
      <span class="feedback-dot" :class="{ validated: isValidated }"></span>
      <span>{{ isValidated ? 'Verifica completata!' : 'Il codice non corrisponde, riprova' }}</span>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, onMounted } from 'vue'

const props = defineProps<{
  onValidate: (isValid: boolean) => void
}>()

const canvasRef = ref<HTMLCanvasElement | null>(null)
const captchaText = ref('')
const userAnswer = ref('')
const isValidated = ref(false)

const generateRandomString = () => {
  const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'
  let result = ''
  for (let i = 0; i < 6; i++) {
    result += chars.charAt(Math.floor(Math.random() * chars.length))
  }
  return result
}

const drawCaptcha = (text: string) => {
  const canvas = canvasRef.value
  if (!canvas) return

  const ctx = canvas.getContext('2d')
  if (!ctx) return

  // Clear canvas
  ctx.clearRect(0, 0, canvas.width, canvas.height)

  // Background gradient (dark theme)
  const gradient = ctx.createLinearGradient(0, 0, canvas.width, canvas.height)
  gradient.addColorStop(0, '#1e293b')
  gradient.addColorStop(1, '#0f172a')
  ctx.fillStyle = gradient
  ctx.fillRect(0, 0, canvas.width, canvas.height)

  // Add noise lines
  for (let i = 0; i < 5; i++) {
    ctx.strokeStyle = `rgba(${100 + Math.random() * 100}, ${100 + Math.random() * 100}, ${100 + Math.random() * 100}, 0.3)`
    ctx.lineWidth = 1 + Math.random() * 2
    ctx.beginPath()
    ctx.moveTo(Math.random() * canvas.width, Math.random() * canvas.height)
    ctx.lineTo(Math.random() * canvas.width, Math.random() * canvas.height)
    ctx.stroke()
  }

  // Add noise dots
  for (let i = 0; i < 50; i++) {
    ctx.fillStyle = `rgba(${100 + Math.random() * 150}, ${100 + Math.random() * 150}, ${100 + Math.random() * 150}, 0.4)`
    ctx.beginPath()
    ctx.arc(
      Math.random() * canvas.width,
      Math.random() * canvas.height,
      1 + Math.random() * 2,
      0,
      Math.PI * 2
    )
    ctx.fill()
  }

  // Draw captcha text with distortion
  const chars = text.split('')
  const baseX = 20
  const baseY = 50

  chars.forEach((char, index) => {
    ctx.save()

    // Random font size
    const fontSize = 28 + Math.random() * 8
    ctx.font = `bold ${fontSize}px Arial`

    // Random rotation
    const rotation = (Math.random() - 0.5) * 0.4
    const x = baseX + index * 30
    const y = baseY + (Math.random() - 0.5) * 10

    ctx.translate(x, y)
    ctx.rotate(rotation)

    // Colors for dark background (brighter)
    const colors = ['#38bdf8', '#818cf8', '#a78bfa', '#34d399', '#fbbf24']
    ctx.fillStyle = colors[Math.floor(Math.random() * colors.length)]

    // Draw text with shadow
    ctx.shadowColor = 'rgba(0, 0, 0, 0.5)'
    ctx.shadowBlur = 3
    ctx.shadowOffsetX = 1
    ctx.shadowOffsetY = 1

    ctx.fillText(char, 0, 0)
    ctx.restore()
  })

  // Add curved lines over the text
  for (let i = 0; i < 3; i++) {
    ctx.strokeStyle = `rgba(${100 + Math.random() * 100}, ${100 + Math.random() * 100}, ${100 + Math.random() * 100}, 0.25)`
    ctx.lineWidth = 1.5
    ctx.beginPath()
    ctx.moveTo(Math.random() * 50, Math.random() * canvas.height)
    ctx.bezierCurveTo(
      Math.random() * canvas.width,
      Math.random() * canvas.height,
      Math.random() * canvas.width,
      Math.random() * canvas.height,
      canvas.width - Math.random() * 50,
      Math.random() * canvas.height
    )
    ctx.stroke()
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

onMounted(() => {
  generateCaptcha()
})

watch(userAnswer, (newValue) => {
  const isCorrect = newValue.toUpperCase() === captchaText.value
  isValidated.value = isCorrect && newValue !== ''
  props.onValidate(isCorrect && newValue !== '')
})
</script>

<style scoped>
.captcha-space {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.captcha-label {
  font-size: 14px;
  font-weight: 500;
  color: #94a3b8;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.captcha-icon {
  font-size: 16px;
}

.captcha-container {
  display: flex;
  gap: 0.75rem;
  align-items: flex-start;
}

.canvas-wrapper {
  position: relative;
  border-radius: 12px;
  overflow: hidden;
  transition: all 0.3s ease;
}

.canvas-wrapper.hover-effect:hover {
  box-shadow: 0 4px 20px rgba(56, 189, 248, 0.15);
}

.captcha-canvas {
  border: 2px solid rgba(56, 189, 248, 0.2);
  border-radius: 12px;
  display: block;
  transition: all 0.3s ease;
}

.canvas-wrapper:hover .captcha-canvas {
  border-color: rgba(56, 189, 248, 0.4);
}

.btn-refresh {
  width: 44px;
  height: 44px;
  padding: 0;
  border: 2px solid rgba(56, 189, 248, 0.2);
  background: rgba(30, 41, 59, 0.5);
  border-radius: 12px;
  cursor: pointer;
  font-size: 20px;
  font-weight: 600;
  color: #38bdf8;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
}

.btn-refresh:hover {
  border-color: #38bdf8;
  background: rgba(56, 189, 248, 0.1);
  transform: rotate(180deg);
}

.btn-refresh:active {
  transform: rotate(180deg) scale(0.95);
}

.captcha-input {
  width: 100%;
  padding: 0.75rem 1rem;
  border: 2px solid rgba(100, 116, 139, 0.3);
  border-radius: 12px;
  font-size: 14px;
  font-family: 'Inter', sans-serif;
  background: rgba(30, 41, 59, 0.5);
  color: #e2e8f0;
  transition: border-color 0.3s ease, box-shadow 0.3s ease;
  box-sizing: border-box;
  letter-spacing: 2px;
  text-transform: uppercase;
}

.captcha-input::placeholder {
  color: #475569;
  letter-spacing: normal;
  text-transform: none;
}

.captcha-input:focus {
  outline: none;
  border-color: rgba(56, 189, 248, 0.5);
  box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.1);
}

.captcha-feedback {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 13px;
  color: #f87171;
  padding: 0.25rem 0;
}

.captcha-feedback.success {
  color: #34d399;
}

.feedback-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background-color: #f87171;
  display: inline-block;
}

.feedback-dot.validated {
  background-color: #34d399;
}

@media (max-width: 480px) {
  .captcha-container {
    flex-direction: column;
  }

  .btn-refresh {
    width: 100%;
    height: auto;
    padding: 0.75rem;
  }
}
</style>
