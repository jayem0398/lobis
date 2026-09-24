<template>
  <app-layout>
    <div class="dashboard-page-wrapper">
      
      <!-- HERO HEADER BAR -->
      <div class="welcome-hero-banner mb-24">
        <div class="hero-text-content">
          <h1>Financial Reports &amp; Executive Analytics</h1>
          <p>Comprehensive corporate revenue breakdown, BIR tax compliance statements, and collection performance tracker.</p>
        </div>
        <div class="hero-actions-group">
          <button @click="exportToCsv" class="btn-sm-action outline">
            <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
            Export CSV
          </button>
          <button @click="exportPdf" class="btn-sm-action sky">
            <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
            Export PDF
          </button>
          <button @click="printReport" class="btn-sm-action dark">
            <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
            Print
          </button>
        </div>
      </div>

      <!-- FILTER CONTROLS BAR -->
      <div class="analytics-card-panel p-20 mb-24">
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

      <!-- KPI METRICS GRID (5 CARDS) -->
      <div class="kpi-grid-container mb-24">
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
            <span class="kpi-number text-sky-700">₱{{ formatCurrency(summary.total_vat) }}</span>
            <small class="text-xs text-slate-500 mt-1">Vatable Sales: ₱{{ formatCurrency(taxSummary.vatable_sales) }}</small>
          </div>
          <div class="kpi-icon-box bg-sky-50 text-sky-700">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
          </div>
        </div>

        <div class="kpi-card-item">
          <div class="kpi-info">
            <span class="kpi-label">Withholding Tax (2% EWT)</span>
            <span class="kpi-number text-purple-700">₱{{ formatCurrency(summary.total_wht) }}</span>
            <small class="text-xs text-slate-500 mt-1">Total Tax Creditable</small>
          </div>
          <div class="kpi-icon-box bg-purple-50 text-purple-700">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
          </div>
        </div>
      </div>

      <!-- MAIN REPORT TABS & TABLES -->
      <div id="printable-report-area" class="analytics-card-panel">
        <div class="panel-header-box flex-between-box">
          <div class="report-tabs-navigation">
            <button 
              v-for="tab in ['Monthly Performance', 'BIR Tax &amp; Compliance', 'Client Ranking', 'Team Quota Tracker']" 
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
              <h4 class="font-bold text-slate-800 text-sm uppercase">Monthly Revenue &amp; Collection Breakdown ({{ new Date().getFullYear() }})</h4>
              <div v-if="yoy.last_year_billed > 0" class="yoy-legend">
                <span class="yoy-badge yoy-up">▲ Above Last Year</span>
                <span class="yoy-badge yoy-down">▼ Below Last Year</span>
              </div>
            </div>
            <div class="items-table-frame">
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
                    <td class="text-right text-purple-700">₱{{ formatCurrency(m.withholding_tax) }}</td>
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
                    <span class="font-bold text-sky-700">₱{{ formatCurrency(taxSummary.output_vat) }}</span>
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
                    <span class="font-bold text-purple-700">₱{{ formatCurrency(taxSummary.withholding_tax) }}</span>
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
                    <td class="text-right font-bold text-sky-700">{{ c.revenue_share }}%</td>
                  </tr>
                  <tr v-if="clientBreakdown.length === 0">
                    <td colspan="6" class="empty-data-message">No client revenue records available for this period.</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- TAB 4: TEAM QUOTA TRACKER -->
          <div v-if="activeReportTab === 'Team Quota Tracker'">
            <div class="section-sub-header mb-16">
              <h4 class="font-bold text-slate-800 text-sm uppercase">Team Performance &amp; Quota Target Report</h4>
            </div>
            <div class="items-table-frame">
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
                  <tr v-if="teamBreakdown.length === 0">
                    <td colspan="5" class="empty-data-message">No team quota metrics available.</td>
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
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import { useToast } from 'vue-toastification';
import AppLayout from '@/Layouts/AppLayout.vue';

const toast = useToast();
const activeReportTab = ref('Monthly Performance');
const teams = ref([]);

const filters = ref({
  start_date: '',
  end_date: '',
  team_id: '',
});

const summary = ref({
  total_billed: 0,
  total_subtotal: 0,
  total_paid: 0,
  total_pending: 0,
  total_draft: 0,
  total_overdue: 0,
  total_vat: 0,
  total_wht: 0,
  total_discount: 0,
  collection_rate: 0,
  invoice_count: 0,
});

const yoy = ref({
  last_year_billed: 0,
  last_year_paid: 0,
  growth_billed_pct: null,
  growth_paid_pct: null,
});

const monthlyTrend = ref([]);
const taxSummary = ref({ vatable_sales: 0, output_vat: 0, non_vat_sales: 0, exempt_sales: 0, withholding_tax: 0 });
const clientBreakdown = ref([]);
const teamBreakdown = ref([]);

const currentFormattedDate = computed(() => {
  return new Date().toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
});

const formatCurrency = (val) => Number(val || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

const fetchReportData = async () => {
  try {
    const res = await axios.get('/api/reports/financial', { params: filters.value });
    const data = res.data;
    summary.value = data.summary || summary.value;
    monthlyTrend.value = data.monthly_trend || [];
    taxSummary.value = data.tax_summary || taxSummary.value;
    clientBreakdown.value = data.client_breakdown || [];
    teamBreakdown.value = data.team_breakdown || [];
    if (data.yoy) {
      yoy.value = data.yoy;
    }
  } catch (error) {
    console.error('Failed to load financial report data:', error);
    toast.error('Failed to load report analytics.');
  }
};

const fetchTeams = async () => {
  try {
    const res = await axios.get('/api/teams');
    teams.value = res.data.data || res.data || [];
  } catch (e) {
    console.error('Failed to load teams:', e);
  }
};

const resetFilters = () => {
  filters.value = { start_date: '', end_date: '', team_id: '' };
  fetchReportData();
};

const printReport = () => {
  window.print();
};

const exportPdf = () => {
  const params = new URLSearchParams(filters.value).toString();
  window.open(`/api/reports/financial/pdf?${params}`, '_blank');
};

const exportToCsv = () => {
  let csvContent = "data:text/csv;charset=utf-8,";
  csvContent += "Financial Reports Summary\n";
  csvContent += `Generated Date,${currentFormattedDate.value}\n\n`;
  csvContent += "Metric,Amount (PHP)\n";
  csvContent += `Total Billed,${summary.value.total_billed}\n`;
  csvContent += `Total Collected (Paid),${summary.value.total_paid}\n`;
  csvContent += `Pending Balance,${summary.value.total_pending}\n`;
  csvContent += `Overdue Amount,${summary.value.total_overdue}\n`;
  csvContent += `Output VAT (12%),${summary.value.total_vat}\n`;
  csvContent += `Withholding Tax (2%),${summary.value.total_wht}\n\n`;

  csvContent += "Month,Billed,Collected,Output VAT,Withholding Tax,Collection Rate (%)\n";
  monthlyTrend.value.forEach(row => {
    csvContent += `${row.month},${row.billed},${row.paid},${row.vat},${row.withholding_tax},${row.collection_rate}%\n`;
  });

  const encodedUri = encodeURI(csvContent);
  const link = document.createElement("a");
  link.setAttribute("href", encodedUri);
  link.setAttribute("download", `Financial_Report_${new Date().toISOString().slice(0,10)}.csv`);
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
};

onMounted(async () => {
  await fetchTeams();
  await fetchReportData();
});
</script>

<style scoped>
.dashboard-page-wrapper { max-width: 1400px; margin: 0 auto; padding-left: 1in; padding-right: 1in; padding-top: 24px; padding-bottom: 24px; box-sizing: border-box; }
.mb-24 { margin-bottom: 24px; }
.mb-16 { margin-bottom: 16px; }
.p-20 { padding: 20px; }
.p-24 { padding: 24px; }

.welcome-hero-banner { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px 24px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }
.hero-text-content h1 { font-size: 18px; font-weight: 800; color: #0f172a; margin: 0 0 4px 0; letter-spacing: -0.5px; }
.hero-text-content p { font-size: 12px; color: #64748b; margin: 0; }
.hero-actions-group { display: flex; gap: 8px; align-items: center; }

/* Compact action buttons */
.btn-sm-action {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 6px 12px;
  border-radius: 6px;
  font-size: 11px;
  font-weight: 600;
  cursor: pointer;
  border: 1px solid transparent;
  transition: all 0.2s ease;
  white-space: nowrap;
  letter-spacing: 0.2px;
}
.btn-sm-action.outline { background: #ffffff; border-color: #cbd5e1; color: #334155; }
.btn-sm-action.outline:hover { background: #f8fafc; border-color: #94a3b8; }
.btn-sm-action.sky { background: #f0f9ff; border-color: #bae6fd; color: #0369a1; }
.btn-sm-action.sky:hover { background: #e0f2fe; }
.btn-sm-action.dark { background: #0f172a; border-color: #0f172a; color: #ffffff; }
.btn-sm-action.dark:hover { background: #1e293b; }

.analytics-card-panel { background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }
.panel-header-box { background: #0f172a; padding: 14px 20px; display: flex; justify-content: space-between; align-items: center; }

/* Generated date pill — right side of tab header */
.generated-date-pill {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  font-size: 10px;
  font-weight: 600;
  color: #64748b;
  background: rgba(255,255,255,0.07);
  padding: 4px 10px;
  border-radius: 20px;
  border: 1px solid rgba(255,255,255,0.1);
  white-space: nowrap;
}

/* YoY badges */
.yoy-badge {
  display: inline-flex;
  align-items: center;
  gap: 2px;
  font-size: 10px;
  font-weight: 700;
  padding: 2px 7px;
  border-radius: 10px;
  white-space: nowrap;
}
.yoy-up { background: #dcfce7; color: #166534; }
.yoy-down { background: #fee2e2; color: #991b1b; }
.yoy-legend { display: flex; gap: 8px; }

.kpi-footer { display: flex; align-items: center; gap: 8px; margin-top: 4px; flex-wrap: wrap; }

.filters-grid-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; align-items: flex-end; }
.filter-item-box { display: flex; flex-direction: column; }
.filter-item-box label { font-size: 11px; font-weight: 700; text-transform: uppercase; color: #64748b; margin-bottom: 6px; }

.form-control-input, .form-select-dropdown { width: 100%; height: 40px; padding: 0 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px; outline: none; background: #ffffff; color: #1e293b; }
.btn-secondary-action { height: 40px; padding: 0 16px; border: 1px solid #cbd5e1; background: #f8fafc; border-radius: 8px; font-size: 12px; font-weight: 700; color: #475569; cursor: pointer; }
.btn-secondary-action:hover { background: #f1f5f9; }

.kpi-grid-container { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px; }
.kpi-card-item { background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 20px; display: flex; justify-content: space-between; align-items: flex-start; box-shadow: 0 2px 4px rgba(0,0,0,0.03); }
.kpi-info { display: flex; flex-direction: column; }
.kpi-label { font-size: 11px; font-weight: 700; text-transform: uppercase; color: #64748b; margin-bottom: 4px; letter-spacing: 0.5px; }
.kpi-number { font-size: 20px; font-weight: 800; color: #0f172a; }
.kpi-icon-box { width: 42px; height: 42px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.kpi-chip-badge { display: inline-block; padding: 2px 8px; border-radius: 12px; font-size: 10px; }

.report-tabs-navigation { display: flex; gap: 6px; }
.report-tab-btn { background: transparent; border: none; color: #64748b; font-size: 11px; font-weight: 700; padding: 7px 14px; border-radius: 6px; cursor: pointer; text-transform: uppercase; letter-spacing: 0.3px; transition: all 0.2s ease; }
.report-tab-btn.active { background: rgba(255,255,255,0.15); color: #ffffff; }
.report-tab-btn:not(.active):hover { background: rgba(255,255,255,0.08); color: #e2e8f0; }

.enterprise-data-table { width: 100%; border-collapse: collapse; }
.enterprise-data-table th { background: #f8fafc; padding: 14px 18px; font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; }
.enterprise-data-table td { padding: 16px 18px; border-bottom: 1px solid #e2e8f0; font-size: 13px; color: #334155; }
.progress-bar-track { background: #e2e8f0; border-radius: 10px; overflow: hidden; }
.progress-bar-fill { height: 100%; border-radius: 10px; transition: width 0.3s ease; }
.empty-data-message { padding: 40px; text-align: center; color: #94a3b8; font-style: italic; }

/* Utility classes */
.flex-between-box { display: flex; justify-content: space-between; align-items: center; }
.section-sub-header { display: flex; justify-content: space-between; align-items: center; }
.text-xs { font-size: 11px; }
.font-bold { font-weight: 700; }
.text-slate-800 { color: #1e293b; }
.text-slate-700 { color: #334155; }
.text-slate-600 { color: #475569; }
.text-slate-500 { color: #64748b; }
.text-slate-400 { color: #94a3b8; }
.text-slate-900 { color: #0f172a; }
.text-emerald-600 { color: #059669; }
.text-amber-600 { color: #d97706; }
.text-amber-700 { color: #b45309; }
.text-sky-700 { color: #0369a1; }
.text-purple-700 { color: #7e22ce; }
.text-right { text-align: right; }
.text-center { text-align: center; }
.font-semibold { font-weight: 600; }
.items-table-frame { border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden; }
.flex { display: flex; }
.items-center { align-items: center; }
.gap-2 { gap: 8px; }
.flex-1 { flex: 1; }
.inline-block { display: inline-block; }
.space-y-3 > * + * { margin-top: 12px; }
.grid { display: grid; }
.grid-cols-1 { grid-template-columns: 1fr; }
.gap-6 { gap: 24px; }
.border { border-width: 1px; border-style: solid; }
.border-slate-200 { border-color: #e2e8f0; }
.rounded-xl { border-radius: 12px; }
.bg-slate-50 { background-color: #f8fafc; }
.border-b { border-bottom-width: 1px; border-bottom-style: solid; }
.pb-8 { padding-bottom: 8px; }
.justify-between { justify-content: space-between; }
.text-sm { font-size: 13px; }
.mt-1 { margin-top: 4px; }
.bg-emerald-100 { background: #d1fae5; }
.text-emerald-700 { color: #047857; }
.bg-slate-100 { background: #f1f5f9; }
.bg-emerald-50 { background: #f0fdf4; }
.bg-amber-50 { background: #fffbeb; }
.bg-sky-50 { background: #f0f9ff; }
.bg-purple-50 { background: #faf5ff; }
.bg-emerald-500 { background: #10b981; }
.bg-sky-500 { background: #0ea5e9; }
.border-l-4 { border-left-width: 4px; border-left-style: solid; }
.border-l-emerald-500 { border-left-color: #10b981; }
.border-l-amber-500 { border-left-color: #f59e0b; }

@media (min-width: 768px) {
  .md\:grid-cols-2 { grid-template-columns: repeat(2, 1fr); }
}

@media print {
  body * { visibility: hidden; }
  #printable-report-area, #printable-report-area * { visibility: visible; }
  #printable-report-area { position: absolute; left: 0; top: 0; width: 100%; box-shadow: none; border: none; }
  .panel-header-box { background: #0f172a !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
}
</style>