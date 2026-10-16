<template>
  <div class="skeleton-card">
    <div class="skeleton-line title" :style="titleStyle"></div>
    <div class="skeleton-line large" :style="valueStyle"></div>
    <div class="skeleton-line small" :style="subStyle"></div>
  </div>
</template>

<script setup>
defineProps({
  titleStyle: { type: String, default: 'width: 60%;' },
  valueStyle: { type: String, default: 'width: 90%; margin-top: 10px; height: 24px;' },
  subStyle: { type: String, default: 'width: 80%; margin-top: 8px;' }
})
</script>

<style scoped>
.skeleton-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 18px 20px;
  border-left: 4px solid #e2e8f0;
  box-shadow: 0 2px 4px rgba(0,0,0,0.02);
  animation: pulse 1.6s ease-in-out infinite;
}
.skeleton-line {
  background: linear-gradient(90deg, #f1f5f9 25%, #f8fafc 50%, #f1f5f9 75%);
  background-size: 200% 100%;
  animation: shimmer 1.6s infinite;
  border-radius: 6px;
  height: 12px;
}
.skeleton-line.title { height: 12px; }
.skeleton-line.large { height: 28px; margin-top: 10px; }
.skeleton-line.small { height: 10px; margin-top: 8px; }

@keyframes shimmer {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}
@keyframes pulse {
  0%, 100% { opacity: 0.95; }
  50% { opacity: 0.7; }
}
</style>