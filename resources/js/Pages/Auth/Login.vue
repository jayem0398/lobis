<template>
  <div class="login-page-wrapper">
    <div class="login-card">
      
      <div class="login-card-header">
        <div class="app-logo-mark">
          <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>
        </div>
        <h2>Enterprise Portal</h2>
        <p>Sign in to manage your workspace</p>
      </div>

      <form @submit.prevent="handleLogin" class="login-card-body">
        
        <div class="form-group">
          <label>Email Address</label>
          <div class="input-container">
            <svg class="input-icon" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
            <input v-model="form.email" type="email" required placeholder="name@company.com" autocomplete="email" />
          </div>
        </div>

        <div class="form-group">
          <label>Password</label>
          <div class="input-container">
            <svg class="input-icon" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
            <input v-model="form.password" :type="showPassword ? 'text' : 'password'" required placeholder="••••••••" autocomplete="current-password" />
            <button type="button" @click="showPassword = !showPassword" class="password-toggle-btn" tabindex="-1">
              <svg v-if="!showPassword" viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
              <svg v-else viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
            </button>
          </div>
        </div>

        <div class="form-options">
          <label class="remember-label">
            <input v-model="form.remember" type="checkbox" />
            <span>Remember this device</span>
          </label>
        </div>

        <button type="submit" :disabled="isSubmitting" class="btn-submit">
          {{ isSubmitting ? 'Signing in...' : 'Sign In' }}
        </button>

      </form>

    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import axios from 'axios';
import { useToast } from 'vue-toastification';
import { router } from '@inertiajs/vue3';

const toast = useToast();
const isSubmitting = ref(false);
const showPassword = ref(false);

const form = ref({
  email: '',
  password: '',
  remember: false
});

const handleLogin = async () => {
  isSubmitting.value = true;
  try {
    await axios.get('/sanctum/csrf-cookie');
    await axios.post('/api/login', form.value);
    toast.success('Welcome back!');
    router.visit('/dashboard'); 
  } catch (error) {
    console.error('Login error:', error);
    toast.error(error.response?.data?.message || 'Invalid email or password.');
  } finally {
    isSubmitting.value = false;
  }
};
</script>

<style scoped>
.login-page-wrapper {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: #f8fafc;
  font-family: inherit;
  padding: 24px;
  box-sizing: border-box;
}

.login-card {
  background: #ffffff;
  width: 100%;
  max-width: 400px;
  border-radius: 14px;
  border: 1px solid #e2e8f0;
  box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.05), 0 8px 10px -6px rgba(15, 23, 42, 0.05);
  overflow: hidden;
}

.login-card-header {
  padding: 32px 32px 24px 32px;
  text-align: center;
  border-bottom: 1px solid #f1f5f9;
}

.app-logo-mark {
  width: 48px;
  height: 48px;
  background: #eff6ff;
  border: 1px solid #dbeafe;
  color: #2563eb;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 16px auto;
}

.login-card-header h2 {
  font-size: 18px;
  font-weight: 700;
  color: #0f172a;
  margin: 0 0 4px 0;
  letter-spacing: -0.3px;
}

.login-card-header p {
  font-size: 13px;
  color: #64748b;
  margin: 0;
}

.login-card-body {
  padding: 28px 32px;
  display: flex;
  flex-direction: column;
  gap: 18px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.form-group label {
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  color: #475569;
  letter-spacing: 0.5px;
}

.input-container {
  position: relative;
  display: flex;
  align-items: center;
}

.input-icon {
  position: absolute;
  left: 14px;
  color: #94a3b8;
  pointer-events: none;
}

.input-container input {
  width: 100%;
  padding: 11px 14px 11px 42px;
  background: #ffffff;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  font-size: 13px;
  color: #1e293b;
  outline: none;
  transition: all 0.2s ease;
  box-sizing: border-box;
}

.input-container input:focus {
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.input-container input::placeholder {
  color: #94a3b8;
}

.password-toggle-btn {
  position: absolute;
  right: 12px;
  background: none;
  border: none;
  color: #94a3b8;
  cursor: pointer;
  padding: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 4px;
}

.password-toggle-btn:hover {
  color: #475569;
}

.form-options {
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-size: 12px;
}

.remember-label {
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  color: #334155;
  font-weight: 500;
}

.remember-label input {
  accent-color: #2563eb;
  width: 15px;
  height: 15px;
  border-radius: 4px;
  cursor: pointer;
}

.btn-submit {
  background: #0f172a;
  color: #ffffff;
  padding: 12px;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 700;
  text-transform: uppercase;
  border: none;
  cursor: pointer;
  width: 100%;
  letter-spacing: 0.5px;
  transition: background 0.2s ease;
  margin-top: 4px;
}

.btn-submit:hover:not(:disabled) {
  background: #1e293b;
}

.btn-submit:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
</style>