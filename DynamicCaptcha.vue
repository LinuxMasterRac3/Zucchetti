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
        title="Genera codice"
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

  // Background gradient
  const gradient = ctx.createLinearGradient(0, 0, canvas.width, canvas.height)
  gradient.addColorStop(0, '#f0f4f8')
  gradient.addColorStop(1, '#d9e2ec')
  ctx.fillStyle = gradient
  ctx.fillRect(0, 0, canvas.width, canvas.height)

  // Add noise lines
  for (let i = 0; i < 5; i++) {
    ctx.strokeStyle = `rgba(${Math.random() * 100}, ${Math.random() * 100}, ${Math.random() * 100}, 0.3)`
    ctx.lineWidth = 1 + Math.random() * 2
    ctx.beginPath()
    ctx.moveTo(Math.random() * canvas.width, Math.random() * canvas.height)
    ctx.lineTo(Math.random() * canvas.width, Math.random() * canvas.height)
    ctx.stroke()
  }

  // Add noise dots
  for (let i = 0; i < 50; i++) {
    ctx.fillStyle = `rgba(${Math.random() * 150}, ${Math.random() * 150}, ${Math.random() * 150}, 0.4)`
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

    // Random color (darker for readability)
    const colors = ['#1a365d', '#2c5282', '#2d3748', '#744210', '#22543d']
    ctx.fillStyle = colors[Math.floor(Math.random() * colors.length)]

    // Draw text with shadow
    ctx.shadowColor = 'rgba(0, 0, 0, 0.3)'
    ctx.shadowBlur = 2
    ctx.shadowOffsetX = 1
    ctx.shadowOffsetY = 1

    ctx.fillText(char, 0, 0)
    ctx.restore()
  })

  // Add curved lines over the text
  for (let i = 0; i < 3; i++) {
    ctx.strokeStyle = `rgba(${Math.random() * 100}, ${Math.random() * 100}, ${Math.random() * 100}, 0.25)`
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
  margin-bottom: 1.5rem;
}

.captcha-label {
  font-size: 14px;
  font-weight: 600;
  color: #333;
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
  border-radius: 5px;
  overflow: hidden;
  transition: all 0.3s ease;
}

.canvas-wrapper.hover-effect:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.captcha-canvas {
  border: 2px solid #e0e0e0;
  border-radius: 5px;
  background: white;
  display: block;
  transition: box-shadow 0.3s ease;
}

.canvas-wrapper:hover .captcha-canvas {
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.btn-refresh {
  width: 44px;
  height: 44px;
  padding: 0;
  border: 2px solid #e0e0e0;
  background: white;
  border-radius: 5px;
  cursor: pointer;
  font-size: 18px;
  font-weight: 600;
  color: #667eea;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
}

.btn-refresh:hover {
  border-color: #667eea;
  background-color: rgba(102, 126, 234, 0.05);
  transform: rotate(180deg);
}

.btn-refresh:active {
  transform: rotate(180deg) scale(0.95);
}

.captcha-input {
  width: 100%;
  padding: 0.75rem;
  border: 2px solid #e0e0e0;
  border-radius: 5px;
  font-size: 14px;
  font-family: inherit;
  transition: border-color 0.3s ease, box-shadow 0.3s ease;
}

.captcha-input:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.captcha-feedback {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 13px;
  color: #c33;
  padding: 0.5rem 0;
}

.captcha-feedback.success {
  color: #2d8659;
}

.feedback-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background-color: #c33;
  display: inline-block;
}

.feedback-dot.validated {
  background-color: #2d8659;
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
