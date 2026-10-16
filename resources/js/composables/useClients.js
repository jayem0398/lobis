import { ref, computed, watch, onMounted } from 'vue';
import axios from 'axios';
import { useToast } from 'vue-toastification';

export function useClients() {
  const toast = useToast();

  // ── Data ──────────────────────────────────────────────────────────────────
  const clients = ref([]);
  const users = ref([]);

  // ── Filter / Search ───────────────────────────────────────────────────────
  const searchQuery = ref('');
  const selectedStatus = ref('All');

  // ── Pagination ────────────────────────────────────────────────────────────
  const currentPage = ref(1);
  const perPage = ref(15);

  // ── Modal Visibility ──────────────────────────────────────────────────────
  const showAddModal = ref(false);
  const showViewModal = ref(false);
  const showDeleteModal = ref(false);
  const showEditConfirmModal = ref(false);

  // ── Working State ─────────────────────────────────────────────────────────
  const selectedClient = ref(null);
  const selectedClientForDeletion = ref(null);
  const isSubmitting = ref(false);
  const isLoading = ref(true);

  const emptyAddForm = () => ({
    client_name: '',
    contact_person: '',
    position: '',
    user_id: '',
    contact_number: '',
    tin_number: '',
    client_address: '',
    client_status: 'Approved',
  });

  const addForm = ref(emptyAddForm());

  // ── KPI Computeds ─────────────────────────────────────────────────────────
  const activeClientsCount = computed(() =>
    clients.value.filter(c => (c.client_status || c.status) === 'Approved').length
  );

  const pendingClientsCount = computed(() =>
    clients.value.filter(c => (c.client_status || c.status) === 'Pending Approval').length
  );

  // ── Filtering ─────────────────────────────────────────────────────────────
  const filteredClients = computed(() => {
    return clients.value.filter(client => {
      const status = client.client_status || client.status || '';
      const matchesStatus = selectedStatus.value === 'All' || status === selectedStatus.value;

      const query = searchQuery.value.toLowerCase().trim();
      if (!query) return matchesStatus;

      const name    = client.client_name?.toLowerCase() || '';
      const contact = client.contact_person?.toLowerCase() || '';
      const tin     = client.tin_number?.toLowerCase() || '';
      const code    = (client.client_code || 'CL-' + client.id).toLowerCase();

      return matchesStatus && (
        name.includes(query) || contact.includes(query) ||
        tin.includes(query)  || code.includes(query)
      );
    });
  });

  // ── Pagination Computeds ──────────────────────────────────────────────────
  const totalPages = computed(() => {
    if (perPage.value === -1) return 1;
    return Math.max(1, Math.ceil(filteredClients.value.length / perPage.value));
  });

  const paginationStart = computed(() => {
    if (filteredClients.value.length === 0) return 0;
    if (perPage.value === -1) return 1;
    return (currentPage.value - 1) * perPage.value + 1;
  });

  const paginationEnd = computed(() => {
    if (perPage.value === -1) return filteredClients.value.length;
    return Math.min(currentPage.value * perPage.value, filteredClients.value.length);
  });

  const paginatedClients = computed(() => {
    if (perPage.value === -1) return filteredClients.value;
    const start = (currentPage.value - 1) * perPage.value;
    return filteredClients.value.slice(start, start + perPage.value);
  });

  const visiblePages = computed(() => {
    const total = totalPages.value;
    const current = currentPage.value;
    if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1);

    const pages = [1];
    if (current > 3) pages.push('...');
    const start = Math.max(2, current - 1);
    const end = Math.min(total - 1, current + 1);
    for (let i = start; i <= end; i++) pages.push(i);
    if (current < total - 2) pages.push('...');
    pages.push(total);
    return pages;
  });

  // ── Watchers ──────────────────────────────────────────────────────────────
  watch([selectedStatus, searchQuery, perPage], () => { currentPage.value = 1; });

  // ── Helpers ───────────────────────────────────────────────────────────────
  const getUserName = (userId) => {
    const u = users.value.find(user => user.id === userId);
    return u ? u.name : null;
  };

  const getStatusClass = (status) => {
    switch (status) {
      case 'Approved':         return 'pill-amber';
      case 'Pending Approval': return 'pill-slate';
      case 'Suspended':        return 'pill-danger';
      default:                 return 'pill-slate';
    }
  };

  // ── API Calls ─────────────────────────────────────────────────────────────
  const fetchClients = async () => {
    isLoading.value = true;
    try {
      const response = await axios.get('/api/clients');
      clients.value = response.data.data || response.data || [];
    } catch (error) {
      toast.error('Failed to load clients data.');
    } finally {
      isLoading.value = false;
    }
  };

  const fetchUsers = async () => {
    try {
      const response = await axios.get('/api/users');
      users.value = response.data.data || response.data || [];
    } catch (error) {
      console.error('Failed to load users for assignment.', error);
    }
  };

  // ── Actions ───────────────────────────────────────────────────────────────
  const openAddModal = () => {
    addForm.value = emptyAddForm();
    showAddModal.value = true;
  };

  const openViewModal = (client) => {
    selectedClient.value = { ...client, client_status: client.client_status || client.status };
    showViewModal.value = true;
  };

  const promptDeleteClient = (client) => {
    selectedClientForDeletion.value = client;
    showDeleteModal.value = true;
  };

  const executeDeleteClient = async () => {
    if (!selectedClientForDeletion.value) return;
    isSubmitting.value = true;
    try {
      const id = selectedClientForDeletion.value.id;
      await axios.delete(`/api/clients/${id}`);
      clients.value = clients.value.filter(c => c.id !== id);
      toast.success('Client profile deleted successfully.');
      showDeleteModal.value = false;
    } catch (error) {
      toast.error('Failed to delete client profile.');
    } finally {
      isSubmitting.value = false;
    }
  };

  const saveNewClient = async () => {
    isSubmitting.value = true;
    try {
      await axios.post('/api/clients', addForm.value);
      toast.success('Client registered successfully!');
      showAddModal.value = false;
      await fetchClients();
    } catch (error) {
      toast.error(error.response?.data?.message || 'Failed to register client.');
    } finally {
      isSubmitting.value = false;
    }
  };

  const promptUpdateClient = () => {
    showEditConfirmModal.value = true;
  };

  const executeUpdateClient = async () => {
    isSubmitting.value = true;
    try {
      const res = await axios.put(`/api/clients/${selectedClient.value.id}`, selectedClient.value);
      const updated = res.data.data || res.data;
      const index = clients.value.findIndex(c => c.id === selectedClient.value.id);
      if (index !== -1) clients.value[index] = updated;

      toast.success('Client profile updated successfully!');
      showEditConfirmModal.value = false;
      showViewModal.value = false;
      await fetchClients();
    } catch (error) {
      toast.error(error.response?.data?.message || 'Failed to update client.');
    } finally {
      isSubmitting.value = false;
    }
  };

  // ── Lifecycle ─────────────────────────────────────────────────────────────
  const initClients = async () => {
    isLoading.value = true;
    await fetchClients();
    await fetchUsers();
    isLoading.value = false;
  };

  return {
    // State
    clients, users, searchQuery, selectedStatus,
    currentPage, perPage,
    showAddModal, showViewModal, showDeleteModal, showEditConfirmModal,
    selectedClient, selectedClientForDeletion, isSubmitting, isLoading, addForm,

    // Computeds
    activeClientsCount, pendingClientsCount,
    filteredClients, totalPages, paginationStart, paginationEnd,
    paginatedClients, visiblePages,

    // Helpers
    getUserName, getStatusClass,

    // Actions
    initClients, fetchClients,
    openAddModal, openViewModal,
    promptDeleteClient, executeDeleteClient,
    saveNewClient, promptUpdateClient, executeUpdateClient,
  };
}
