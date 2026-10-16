<template>
  <app-layout>
    <div class="dashboard-page-wrapper">
      
      <!-- HERO HEADER BAR -->
      <div class="welcome-hero-banner mb-24">
        <div class="hero-text-content">
          <div class="hero-title-badge">
            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
            <span>SECURITY AUDIT TRAIL</span>
          </div>
          <h1>System Activity &amp; User Audit Logs</h1>
          <p>Real-time audit log monitoring security events, user authentication, invoice operations, and system mutations.</p>
        </div>
        <div class="hero-stats-quick">
          <div class="stat-pill">
            <span class="stat-dot amber"></span>
            <span>Live Audit Logging Active</span>
          </div>
        </div>
      </div>

      <!-- AUDIT SUMMARY METRICS CARDS -->
      <div class="kpi-grid-container mb-24">
        <template v-if="isLoading">
          <SkeletonCard v-for="n in 4" :key="n" />
        </template>
        <template v-else>
        <div class="kpi-card-item border-l-4 border-l-slate-700">
          <div class="kpi-info">
            <span class="kpi-label">Total Audit Events</span>
            <span class="kpi-number">{{ totalLogs }}</span>
            <small class="text-xs text-slate-500 mt-1">Recorded log entries</small>
          </div>
          <div class="kpi-icon-box bg-slate-100 text-slate-700">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
          </div>
        </div>

        <div class="kpi-card-item border-l-4 border-l-amber-500">
          <div class="kpi-info">
            <span class="kpi-label">Authentication Events</span>
            <span class="kpi-number text-amber-700">{{ loginCount }}</span>
            <small class="text-xs text-slate-500 mt-1">Sign-in &amp; Session logs</small>
          </div>
          <div class="kpi-icon-box bg-amber-50 text-amber-600">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path><polyline points="10 17 15 12 10 7"></polyline><line x1="15" y1="12" x2="3" y2="12"></line></svg>
          </div>
        </div>

        <div class="kpi-card-item border-l-4 border-l-slate-700">
          <div class="kpi-info">
            <span class="kpi-label">Create &amp; Approve Logs</span>
            <span class="kpi-number text-slate-800">{{ createCount }}</span>
            <small class="text-xs text-slate-500 mt-1">New records created</small>
          </div>
          <div class="kpi-icon-box bg-slate-100 text-slate-700">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
          </div>
        </div>

        <div class="kpi-card-item border-l-4 border-l-amber-500">
          <div class="kpi-info">
            <span class="kpi-label">Updates &amp; Edits</span>
            <span class="kpi-number text-amber-700">{{ updateCount }}</span>
            <small class="text-xs text-slate-500 mt-1">Data modifications</small>
          </div>
          <div class="kpi-icon-box bg-amber-50 text-amber-600">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.5 2v6h-6"></path><path d="M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"></path></svg>
          </div>
        </div>
        </template>
      </div>

      <!-- MAIN CARD PANEL -->
      <div class="analytics-card-panel">
        <div class="panel-header-box flex-between-box">
          <div class="flex items-center gap-2">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" class="text-amber-400"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
            <h3>Activity Logs &amp; Audit Trail</h3>
          </div>
          <span class="text-xs font-bold text-amber-400">Restricted Admin Access</span>
        </div>

        <div class="panel-body-content p-24">
          <!-- SEARCH & ACTION FILTERS TOOLBAR -->
          <div class="toolbar-header-row mb-20">
            <div class="search-input-box" style="width: 360px; max-width: 100%;">
              <svg class="search-icon" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
              <input v-model="searchQuery" @input="fetchLogs(1)" type="text" placeholder="Search by user, action, IP, or details..." />
            </div>

            <div class="filters-right-box">
              <!-- ACTION FILTER DROPDOWN -->
              <select v-model="selectedAction" @change="fetchLogs(1)" class="form-select-dropdown">
                <option value="">All Action Types</option>
                <option value="User Login">User Login</option>
                <option value="Create Invoice">Create Invoice</option>
                <option value="Update Invoice">Update Invoice</option>
                <option value="Approve Invoice">Approve Invoice</option>
                <option value="Delete Invoice">Delete Invoice</option>
              </select>
              <button @click="resetFilters" class="btn-secondary-action">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" class="inline-block mr-1"><path d="M21.5 2v6h-6"></path><path d="M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"></path></svg>
                Reset
              </button>
            </div>
          </div>

          <!-- TABLE -->
          <div class="items-table-frame">
              <SkeletonTable v-if="isLoading" :rows="8" :cols="5" />
              <template v-else>
            <table class="enterprise-data-table">
              <thead>
                <tr>
                  <th style="width: 180px;">Timestamp</th>
                  <th style="width: 200px;">User / Actor</th>
                  <th style="width: 160px;">Action</th>
                  <th>Activity Description</th>
                  <th style="width: 140px; text-align: right;">IP Address</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="log in logs" :key="log.id" class="log-row-item">
                  <td class="text-xs font-medium text-slate-500">
                    <div class="flex items-center gap-1.5">
                      <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" class="text-slate-400"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 16 14"></polyline></svg>
                      <span>{{ formatDate(log.created_at) }}</span>
                    </div>
                  </td>
                  <td>
                    <div class="user-actor-info">
                      <div class="user-avatar-circle">
                        {{ getUserInitial(log.user_name) }}
                      </div>
                      <div class="user-text-meta">
                        <span class="user-name-text">{{ log.user_name || 'System' }}</span>
                      </div>
                    </div>
                  </td>
                  <td>
                    <span :class="['action-chip', getActionChipClass(log.action)]">
                      <span class="chip-dot"></span>
                      {{ log.action }}
                    </span>
                  </td>
                  <td class="text-xs text-slate-700 font-medium leading-relaxed">
                    {{ log.description }}
                  </td>
                  <td class="text-right">
                    <span class="ip-address-badge">
                      <svg viewBox="0 0 24 24" width="11" height="11" fill="none" stroke="currentColor" stroke-width="2" class="mr-1 inline-block"><rect x="2" y="2" width="20" height="8" rx="2" ry="2"></rect><rect x="2" y="14" width="20" height="8" rx="2" ry="2"></rect><line x1="6" y1="6" x2="6.01" y2="6"></line><line x1="6" y1="18" x2="6.01" y2="18"></line></svg>
                      {{ log.ip_address || '127.0.0.1' }}
                    </span>
                  </td>
                </tr>
                <tr v-if="logs.length === 0 && !isLoading">
                  <td colspan="5" class="empty-data-message">
                    <div class="empty-state-box">
                      <svg viewBox="0 0 24 24" width="36" height="36" fill="none" stroke="currentColor" stroke-width="1.5" class="text-slate-300 mb-2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                      <p class="font-semibold text-slate-600 mb-1">No activity logs found</p>
                      <span class="text-xs text-slate-400">There are no audit logs matching your current search or action filter.</span>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
              </template>
          </div>

          <!-- PAGINATION -->
          <div class="flex-between-box mt-20 pt-16 border-t border-slate-200" v-if="pagination.last_page > 1">
            <span class="text-xs text-slate-500 font-semibold">
              Showing Page {{ pagination.current_page }} of {{ pagination.last_page }} ({{ totalLogs }} Total Logs)
            </span>
            <div class="flex gap-2">
              <button 
                @click="fetchLogs(pagination.current_page - 1)" 
                :disabled="pagination.current_page <= 1"
                class="btn-pagination"
              >
                &larr; Previous
              </button>
              <button 
                @click="fetchLogs(pagination.current_page + 1)" 
                :disabled="pagination.current_page >= pagination.last_page"
                class="btn-pagination"
              >
                Next &rarr;
              </button>
            </div>
          </div>

        </div>
      </div>

    </div>
  </app-layout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useActivityLogs } from '@/composables/useActivityLogs';
import SkeletonCard from '@/Components/Skeleton/SkeletonCard.vue';
import SkeletonTable from '@/Components/Skeleton/SkeletonTable.vue';
import './Index.css';

const {
  logs,
  totalLogs,
  searchQuery,
  selectedAction,
  pagination,
  isLoading,
  loginCount,
  createCount,
  updateCount,
  fetchLogs,
  formatDate,
  getUserInitial,
  getActionChipClass,
  resetFilters
} = useActivityLogs();
</script>

