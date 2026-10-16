<template>
  <div class="skeleton-table">
    <div v-for="n in rows" :key="n" class="skeleton-row">
      <div v-for="i in cols" :key="i" class="skeleton-line" :style="lineStyle(i)"></div>
    </div>
    <div v-if="rows === 0" class="skeleton-row empty"></div>
  </div>
</template>

<script setup>
const props = defineProps({
  rows: { type: Number, default: 6 },
  cols: { type: Number, default: 4 },
  minWidth: { type: String, default: '40%' },
  maxWidth: { type: String, default: '90%' }
})

const lineStyle = (i) => {
  const rnd = 40 + (i * 12) % 50
  return `width: ${rnd}%`
}
</script>

<style scoped>
.skeleton-table {
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  overflow: hidden;
  background: #fff;
  animation: pulse 1.6s ease-in-out infinite;
}
.skeleton-row {
  display: flex;
  gap: 16px;
  padding: 16px 18px;
  border-bottom: 1px solid #f1f5f9;
}
.skeleton-row:last-child { border-bottom: none; }
.skeleton-line {
  flex: 1;
  background: linear-gradient(90deg, #f1f5f9 25%, #f8fafc 50%, #f1f5f9 75%);
  background-size: 200% 100%;
  animation: shimmer 1.6s infinite;
  border-radius: 6px;
  height: 14px;
}
@keyframes shimmer {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}
@keyframes pulse {
  0%, 100% { opacity: 0.95; }
  50% { opacity: 0.7; }
}
</style>