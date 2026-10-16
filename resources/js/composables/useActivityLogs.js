import { ref, computed, onMounted } from 'vue';
import axios from 'axios';

export function useActivityLogs() {
  const logs = ref([]);
  const totalLogs = ref(0);
  const searchQuery = ref('');
  const selectedAction = ref('');
  const isLoading = ref(true);

  const pagination = ref({
    current_page: 1,
    last_page: 1
  });

  const loginCount = computed(() => {
    return logs.value.filter(l => l.action && l.action.toLowerCase().includes('login')).length;
  });

  const createCount = computed(() => {
    return logs.value.filter(l => l.action && (l.action.toLowerCase().includes('create') || l.action.toLowerCase().includes('approve'))).length;
  });

  const updateCount = computed(() => {
    return logs.value.filter(l => l.action && (l.action.toLowerCase().includes('update') || l.action.toLowerCase().includes('edit'))).length;
  });

  const fetchLogs = async (page = 1) => {
    isLoading.value = true;
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
    } finally {
      isLoading.value = false;
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

  const getUserInitial = (name) => {
    if (!name || name === 'System') return 'SYS';
    const parts = name.trim().split(' ');
    if (parts.length >= 2) {
      return (parts[0][0] + parts[1][0]).toUpperCase();
    }
    return name.substring(0, 2).toUpperCase();
  };

  const getActionChipClass = (action) => {
    if (!action) return 'chip-slate';
    const act = action.toLowerCase();
    if (act.includes('login')) return 'chip-amber';
    if (act.includes('create')) return 'chip-slate-dark';
    if (act.includes('update') || act.includes('edit')) return 'chip-amber';
    if (act.includes('delete')) return 'chip-red';
    if (act.includes('approve')) return 'chip-amber-gold';
    return 'chip-slate';
  };

  const resetFilters = () => {
    searchQuery.value = '';
    selectedAction.value = '';
    fetchLogs(1);
  };

  const initActivityLogs = async () => {
    await fetchLogs(1);
  };

  onMounted(() => {
    initActivityLogs();
  });

  return {
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
    resetFilters,
    initActivityLogs
  };
}
