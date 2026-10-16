<template>
  <app-layout>
    <div class="dashboard-page-wrapper">
      
      <div class="welcome-hero-banner">
        <div class="hero-text-content">
          <div class="hero-title-badge">
            <span class="hero-badge-dot"></span>
            <span>Billing &amp; Collections Overview</span>
          </div>
          <h1>Welcome back, <span class="text-slate-900">{{ isAdmin ? 'Administrator' : (currentUser.name || 'Team Member') }}</span>!</h1>
          <p>{{ isAdmin ? 'Here is your daily summary of collections, active billings, and revenue performance.' : 'Here is the summary of your team and personal billing collections.' }}</p>
        </div>
        <div class="hero-actions-group">
          <a href="/invoices/create" class="hero-action-button primary">
            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            <span>+ New Invoice</span>
          </a>
          <a v-if="isAdmin" href="/clients" class="hero-action-button secondary">
            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>
            <span>Add Client</span>
          </a>
        </div>
      </div>

      <div class="metrics-grid-container">
        <template v-if="isLoading">
          <SkeletonCard v-for="n in 4" :key="n" />
        </template>
        <template v-else>
          <div class="metric-card-box border-l-4 border-l-amber-500">
            <div class="metric-top-row">
              <span class="metric-card-title">Total Revenue</span>
              <div class="metric-icon-box bg-amber-50 text-amber-600">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
              </div>
            </div>
            <div class="metric-card-value text-amber-700">₱{{ formatCurrency(metrics.total_revenue || 0) }}</div>
            <div class="metric-trend text-muted">{{ isAdmin ? 'Real-time database total paid revenue' : 'Your assigned total collected revenue' }}</div>
          </div>

          <div class="metric-card-box border-l-4 border-l-slate-700">
            <div class="metric-top-row">
              <span class="metric-card-title">Pending Collections</span>
              <div class="metric-icon-box bg-slate-100 text-slate-700">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
              </div>
            </div>
            <div class="metric-card-value text-slate-800">₱{{ formatCurrency(metrics.pending_amount || 0) }}</div>
            <div class="metric-trend text-muted">{{ metrics.unpaid_invoices || 0 }} unpaid invoices awaiting collection</div>
          </div>

          <div class="metric-card-box border-l-4 border-l-amber-500">
            <div class="metric-top-row">
              <span class="metric-card-title">{{ isAdmin ? 'Total Active Clients' : 'Assigned Clients' }}</span>
              <div class="metric-icon-box bg-amber-50 text-amber-600">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
              </div>
            </div>
            <div class="metric-card-value text-amber-700">{{ metrics.total_clients || 0 }}</div>
            <div class="metric-trend text-muted">Active in your view</div>
          </div>

          <div class="metric-card-box border-l-4 border-l-slate-700">
            <div class="metric-top-row">
              <span class="metric-card-title">Active Invoices</span>
              <div class="metric-icon-box bg-slate-100 text-slate-700">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
              </div>
            </div>
            <div class="metric-card-value text-slate-800">{{ metrics.total_invoices || 0 }}</div>
            <div class="metric-trend text-muted">Total billing records</div>
          </div>
        </template>
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
              <div v-for="(item, index) in chartData" :key="index" class="chart-column-item" :title="item.month_name + ' | Paid: ₱' + formatCurrency(item.paid_amount) + ' | Unpaid: ₱' + formatCurrency(item.unpaid_amount)">
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
          <div class="panel-body-content no-padding">
            <div class="recent-table-wrapper">
              <SkeletonTable v-if="isLoading" :rows="6" :cols="4" />
              <table v-else class="recent-billing-table">
                <thead>
                  <tr>
                    <th style="width: 80px;">Invoice</th>
                    <th>Client</th>
                    <th style="width: 95px;" class="text-right">Amount</th>
                    <th style="width: 145px;" class="text-center">Status</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="inv in recentInvoices" :key="inv.id">
                    <td class="font-bold text-slate-900">
                      <span class="invoice-num-tag">{{ inv.invoice_number }}</span>
                    </td>
                    <td>
                      <div class="client-name-text" :title="inv.client_name">{{ inv.client_name }}</div>
                    </td>
                    <td class="font-bold text-slate-900 text-right">₱{{ formatCurrency(inv.amount || inv.total_amount) }}</td>
                    <td class="text-center">
                      <span :class="['status-pill', getStatusClass(inv.status)]">
                        {{ inv.status }}
                      </span>
                    </td>
                  </tr>
                  <tr v-if="recentInvoices.length === 0 && !isLoading">
                    <td colspan="4" class="text-center text-muted" style="padding: 24px;">No recent invoices found.</td>
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
import AppLayout from '@/Layouts/AppLayout.vue';
import { useDashboard } from '@/composables/useDashboard';
import SkeletonCard from '@/Components/Skeleton/SkeletonCard.vue';
import SkeletonTable from '@/Components/Skeleton/SkeletonTable.vue';
import './Index.css';

const {
  metrics,
  recentInvoices,
  chartData,
  isAdmin,
  currentUser,
  isLoading,
  formatCurrency,
  getBarHeight,
  getStatusClass,
  filterInvoicesByAging
} = useDashboard();
</script>