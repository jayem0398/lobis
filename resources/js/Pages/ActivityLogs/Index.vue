<template>
  <app-layout>
    <div class="dashboard-page-wrapper">
      
      <!-- HERO HEADER BAR -->
      <div class="welcome-hero-banner mb-24">
        <div class="hero-text-content">
          <h1>System Activity Logs &amp; Audit Trail</h1>
          <p>Real-time security logs, user actions, invoice updates, and administrative audit trails.</p>
        </div>
      </div>

      <!-- MAIN CARD PANEL -->
      <div class="analytics-card-panel">
        <div class="panel-header-box flex-between-box">
          <h3>System Audit Trail (Admin Restricted)</h3>
          <span class="text-xs font-bold text-slate-300">Total Entries: {{ totalLogs }}</span>
        </div>

        <div class="panel-body-content p-24">
          <!-- SEARCH & ACTION FILTERS -->
          <div class="flex-between-box mb-20 gap-4 flex-wrap">
            <div class="search-input-box" style="max-width: 360px;">
              <svg class="search-icon" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
              <input v-model="searchQuery" @input="fetchLogs(1)" type="text" placeholder="Search by user, action, or details..." />
            </div>

            <div class="flex items-center gap-3">
              <select v-model="selectedAction" @change="fetchLogs(1)" class="form-select-dropdown" style="width: 200px; height: 38px;">
                <option value="">All Action Types</option>
                <option value="User Login">User Login</option>
                <option value="Create Invoice">Create Invoice</option>
                <option value="Update Invoice">Update Invoice</option>
                <option value="Approve Invoice">Approve Invoice</option>
                <option value="Delete Invoice">Delete Invoice</option>
              </select>
              <button @click="resetFilters" class="btn-secondary-action" style="height: 38px;">Reset</button>
            </div>
          </div>

          <!-- TABLE -->
          <div class="items-table-frame">
            <table class="enterprise-data-table">
              <thead>
                <tr>
                  <th style="width: 170px;">Timestamp</th>
                  <th style="width: 160px;">User</th>
                  <th style="width: 150px;">Action</th>
                  <th>Activity Description</th>
                  <th style="width: 120px; text-align: right;">IP Address</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="log in logs" :key="log.id">
                  <td class="text-xs font-medium text-slate-500">{{ formatDate(log.created_at) }}</td>
                  <td>
                    <span class="font-bold text-slate-900 block">{{ log.user_name || 'System' }}</span>
                  </td>
                  <td>
                    <span :class="['action-chip', getActionChipClass(log.action)]">
                      {{ log.action }}
                    </span>
                  </td>
                  <td class="text-xs text-slate-700 font-medium">{{ log.description }}</td>
                  <td class="text-right text-xs text-slate-400 font-mono">{{ log.ip_address || '127.0.0.1' }}</td>
                </tr>
                <tr v-if="logs.length === 0">
                  <td colspan="5" class="empty-data-message">No activity logs recorded matching your filter.</td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- PAGINATION -->
          <div class="flex-between-box mt-20 pt-16 border-t border-slate-200" v-if="pagination.last_page > 1">
            <span class="text-xs text-slate-500 font-medium">Page {{ pagination.current_page }} of {{ pagination.last_page }}</span>
            <div class="flex gap-2">
              <button 
                @click="fetchLogs(pagination.current_page - 1)" 
                :disabled="pagination.current_page <= 1"
                class="btn-pagination"
              >
                Previous
              </button>
              <button 
                @click="fetchLogs(pagination.current_page + 1)" 
                :disabled="pagination.current_page >= pagination.last_page"
                class="btn-pagination"
              >
                Next
              </button>
            </div>
          </div>

        </div>
      </div>

    </div>
  </app-layout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';

const logs = ref([]);
const totalLogs = ref(0);
const searchQuery = ref('');
const selectedAction = ref('');

const pagination = ref({
  current_page: 1,
  last_page: 1
});

const fetchLogs = async (page = 1) => {
  try {
    const res = await axios.get('/api/activity-logs', {
      params: {
        page,
        search: searchQuery.value,
        action: selectedAction.value
      }
    });
    const responseData = res.data.data;
    logs.value = responseData.data || [];
    totalLogs.value = responseData.total || 0;
    pagination.value = {
      current_page: responseData.current_page || 1,
      last_page: responseData.last_page || 1
    };
  } catch (error) {
    console.error('Failed to load activity logs:', error);
  }
};

const formatDate = (isoStr) => {
  if (!isoStr) return '';
  return new Date(isoStr).toLocaleString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

const getActionChipClass = (action) => {
  if (action.includes('Login')) return 'chip-blue';
  if (action.includes('Create')) return 'chip-green';
  if (action.includes('Update') || action.includes('Edit')) return 'chip-amber';
  if (action.includes('Delete')) return 'chip-red';
  return 'chip-slate';
};

const resetFilters = () => {
  searchQuery.value = '';
  selectedAction.value = '';
  fetchLogs(1);
};

onMounted(() => {
  fetchLogs(1);
});
</script>

<style scoped>
.dashboard-page-wrapper { max-width: 1400px; margin: 0 auto; padding-left: 1in; padding-right: 1in; padding-top: 24px; padding-bottom: 24px; box-sizing: border-box; }
.mb-24 { margin-bottom: 24px; }
.mb-20 { margin-bottom: 20px; }
.p-24 { padding: 24px; }

.welcome-hero-banner { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px 28px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }
.hero-text-content h1 { font-size: 20px; font-weight: 800; color: #0f172a; margin: 0 0 6px 0; letter-spacing: -0.5px; }
.hero-text-content p { font-size: 13px; color: #64748b; margin: 0; }

.analytics-card-panel { background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }
.panel-header-box { background: #0f172a; padding: 16px 24px; color: #ffffff; }
.panel-header-box h3 { color: #ffffff; font-size: 13px; font-weight: 700; text-transform: uppercase; margin: 0; }

.search-input-box { position: relative; width: 100%; }
.search-input-box input { width: 100%; height: 38px; padding-left: 36px; padding-right: 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px; outline: none; background: #ffffff; color: #1e293b; }
.search-icon { position: absolute; left: 12px; top: 11px; color: #94a3b8; }

.form-select-dropdown { height: 38px; padding: 0 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px; background: #ffffff; color: #1e293b; }
.btn-secondary-action { padding: 0 14px; border: 1px solid #cbd5e1; background: #f8fafc; border-radius: 8px; font-size: 12px; font-weight: 700; color: #475569; cursor: pointer; }
.btn-secondary-action:hover { background: #f1f5f9; }

.enterprise-data-table { width: 100%; border-collapse: collapse; }
.enterprise-data-table th { background: #f8fafc; padding: 12px 16px; font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; }
.enterprise-data-table td { padding: 14px 16px; border-bottom: 1px solid #e2e8f0; font-size: 13px; color: #334155; }

.action-chip { display: inline-block; padding: 3px 8px; border-radius: 6px; font-size: 11px; font-weight: 700; }
.chip-blue { background: #e0f2fe; color: #0369a1; }
.chip-green { background: #d1fae5; color: #047857; }
.chip-amber { background: #fef3c7; color: #b45309; }
.chip-red { background: #fee2e2; color: #b91c1c; }
.chip-slate { background: #f1f5f9; color: #475569; }

.btn-pagination { padding: 6px 14px; border: 1px solid #cbd5e1; background: #ffffff; border-radius: 6px; font-size: 12px; font-weight: 600; color: #334155; cursor: pointer; }
.btn-pagination:disabled { opacity: 0.5; cursor: not-allowed; }
.empty-data-message { padding: 40px; text-align: center; color: #94a3b8; font-style: italic; }
</style>
