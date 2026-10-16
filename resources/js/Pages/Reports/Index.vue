<template>
  <app-layout>
    <div class="dashboard-page-wrapper">
      
      <!-- HERO HEADER BAR -->
      <div class="welcome-hero-banner mb-24">
        <div class="hero-text-content">
          <div class="hero-title-badge">
            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
            <span>FINANCIAL REPORTS</span>
          </div>
          <h1>Financial Reports &amp; Executive Analytics</h1>
          <p>Comprehensive corporate revenue breakdown, BIR tax compliance statements, and collection performance tracker.</p>
        </div>
        <div class="hero-actions-group">
          <button @click="exportToCsv" class="btn-sm-action outline">
            <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
            Export CSV
          </button>
          <button @click="exportPdf" class="btn-sm-action amber">
            <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
            Export PDF
          </button>
        </div>
      </div>

      <!-- FILTER CONTROLS BAR (Admin Only) -->
      <div v-if="isAdminOrSuper" class="analytics-card-panel p-20 mb-24">
        <div class="filters-grid-row">
          <div class="filter-item-box">
            <label>Start Date</label>
            <input v-model="filters.start_date" type="date" class="form-control-input" @change="fetchReportData" />
          </div>
          <div class="filter-item-box">
            <label>End Date</label>
            <input v-model="filters.end_date" type="date" class="form-control-input" @change="fetchReportData" />
          </div>
          <div v-if="teams.length > 0" class="filter-item-box">
            <label>Team</label>
            <select v-model="filters.team_id" class="form-select-dropdown" @change="fetchReportData">
              <option value="">All Teams (Company-wide)</option>
              <option v-for="team in teams" :key="team.id" :value="team.id">{{ team.team_name || team.name }}</option>
            </select>
          </div>
          <div class="filter-item-box align-self-end">
            <button @click="resetFilters" class="btn-secondary-action">Reset Filters</button>
          </div>
        </div>
      </div>

      <!-- TEAM SCOPE BANNER (Non-Admin Users) -->
      <div v-if="!isAdminOrSuper" class="team-scope-banner">
        <div class="team-banner-content">
          <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
          <span>Reporting Scope: <strong>{{ userTeamName ? userTeamName : 'Assigned Team Data Only' }}</strong></span>
        </div>
        <span class="team-scope-chip">Filtered by Team</span>
      </div>

      <!-- KPI METRICS GRID (5 CARDS) -->
      <div class="kpi-grid-container mb-24">
        <template v-if="isLoading">
          <SkeletonCard v-for="n in 5" :key="n" />
        </template>
        <template v-else>
        <!-- Total Billed -->
        <div class="kpi-card-item">
          <div class="kpi-info">
            <span class="kpi-label">Total Billed Revenue</span>
            <span class="kpi-number">₱{{ formatCurrency(summary.total_billed) }}</span>
            <div class="kpi-footer">
              <small class="text-xs text-slate-500 mt-1">{{ summary.invoice_count }} Invoices Total</small>
              <span v-if="yoy.growth_billed_pct !== null" :class="['yoy-badge', yoy.growth_billed_pct >= 0 ? 'yoy-up' : 'yoy-down']">
                {{ yoy.growth_billed_pct >= 0 ? '▲' : '▼' }} {{ Math.abs(yoy.growth_billed_pct) }}% vs LY
              </span>
            </div>
          </div>
          <div class="kpi-icon-box bg-slate-100 text-slate-700">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
          </div>
        </div>

        <!-- Total Collected -->
        <div class="kpi-card-item border-l-4 border-l-emerald-500">
          <div class="kpi-info">
            <span class="kpi-label">Total Collected (Paid)</span>
            <span class="kpi-number text-emerald-600">₱{{ formatCurrency(summary.total_paid) }}</span>
            <div class="kpi-footer">
              <span class="kpi-chip-badge bg-emerald-100 text-emerald-700 font-bold mt-1">{{ summary.collection_rate }}% Rate</span>
              <span v-if="yoy.growth_paid_pct !== null" :class="['yoy-badge', yoy.growth_paid_pct >= 0 ? 'yoy-up' : 'yoy-down']">
                {{ yoy.growth_paid_pct >= 0 ? '▲' : '▼' }} {{ Math.abs(yoy.growth_paid_pct) }}% vs LY
              </span>
            </div>
          </div>
          <div class="kpi-icon-box bg-emerald-50 text-emerald-600">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
          </div>
        </div>

        <div class="kpi-card-item border-l-4 border-l-amber-500">
          <div class="kpi-info">
            <span class="kpi-label">Pending / Unpaid Balance</span>
            <span class="kpi-number text-amber-600">₱{{ formatCurrency(summary.total_pending) }}</span>
            <small class="text-xs text-amber-700 font-semibold mt-1">Overdue: ₱{{ formatCurrency(summary.total_overdue) }}</small>
          </div>
          <div class="kpi-icon-box bg-amber-50 text-amber-600">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
          </div>
        </div>

        <div class="kpi-card-item">
          <div class="kpi-info">
            <span class="kpi-label">Total Output VAT (12%)</span>
            <span class="kpi-number">₱{{ formatCurrency(summary.total_vat) }}</span>
            <small class="text-xs text-slate-500 mt-1">Vatable Sales: ₱{{ formatCurrency(taxSummary.vatable_sales) }}</small>
          </div>
          <div class="kpi-icon-box bg-slate-100 text-slate-700">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
          </div>
        </div>

        <div class="kpi-card-item">
          <div class="kpi-info">
            <span class="kpi-label">Withholding Tax (2% EWT)</span>
            <span class="kpi-number">₱{{ formatCurrency(summary.total_wht) }}</span>
            <small class="text-xs text-slate-500 mt-1">Total Tax Creditable</small>
          </div>
          <div class="kpi-icon-box bg-slate-100 text-slate-700">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
          </div>
        </div>
        </template>
      </div>

      <!-- MAIN REPORT TABS & TABLES -->
      <div id="printable-report-area" class="analytics-card-panel">
        <div class="panel-header-box flex-between-box">
          <div class="report-tabs-navigation">
            <button 
              v-for="tab in availableReportTabs" 
              :key="tab"
              @click="activeReportTab = tab"
              :class="['report-tab-btn', { active: activeReportTab === tab }]"
            >
              {{ tab }}
            </button>
          </div>
          <span class="generated-date-pill">
            <svg viewBox="0 0 24 24" width="10" height="10" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
            Generated: {{ currentFormattedDate }}
          </span>
        </div>

        <div class="panel-body-content p-24">
          
          <!-- TAB 1: MONTHLY PERFORMANCE -->
          <div v-if="activeReportTab === 'Monthly Performance'">
            <div class="section-sub-header mb-16 flex-between-box">
              <h4 class="font-bold text-slate-800 text-sm uppercase">Monthly Revenue &amp; Collection Breakdown</h4>
              <div v-if="yoy.last_year_billed > 0" class="yoy-legend">
                <span class="yoy-badge yoy-up">▲ Above Last Year</span>
                <span class="yoy-badge yoy-down">▼ Below Last Year</span>
              </div>
            </div>
            <div class="items-table-frame">
              <SkeletonTable v-if="isLoading" :rows="6" :cols="6" />
              <template v-else>
              <table class="enterprise-data-table">
                <thead>
                  <tr>
                    <th>Month</th>
                    <th class="text-right">Billed Amount (₱)</th>
                    <th class="text-right">Collected (Paid) (₱)</th>
                    <th class="text-right">Output VAT (12%) (₱)</th>
                    <th class="text-right">Withholding Tax (2%) (₱)</th>
                    <th style="width: 180px; text-align: center;">Collection Rate</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="m in monthlyTrend" :key="m.month">
                    <td class="font-bold text-slate-900">{{ m.month }}</td>
                    <td class="text-right font-semibold">₱{{ formatCurrency(m.billed) }}</td>
                    <td class="text-right font-bold text-emerald-600">₱{{ formatCurrency(m.paid) }}</td>
                    <td class="text-right text-slate-600">₱{{ formatCurrency(m.vat) }}</td>
                    <td class="text-right text-slate-600">₱{{ formatCurrency(m.withholding_tax) }}</td>
                    <td>
                      <div class="flex items-center gap-2">
                        <div class="progress-bar-track flex-1" style="height: 8px;">
                          <div class="progress-bar-fill bg-emerald-500" :style="{ width: Math.min(m.collection_rate, 100) + '%' }"></div>
                        </div>
                        <span class="text-xs font-bold text-slate-700" style="width: 40px; text-align: right;">{{ m.collection_rate }}%</span>
                      </div>
                    </td>
                  </tr>
                </tbody>
                <tfoot v-if="yoy.last_year_billed > 0">
                  <tr style="background: #f8fafc;">
                    <td class="font-bold text-slate-400 text-xs uppercase" style="padding: 10px 18px; border-top: 2px dashed #e2e8f0;">Prior Year Reference</td>
                    <td class="text-right text-slate-400 text-xs font-semibold" style="padding: 10px 18px; border-top: 2px dashed #e2e8f0;">₱{{ formatCurrency(yoy.last_year_billed) }}</td>
                    <td class="text-right text-slate-400 text-xs font-semibold" style="padding: 10px 18px; border-top: 2px dashed #e2e8f0;">₱{{ formatCurrency(yoy.last_year_paid) }}</td>
                    <td colspan="3" class="text-slate-400 text-xs italic" style="padding: 10px 18px; border-top: 2px dashed #e2e8f0; text-align: right;">Last Calendar Year Totals</td>
                  </tr>
                </tfoot>
              </table>
              </template>
            </div>
          </div>

          <!-- TAB 2: BIR TAX & COMPLIANCE -->
          <div v-if="activeReportTab === 'BIR Tax & Compliance'">
            <div class="section-sub-header mb-16">
              <h4 class="font-bold text-slate-800 text-sm uppercase">Official BIR Tax &amp; Withholding Summary Statement</h4>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="border border-slate-200 rounded-xl p-20 bg-slate-50">
                <h5 class="font-bold text-slate-900 text-xs uppercase mb-12 border-b border-slate-200 pb-8">Value Added Tax (VAT) Summary</h5>
                <div class="space-y-3">
                  <div class="flex justify-between text-sm">
                    <span class="text-slate-600">Gross Vatable Sales:</span>
                    <span class="font-bold text-slate-900">₱{{ formatCurrency(taxSummary.vatable_sales) }}</span>
                  </div>
                  <div class="flex justify-between text-sm">
                    <span class="text-slate-600">Output VAT (12% Rate):</span>
                    <span class="font-bold text-slate-900">₱{{ formatCurrency(taxSummary.output_vat) }}</span>
                  </div>
                  <div class="flex justify-between text-sm">
                    <span class="text-slate-600">Non-VAT / Zero-Rated Sales:</span>
                    <span class="font-bold text-slate-800">₱{{ formatCurrency(taxSummary.non_vat_sales) }}</span>
                  </div>
                  <div class="flex justify-between text-sm">
                    <span class="text-slate-600">Tax Exempt Sales:</span>
                    <span class="font-bold text-slate-800">₱{{ formatCurrency(taxSummary.exempt_sales) }}</span>
                  </div>
                </div>
              </div>

              <div class="border border-slate-200 rounded-xl p-20 bg-slate-50">
                <h5 class="font-bold text-slate-900 text-xs uppercase mb-12 border-b border-slate-200 pb-8">Withholding Tax (Creditable EWT)</h5>
                <div class="space-y-3">
                  <div class="flex justify-between text-sm">
                    <span class="text-slate-600">Total Tax Creditable Withheld (2%):</span>
                    <span class="font-bold text-slate-900">₱{{ formatCurrency(taxSummary.withholding_tax) }}</span>
                  </div>
                  <div class="flex justify-between text-sm">
                    <span class="text-slate-600">Total Invoices with EWT:</span>
                    <span class="font-bold text-slate-900">{{ summary.invoice_count }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- TAB 3: CLIENT RANKING -->
          <div v-if="activeReportTab === 'Client Ranking'">
            <div class="section-sub-header mb-16">
              <h4 class="font-bold text-slate-800 text-sm uppercase">Top Revenue Generating Clients</h4>
            </div>
            <div class="items-table-frame">
              <SkeletonTable v-if="isLoading" :rows="6" :cols="6" />
              <template v-else>
              <table class="enterprise-data-table">
                <thead>
                  <tr>
                    <th>Rank / Client Name</th>
                    <th class="text-center">Invoices</th>
                    <th class="text-right">Total Billed (₱)</th>
                    <th class="text-right">Collected (Paid) (₱)</th>
                    <th class="text-right">Outstanding (₱)</th>
                    <th class="text-right">Revenue Share</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(c, idx) in clientBreakdown" :key="c.client_id">
                    <td class="font-bold text-slate-900">
                      <span class="inline-block w-6 text-slate-400 font-semibold">#{{ idx + 1 }}</span>
                      {{ c.client_name }}
                    </td>
                    <td class="text-center font-bold text-slate-700">{{ c.invoice_count }}</td>
                    <td class="text-right font-bold text-slate-900">₱{{ formatCurrency(c.total_billed) }}</td>
                    <td class="text-right font-bold text-emerald-600">₱{{ formatCurrency(c.total_paid) }}</td>
                    <td class="text-right font-bold text-amber-600">₱{{ formatCurrency(c.total_unpaid) }}</td>
                    <td class="text-right font-bold text-slate-700">{{ c.revenue_share }}%</td>
                  </tr>
                  <tr v-if="clientBreakdown.length === 0 && !isLoading">
                    <td colspan="6" class="empty-data-message">No client revenue records available for this period.</td>
                  </tr>
                </tbody>
              </table>
              </template>
            </div>
          </div>

          <!-- TAB 4: TEAM QUOTA TRACKER -->
          <div v-if="activeReportTab === 'Team Quota Tracker'">
            <div class="section-sub-header mb-16">
              <h4 class="font-bold text-slate-800 text-sm uppercase">Team Performance &amp; Quota Target Report</h4>
            </div>
            <div class="items-table-frame">
              <SkeletonTable v-if="isLoading" :rows="6" :cols="6" />
              <template v-else>
              <table class="enterprise-data-table">
                <thead>
                  <tr>
                    <th>Team Name</th>
                    <th class="text-right">Quota Target (₱)</th>
                    <th class="text-right">Total Billed (₱)</th>
                    <th class="text-right">Total Collected (₱)</th>
                    <th style="width: 220px; text-align: center;">Quota Achievement</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="t in teamBreakdown" :key="t.team_id">
                    <td class="font-bold text-slate-900">{{ t.team_name }}</td>
                    <td class="text-right font-semibold text-slate-700">₱{{ formatCurrency(t.quota) }}</td>
                    <td class="text-right font-bold text-slate-900">₱{{ formatCurrency(t.total_billed) }}</td>
                    <td class="text-right font-bold text-emerald-600">₱{{ formatCurrency(t.total_paid) }}</td>
                    <td>
                      <div class="flex items-center gap-2">
                        <div class="progress-bar-track flex-1" style="height: 10px;">
                          <div class="progress-bar-fill" :class="t.achievement >= 100 ? 'bg-emerald-500' : 'bg-sky-500'" :style="{ width: Math.min(t.achievement, 100) + '%' }"></div>
                        </div>
                        <span class="text-xs font-bold" :class="t.achievement >= 100 ? 'text-emerald-700' : 'text-sky-700'" style="width: 45px; text-align: right;">{{ t.achievement }}%</span>
                      </div>
                    </td>
                  </tr>
                  <tr v-if="teamBreakdown.length === 0 && !isLoading">
                    <td colspan="5" class="empty-data-message">No team quota metrics available.</td>
                  </tr>
                </tbody>
              </table>
              </template>
            </div>
          </div>

        </div>
      </div>

    </div>
  </app-layout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useReports } from '@/composables/useReports';
import SkeletonCard from '@/Components/Skeleton/SkeletonCard.vue';
import SkeletonTable from '@/Components/Skeleton/SkeletonTable.vue';
import './Index.css';

const {
  // State
  activeReportTab, teams, isAdminOrSuper, userTeamName,
  filters, summary, yoy, monthlyTrend, taxSummary,
  clientBreakdown, teamBreakdown, isLoading,

  // Computeds
  availableReportTabs, currentFormattedDate,

  // Helpers & Actions
  formatCurrency, fetchUserData, fetchTeams, fetchReportData,
  resetFilters, printReport, exportPdf, exportToCsv
} = useReports();
</script>