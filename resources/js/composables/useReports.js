import { ref, computed, watch, onMounted } from 'vue';
import axios from 'axios';
import { useToast } from 'vue-toastification';

export function useReports() {
  const toast = useToast();

  // ── State ──────────────────────────────────────────────────────────────────
  const activeReportTab = ref('Monthly Performance');
  const teams = ref([]);
  const isAdminOrSuper = ref(false);
  const userTeamName = ref('');
  const isLoading = ref(true);

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

  // ── Computeds ─────────────────────────────────────────────────────────────
  const availableReportTabs = computed(() => {
    const tabs = ['Monthly Performance', 'BIR Tax & Compliance', 'Client Ranking'];
    if (isAdminOrSuper.value) tabs.push('Team Quota Tracker');
    return tabs;
  });

  const currentFormattedDate = computed(() => {
    return new Date().toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
  });

  const formatCurrency = (val) => Number(val || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

  // ── API Operations ────────────────────────────────────────────────────────
  const fetchUserData = async () => {
    try {
      const res = await axios.get('/api/user');
      const user = res.data.user || res.data;
      const role = user?.role?.toLowerCase().trim() ?? '';
      isAdminOrSuper.value = role === 'admin' || role === 'superadmin';
    } catch (e) {
      console.error('Failed to load user info:', e);
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

  const fetchReportData = async () => {
    isLoading.value = true;
    try {
      const params = {};
      if (filters.value.start_date) params.start_date = filters.value.start_date;
      if (filters.value.end_date) params.end_date = filters.value.end_date;
      if (filters.value.team_id !== '' && filters.value.team_id !== null && filters.value.team_id !== undefined) {
        params.team_id = filters.value.team_id;
      }

      const res = await axios.get('/api/reports/financial', { params });
      const data = res.data;
      summary.value = data.summary || summary.value;
      monthlyTrend.value = data.monthly_trend || [];
      taxSummary.value = data.tax_summary || taxSummary.value;
      clientBreakdown.value = data.client_breakdown || [];
      teamBreakdown.value = data.team_breakdown || [];
      userTeamName.value = data.user_team_name || '';
      if (data.yoy) {
        yoy.value = data.yoy;
      }
    } catch (error) {
      console.error('Failed to load financial report data:', error);
      toast.error('Failed to load report analytics.');
    } finally {
      isLoading.value = false;
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
    const paramsObj = {};
    if (filters.value.start_date) paramsObj.start_date = filters.value.start_date;
    if (filters.value.end_date) paramsObj.end_date = filters.value.end_date;
    if (filters.value.team_id) paramsObj.team_id = filters.value.team_id;
    const params = new URLSearchParams(paramsObj).toString();
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
    link.setAttribute("download", `Financial_Report_${new Date().toISOString().slice(0, 10)}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  };

  // Reactively watch filters to instantly fetch data when updated or reset
  watch(filters, () => {
    fetchReportData();
  }, { deep: true });

  const initReports = async () => {
    await fetchUserData();
    if (isAdminOrSuper.value) {
      await fetchTeams();
    }
    await fetchReportData();
  };

  onMounted(() => {
    initReports();
  });

  return {
    // State
    activeReportTab, teams, isAdminOrSuper, userTeamName,
    filters, summary, yoy, monthlyTrend, taxSummary,
    clientBreakdown, teamBreakdown, isLoading,

    // Computeds
    availableReportTabs, currentFormattedDate,

    // Helpers & Actions
    formatCurrency, fetchUserData, fetchTeams, fetchReportData,
    resetFilters, printReport, exportPdf, exportToCsv, initReports
  };
}
