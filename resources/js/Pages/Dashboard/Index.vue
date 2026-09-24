<template>
  <app-layout>
    <div class="dashboard-page-wrapper">
      
      <div class="welcome-hero-banner">
        <div class="hero-text-content">
          <h1>Welcome back, {{ isAdmin ? 'Administrator' : (currentUser.name || 'Team Member') }}!</h1>
          <p>{{ isAdmin ? 'Here is your daily summary of collections, active billings, and revenue performance.' : 'Here is the summary of your team and personal billing collections.' }}</p>
        </div>
        <div class="hero-actions-group">
          <a href="/invoices/create" class="hero-action-button primary">+ New Invoice</a>
          <a v-if="isAdmin" href="/clients" class="hero-action-button secondary">+ Add Client</a>
        </div>
      </div>

      <div class="metrics-grid-container">
        <div class="metric-card-box">
          <div class="metric-top-row">
            <span class="metric-card-title">Total Revenue</span>
            <div class="metric-icon-box">
              <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
            </div>
          </div>
          <div class="metric-card-value">₱{{ formatCurrency(metrics.total_revenue || 0) }}</div>
          <div class="metric-trend text-muted">{{ isAdmin ? 'Real-time database total paid revenue' : 'Your assigned total collected revenue' }}</div>
        </div>

        <div class="metric-card-box">
          <div class="metric-top-row">
            <span class="metric-card-title">Pending Collections</span>
            <div class="metric-icon-box">
              <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
            </div>
          </div>
          <div class="metric-card-value">₱{{ formatCurrency(metrics.pending_amount || 0) }}</div>
          <div class="metric-trend text-muted">{{ metrics.unpaid_invoices || 0 }} unpaid invoices awaiting collection</div>
        </div>

        <div class="metric-card-box">
          <div class="metric-top-row">
            <span class="metric-card-title">{{ isAdmin ? 'Total Active Clients' : 'Assigned Clients' }}</span>
            <div class="metric-icon-box">
              <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
            </div>
          </div>
          <div class="metric-card-value">{{ metrics.total_clients || 0 }}</div>
          <div class="metric-trend text-muted">Active in your view</div>
        </div>

        <div class="metric-card-box">
          <div class="metric-top-row">
            <span class="metric-card-title">Active Invoices</span>
            <div class="metric-icon-box">
              <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
            </div>
          </div>
          <div class="metric-card-value">{{ metrics.total_invoices || 0 }}</div>
          <div class="metric-trend text-muted">Total billing records</div>
        </div>
      </div>

      <div class="analytics-card-panel mb-24">
        <div class="panel-header-box">
          <h3>Aging Receivables Breakdown</h3>
          <span class="panel-subtitle">Click segment to filter invoices</span>
        </div>
        <div class="panel-body-content">
          <div class="aging-bar-container">
            <div @click="filterInvoicesByAging('current')" class="aging-segment seg-current" style="width: 55%;" title="Click to view Current Invoices">Current</div>
            <div @click="filterInvoicesByAging('1-30')" class="aging-segment seg-30" style="width: 25%;" title="Click to view 1-30 Days Overdue">1-30 Days</div>
            <div @click="filterInvoicesByAging('31-60')" class="aging-segment seg-60" style="width: 12%;" title="Click to view 31-60 Days Overdue">31-60 Days</div>
            <div @click="filterInvoicesByAging('60+')" class="aging-segment seg-over" style="width: 8%;" title="Click to view 60+ Days Overdue">60+ Days</div>
          </div>
          
          <div class="aging-legend">
            <span @click="filterInvoicesByAging('current')" class="legend-item clickable"><span class="dot dot-current"></span> Current</span>
            <span @click="filterInvoicesByAging('1-30')" class="legend-item clickable"><span class="dot dot-30"></span> 1-30 Days Overdue</span>
            <span @click="filterInvoicesByAging('31-60')" class="legend-item clickable"><span class="dot dot-60"></span> 31-60 Days Overdue</span>
            <span @click="filterInvoicesByAging('60+')" class="legend-item clickable"><span class="dot dot-over"></span> 60+ Days Overdue</span>
          </div>
        </div>
      </div>

      <div class="dashboard-dual-grid">
        <div class="analytics-card-panel">
          <div class="panel-header-box">
            <h3>Monthly Revenue Overview</h3>
            <span class="panel-subtitle">FY {{ new Date().getFullYear() }}</span>
          </div>
          <div class="panel-body-content">
            <div class="analytics-bar-chart">
              <div v-for="(item, index) in chartData" :key="index" class="chart-column-item">
                <div class="chart-bars-wrapper">
                  <div class="single-bar paid" :style="{ height: getBarHeight(item.paid_amount) }"></div>
                  <div class="single-bar unpaid" :style="{ height: getBarHeight(item.unpaid_amount) }"></div>
                </div>
                <span class="chart-month-label">{{ item.month_name ? item.month_name.substring(0, 3) : '' }}</span>
              </div>
            </div>
            <div class="chart-legend-bottom">
              <span class="legend-item"><span class="dot paid-dot"></span> Collected Revenue</span>
              <span class="legend-item"><span class="dot unpaid-dot"></span> Pending Receivables</span>
            </div>
          </div>
        </div>

        <div class="analytics-card-panel">
          <div class="panel-header-box">
            <h3>Recent Billing Invoices</h3>
            <a href="/invoices" class="btn-view-all">View All →</a>
          </div>
          <div class="panel-body-content" style="padding: 16px;">
            <div class="recent-table-wrapper">
              <table class="recent-billing-table">
                <thead>
                  <tr>
                    <th>Invoice #</th>
                    <th>Client</th>
                    <th>Amount</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="inv in recentInvoices" :key="inv.id">
                    <td class="font-bold text-slate-900">{{ inv.invoice_number }}</td>
                    <td>{{ inv.client_name }}</td>
                    <td class="font-bold">₱{{ formatCurrency(inv.amount) }}</td>
                    <td>
                      <span :class="['status-pill', getStatusClass(inv.status)]">
                        {{ inv.status }}
                      </span>
                    </td>
                  </tr>
                  <tr v-if="recentInvoices.length === 0">
                    <td colspan="4" class="text-center text-muted" style="padding: 20px;">No recent invoices found.</td>
                  </tr>
                </tbody>
              </table>
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

const metrics = ref({});
const recentInvoices = ref([]);
const chartData = ref([]);
const isAdmin = ref(false);
const currentUser = ref({});

const formatCurrency = (val) => Number(val || 0).toLocaleString('en-US', { minimumFractionDigits: 2 });

const getBarHeight = (amount) => {
  return Math.min(Math.max((Number(amount) / 150000) * 100, 10), 100) + '%';
};

const getStatusClass = (status) => {
  switch (status) {
    case 'Paid': return 'pill-success';
    case 'Pending Approval': return 'pill-warning';
    case 'Overdue': return 'pill-danger';
    case 'Draft': return 'pill-info';
    default: return 'pill-info';
  }
};

const filterInvoicesByAging = (agingCategory) => {
  window.location.href = `/invoices?aging=${agingCategory}`;
};

const fetchDashboardData = async () => {
  try {
    const userRes = await axios.get('/api/user');
    currentUser.value = userRes.data.user || userRes.data;
    isAdmin.value = currentUser.value.role === 'Admin';

    const res = await axios.get('/api/dashboard/analytics');
    metrics.value = res.data.metrics || {};
    recentInvoices.value = res.data.recent_activities || [];
    chartData.value = res.data.charts?.monthly_revenue || [];
  } catch (e) {
    console.error('Failed to load dashboard metrics:', e);
  }
};

onMounted(() => {
  fetchDashboardData();
});
</script>

<style scoped>
/* Same CSS as provided */
.dashboard-page-wrapper { max-width: 1400px; margin: 0 auto; padding: 24px 32px; box-sizing: border-box; }
.welcome-hero-banner { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px 28px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.02); }
.hero-text-content h1 { font-size: 18px; font-weight: 800; color: #0f172a; margin: 0 0 4px 0; }
.hero-text-content p { font-size: 13px; color: #64748b; margin: 0; }
.hero-actions-group { display: flex; gap: 10px; }
.hero-action-button { padding: 9px 16px; border-radius: 8px; font-size: 11px; font-weight: 700; text-decoration: none; text-transform: uppercase; letter-spacing: 0.5px; }
.hero-action-button.primary { background-color: #0f172a; color: #ffffff; border: 1px solid #0f172a; }
.hero-action-button.primary:hover { background-color: #1e293b; }
.hero-action-button.secondary { background-color: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; }
.hero-action-button.secondary:hover { background-color: #e2e8f0; }

.metrics-grid-container { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 24px; }
.metric-card-box { background: #ffffff; padding: 20px 24px; border-radius: 12px; border: 1px solid #e2e8f0; transition: border-color 0.2s; box-shadow: 0 1px 3px rgba(0,0,0,0.02); }
.metric-card-box:hover { border-color: #94a3b8; }
.metric-top-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
.metric-card-title { font-size: 11px; font-weight: 700; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px; }
.metric-icon-box { width: 34px; height: 34px; border-radius: 8px; background: #f8fafc; border: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: center; color: #64748b; }
.metric-card-value { font-size: 24px; font-weight: 800; color: #0f172a; margin-bottom: 4px; }
.metric-trend { font-size: 11px; color: #64748b; font-weight: 500; }

.mb-24 { margin-bottom: 24px; }
.analytics-card-panel { background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.02); }
.panel-header-box { background: #0f172a; padding: 16px 24px; display: flex; justify-content: space-between; align-items: center; }
.panel-header-box h3 { color: #ffffff; font-size: 13px; font-weight: 700; text-transform: uppercase; margin: 0; letter-spacing: 0.5px; }
.panel-subtitle { color: #94a3b8; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
.btn-view-all { color: #94a3b8; font-size: 11px; font-weight: 600; text-decoration: none; text-transform: uppercase; }
.btn-view-all:hover { color: #ffffff; }
.panel-body-content { padding: 24px; }

.aging-bar-container { display: flex; height: 28px; border-radius: 6px; overflow: hidden; font-size: 11px; font-weight: 700; color: #ffffff; line-height: 28px; text-align: center; }
.aging-segment { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; padding: 0 8px; cursor: pointer; transition: opacity 0.2s; }
.aging-segment:hover { opacity: 0.85; filter: brightness(1.1); }
.seg-current { background: #0f172a; }
.seg-30 { background: #334155; }
.seg-60 { background: #64748b; }
.seg-over { background: #94a3b8; color: #0f172a; }

.aging-legend { display: flex; gap: 20px; margin-top: 14px; font-size: 12px; color: #475569; font-weight: 500; }
.legend-item { display: flex; align-items: center; gap: 6px; }
.legend-item.clickable { cursor: pointer; }
.legend-item.clickable:hover { color: #0f172a; text-decoration: underline; }
.dot { width: 9px; height: 9px; border-radius: 50%; }
.dot-current { background: #0f172a; }
.dot-30 { background: #334155; }
.dot-60 { background: #64748b; }
.dot-over { background: #94a3b8; }

.dashboard-dual-grid { display: grid; grid-template-columns: 1.2fr 1fr; gap: 20px; }
.analytics-bar-chart { display: flex; justify-content: space-around; align-items: flex-end; height: 180px; border-bottom: 1px solid #e2e8f0; padding-bottom: 5px; }
.chart-column-item { display: flex; flex-direction: column; align-items: center; flex: 1; height: 100%; justify-content: flex-end; }
.chart-bars-wrapper { display: flex; gap: 4px; align-items: flex-end; height: 100%; width: 100%; justify-content: center; }
.single-bar { width: 12px; border-radius: 3px 3px 0 0; }
.single-bar.paid { background: #0f172a; } 
.single-bar.unpaid { background: #cbd5e1; }
.chart-month-label { font-size: 10px; font-weight: 700; color: #64748b; margin-top: 8px; text-transform: uppercase; }
.chart-legend-bottom { display: flex; justify-content: center; gap: 20px; margin-top: 16px; font-size: 12px; color: #475569; font-weight: 500; }
.paid-dot { background: #0f172a; } 
.unpaid-dot { background: #cbd5e1; }

.recent-table-wrapper { overflow-x: auto; }
.recent-billing-table { width: 100%; border-collapse: collapse; text-align: left; font-size: 12px; }
.recent-billing-table th { background: #f8fafc; padding: 10px 14px; font-weight: 700; color: #64748b; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; letter-spacing: 0.5px; }
.recent-billing-table td { padding: 12px 14px; border-bottom: 1px solid #e2e8f0; color: #334155; }
.font-bold { font-weight: 700; } .text-slate-900 { color: #0f172a; }
.text-center { text-align: center; } .text-muted { color: #94a3b8; }
.status-pill { padding: 4px 10px; border-radius: 6px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
.pill-success { background: #d1fae5; color: #059669; }
.pill-warning { background: #fef3c7; color: #d97706; }
.pill-danger { background: #fee2e2; color: #dc2626; }
.pill-info { background: #e0f2fe; color: #0284c7; }
</style>