import { ref, onMounted, onUnmounted } from 'vue';
import axios from 'axios';

export function useDashboard() {
  const metrics = ref({});
  const recentInvoices = ref([]);
  const chartData = ref([]);
  const isAdmin = ref(false);
  const currentUser = ref({});
  const isLoading = ref(true);

  const formatCurrency = (val) => Number(val || 0).toLocaleString('en-US', { minimumFractionDigits: 2 });

  const getBarHeight = (amount) => {
    const num = Number(amount || 0);
    if (num <= 0) return '0%';
    let max = 0;
    chartData.value.forEach(item => {
      max = Math.max(max, Number(item.paid_amount || 0), Number(item.unpaid_amount || 0));
    });
    const ceiling = max > 0 ? max * 1.15 : 50000;
    return Math.min(Math.max((num / ceiling) * 100, 6), 100) + '%';
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

  const fetchDashboardData = async (silent = false) => {
    if (!silent) isLoading.value = true;
    try {
      const userRes = await axios.get('/api/user');
      currentUser.value = userRes.data.user || userRes.data;
      const role = currentUser.value?.role?.toLowerCase()?.trim() || '';
      isAdmin.value = role === 'admin' || role === 'superadmin' || currentUser.value?.role === 'Admin';

      const res = await axios.get('/api/dashboard/analytics');
      const data = res.data.data || res.data;
      metrics.value = data.metrics || {};
      recentInvoices.value = data.recent_activities || data.recent_invoices || [];
      chartData.value = data.charts?.monthly_revenue || data.chart || [];
    } catch (e) {
      if (!silent) console.error('Failed to load dashboard metrics:', e);
    } finally {
      if (!silent) isLoading.value = false;
    }
  };

  const onDataUpdated = () => {
    fetchDashboardData(true);
  };

  const initDashboard = async () => {
    await fetchDashboardData();
    // Event-driven real-time update when new data is detected
    window.addEventListener('lobis:data-updated', onDataUpdated);
  };

  onMounted(() => {
    initDashboard();
  });

  onUnmounted(() => {
    window.removeEventListener('lobis:data-updated', onDataUpdated);
  });

  return {
    metrics,
    recentInvoices,
    chartData,
    isAdmin,
    currentUser,
    isLoading,
    formatCurrency,
    getBarHeight,
    getStatusClass,
    filterInvoicesByAging,
    fetchDashboardData,
    initDashboard
  };
}
