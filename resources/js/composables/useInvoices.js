import { ref, computed, watch, onMounted } from 'vue';
import axios from 'axios';
import { useToast } from 'vue-toastification';

export function useInvoices() {
  const toast = useToast();

  // ── Auth / Role State ─────────────────────────────────────────────────────
  const isAdmin = ref(false);
  const isSuperAdmin = ref(false);
  const currentUserId = ref(null);
  const isAdminOrSuper = computed(() => isAdmin.value || isSuperAdmin.value);

  // ── Data ──────────────────────────────────────────────────────────────────
  const invoices = ref([]);
  const clients = ref([]);
  const teams = ref([]);

  // ── Filter / Search / Tab State ───────────────────────────────────────────
  const activeTab = ref('All');
  const searchQuery = ref('');
  const selectedTeamFilter = ref('');

  // ── Pagination ────────────────────────────────────────────────────────────
  const currentPage = ref(1);
  const perPage = ref(15);

  // ── Modal Visibility ──────────────────────────────────────────────────────
  const showViewModal = ref(false);
  const showDeleteModal = ref(false);
  const showEditConfirmModal = ref(false);
  const showApproveDeleteModal = ref(false);

  // ── Selected / Working State ──────────────────────────────────────────────
  const selectedInvoiceForDeletion = ref(null);
  const deletionReason = ref('');
  const isSubmitting = ref(false);
  const isLoading = ref(true);
  const originalStatus = ref('');

  const emptyModalForm = () => ({
    id: null,
    user_id: null,
    invoice_number: '',
    client_id: '',
    issue_date: '',
    due_date: '',
    status: 'Draft',
    vat_type: 'vat_exclusive',
    has_withholding_tax: false,
    notes: '',
    items: [],
    subtotal: 0,
    tax_amount: 0,
    withholding_tax: 0,
    total_amount: 0,
    edit_count: 0,
    deletion_requested: false,
    deletion_reason: '',
    discount_amount: 0,
    delivery_address: '',
    delivery_contact_person: '',
    delivery_position: '',
    delivery_phone: '',
  });

  const modalForm = ref(emptyModalForm());

  const currentDate = new Date();

  // ── Tabs ──────────────────────────────────────────────────────────────────
  const availableTabs = computed(() => {
    const base = [
      'All', 'Paid', 'Pending Approval',
      'Current (Not Due)', '1-30 Days Overdue',
      '31-60 Days Overdue', '60+ Days Overdue', 'Draft',
    ];
    if (isAdminOrSuper.value) base.push('Deletion Requests');
    return base;
  });

  const pendingDeletionCount = computed(() =>
    invoices.value.filter(i => i.deletion_requested).length
  );

  // ── KPI Computeds ─────────────────────────────────────────────────────────
  const totalInvoiced = computed(() =>
    invoices.value.reduce((sum, inv) => sum + Number(inv.total_amount || 0), 0)
  );

  const totalCollected = computed(() =>
    invoices.value
      .filter(inv => inv.status === 'Paid')
      .reduce((sum, inv) => sum + Number(inv.total_amount || 0), 0)
  );

  const outstandingBalance = computed(() =>
    invoices.value
      .filter(inv => inv.status !== 'Paid' && inv.status !== 'Cancelled')
      .reduce((sum, inv) => sum + Number(inv.total_amount || 0), 0)
  );

  const overdueAmount = computed(() =>
    invoices.value
      .filter(inv => {
        const days = getOverdueDays(inv.due_date);
        return days > 0 && inv.status !== 'Paid' && inv.status !== 'Cancelled';
      })
      .reduce((sum, inv) => sum + Number(inv.total_amount || 0), 0)
  );

  const activeTeamQuota = computed(() => {
    if (!selectedTeamFilter.value || selectedTeamFilter.value === '') {
      return teams.value.reduce((sum, t) => sum + Number(t.quota || 0), 0) || 1;
    }
    const found = teams.value.find(t => t.id == selectedTeamFilter.value);
    return found ? Number(found.quota || 1) : 1;
  });

  const quotaPercentage = computed(() => {
    const target = activeTeamQuota.value;
    if (target <= 0) return 0;
    return Math.round((totalCollected.value / target) * 100);
  });

  // ── Filtering ─────────────────────────────────────────────────────────────
  const filteredInvoices = computed(() => {
    return invoices.value.filter(inv => {
      const daysOverdue = getOverdueDays(inv.due_date);
      const isPaidOrDraft = ['Paid', 'Draft', 'Cancelled'].includes(inv.status);

      let matchesTab = true;
      if (activeTab.value === 'Paid')                     matchesTab = inv.status === 'Paid';
      else if (activeTab.value === 'Draft')               matchesTab = inv.status === 'Draft';
      else if (activeTab.value === 'Pending Approval')    matchesTab = inv.status === 'Pending Approval';
      else if (activeTab.value === 'Current (Not Due)')   matchesTab = daysOverdue <= 0 && !isPaidOrDraft;
      else if (activeTab.value === '1-30 Days Overdue')   matchesTab = daysOverdue >= 1 && daysOverdue <= 30 && !isPaidOrDraft;
      else if (activeTab.value === '31-60 Days Overdue')  matchesTab = daysOverdue >= 31 && daysOverdue <= 60 && !isPaidOrDraft;
      else if (activeTab.value === '60+ Days Overdue')    matchesTab = daysOverdue > 60 && !isPaidOrDraft;
      else if (activeTab.value === 'Deletion Requests')   matchesTab = inv.deletion_requested === true;

      const q = searchQuery.value?.toLowerCase().trim() || '';
      if (!q) return matchesTab;

      const invNum = String(inv.invoice_number || '').toLowerCase();
      const clientNameStr = String(
        inv.client ? inv.client.client_name : (inv.client_name || '')
      ).toLowerCase();

      return matchesTab && (invNum.includes(q) || clientNameStr.includes(q));
    });
  });

  // ── Pagination Computeds ──────────────────────────────────────────────────
  const totalPages = computed(() => {
    if (perPage.value === -1) return 1;
    return Math.max(1, Math.ceil(filteredInvoices.value.length / perPage.value));
  });

  const paginationStart = computed(() => {
    if (filteredInvoices.value.length === 0) return 0;
    if (perPage.value === -1) return 1;
    return (currentPage.value - 1) * perPage.value + 1;
  });

  const paginationEnd = computed(() => {
    if (perPage.value === -1) return filteredInvoices.value.length;
    return Math.min(currentPage.value * perPage.value, filteredInvoices.value.length);
  });

  const paginatedInvoices = computed(() => {
    if (perPage.value === -1) return filteredInvoices.value;
    const start = (currentPage.value - 1) * perPage.value;
    return filteredInvoices.value.slice(start, start + perPage.value);
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

  // ── Modal Computeds ───────────────────────────────────────────────────────
  const modalSubtotal = computed(() => {
    if (!modalForm.value.items || !Array.isArray(modalForm.value.items)) return 0;
    return modalForm.value.items.reduce(
      (sum, i) => sum + Number(i.quantity || 0) * Number(i.price || 0),
      0
    );
  });

  const modalVatableSales = computed(() => {
    const type = (modalForm.value.vat_type || 'vat_exclusive').toLowerCase();
    if (type === 'vat_inclusive') return modalSubtotal.value / 1.12;
    return modalSubtotal.value;
  });

  const modalTaxAmount = computed(() => {
    const type = (modalForm.value.vat_type || 'vat_exclusive').toLowerCase();
    if (['vat_exempt', 'exempt', 'non-vat'].includes(type)) return 0;
    if (type === 'vat_inclusive') return modalSubtotal.value - modalVatableSales.value;
    return modalSubtotal.value * 0.12;
  });

  const modalWhtAmount = computed(() => {
    if (!modalForm.value.has_withholding_tax) return 0;
    return modalVatableSales.value * 0.02;
  });

  const modalTotalAmount = computed(() => {
    const type = (modalForm.value.vat_type || 'vat_exclusive').toLowerCase();
    const sub = modalSubtotal.value;
    const tax = modalTaxAmount.value;
    const wht = modalWhtAmount.value;
    const discount = Number(modalForm.value.discount_amount) || 0;
    if (type === 'vat_inclusive') return Math.max(0, sub - wht - discount);
    return Math.max(0, sub + tax - wht - discount);
  });

  const canEditModalInvoice = computed(() => {
    if (!modalForm.value.id) return false;
    if (isSuperAdmin.value) return true;
    return currentUserId.value && Number(modalForm.value.user_id) === Number(currentUserId.value);
  });

  const isModalEditable = computed(() => {
    if (!canEditModalInvoice.value) return false;
    return modalForm.value.status !== 'Paid';
  });

  const dynamicStatusBtnLabel = computed(() => {
    const status = modalForm.value.status;
    if (status === 'Approved') return 'Approve Document';
    if (status === 'Paid') return 'Mark as Paid';
    if (status === 'Cancelled') return 'Cancel Invoice';
    if (status === 'Pending Approval') return 'Set as Pending Approval';
    return 'Save Status Change';
  });

  const dynamicStatusBtnClass = computed(() => {
    const status = modalForm.value.status;
    if (status === 'Approved') return 'bg-emerald-600 border-emerald-600 hover:bg-emerald-700';
    if (status === 'Paid') return 'bg-sky-600 border-sky-600 hover:bg-sky-700';
    if (status === 'Cancelled') return 'bg-slate-700 border-slate-700 hover:bg-slate-800';
    if (status === 'Pending Approval') return 'bg-amber-600 border-amber-600 hover:bg-amber-700';
    return 'bg-slate-900';
  });

  // ── Watchers ──────────────────────────────────────────────────────────────
  watch(selectedTeamFilter, () => fetchInvoices());
  watch([searchQuery, activeTab, selectedTeamFilter, perPage], () => { currentPage.value = 1; });

  // ── Utility Helpers ───────────────────────────────────────────────────────
  const formatCurrency = (val) =>
    Number(val || 0).toLocaleString('en-US', { minimumFractionDigits: 2 });

  const getOverdueDays = (dueDateStr) => {
    if (!dueDateStr) return 0;
    return Math.ceil((currentDate - new Date(dueDateStr)) / (1000 * 60 * 60 * 24));
  };

  const getDisplayStatus = (inv) => {
    if (['Paid', 'Draft', 'Pending Approval', 'Cancelled', 'Approved'].includes(inv.status))
      return inv.status;
    const days = getOverdueDays(inv.due_date);
    if (days <= 0) return 'Current';
    if (days <= 30) return '1-30 Days Overdue';
    if (days <= 60) return '31-60 Days Overdue';
    return '60+ Days Overdue';
  };

  const getBadgeClass = (inv) => {
    if (inv.status === 'Paid')             return 'pill-success';
    if (inv.status === 'Draft')            return 'pill-info';
    if (inv.status === 'Approved')         return 'pill-approved';
    if (inv.status === 'Pending Approval') return 'pill-warning';
    if (inv.status === 'Cancelled')        return 'pill-cancelled';
    const days = getOverdueDays(inv.due_date);
    if (days <= 0) return 'pill-success';
    if (days <= 30) return 'pill-warning';
    return 'pill-danger';
  };

  const getClientName = (clientId) => {
    const found = clients.value.find(c => c.id === clientId);
    return found ? found.client_name : 'N/A';
  };

  const formatDateForInput = (d) => {
    if (!d) return '';
    const s = String(d);
    if (s.includes('T')) return s.split('T')[0];
    if (s.includes(' ')) return s.split(' ')[0];
    return s.substring(0, 10);
  };

  const handleImageError = (e) => { e.target.style.display = 'none'; };

  // ── API Calls ─────────────────────────────────────────────────────────────
  const fetchUserData = async () => {
    try {
      const res = await axios.get('/api/user');
      const user = res.data.user || res.data;
      currentUserId.value = user?.id || null;
      const role = user?.role?.toLowerCase().trim() ?? '';
      isAdmin.value = role === 'admin' || role === 'superadmin';
      isSuperAdmin.value = role === 'superadmin';
    } catch (e) {
      console.error('Failed to load user info:', e);
    }
  };

  const fetchInvoices = async () => {
    isLoading.value = true;
    try {
      const params = {};
      if (selectedTeamFilter.value && selectedTeamFilter.value !== '') {
        params.team_id = selectedTeamFilter.value;
      }
      const res = await axios.get('/api/invoices', { params });
      invoices.value = res.data.data || res.data;
    } catch (error) {
      console.error('Error fetching invoices:', error);
      toast.error('Failed to load invoices from database.');
    } finally {
      isLoading.value = false;
    }
  };

  const fetchClients = async () => {
    try {
      const res = await axios.get('/api/clients');
      clients.value = res.data.data || res.data;
    } catch (error) {
      console.error('Error fetching clients:', error);
    }
  };

  const fetchTeams = async () => {
    try {
      const res = await axios.get('/api/teams');
      teams.value = res.data.data || res.data;
    } catch (error) {
      console.error('Error fetching teams:', error);
    }
  };

  // ── Actions ───────────────────────────────────────────────────────────────
  const openViewModal = async (inv) => {
    if (inv.status === 'Draft') {
      window.location.href = `/invoices/create?id=${inv.id}`;
      return;
    }
    try {
      const res = await axios.get(`/api/invoices/${inv.id}`);
      const data = res.data.data || res.data;

      // Normalize DB tax type to match dropdown values
      let dbTaxType = data.tax_type ? data.tax_type.toLowerCase().trim() : 'vat_exclusive';
      if (dbTaxType === 'vat' || dbTaxType === 'exclusive') dbTaxType = 'vat_exclusive';
      if (['exempt', 'non-vat', 'non_vat'].includes(dbTaxType)) dbTaxType = 'vat_exempt';
      if (dbTaxType === 'inclusive') dbTaxType = 'vat_inclusive';

      const withholding = data.withholding_tax || data.withholding_tax_amount || 0;

      modalForm.value = {
        id: data.id,
        user_id: data.user_id,
        invoice_number: data.invoice_number,
        client_id: data.client_id,
        issue_date: formatDateForInput(data.issue_date),
        due_date: formatDateForInput(data.due_date),
        status: data.status || 'Draft',
        vat_type: dbTaxType,
        has_withholding_tax: data.has_withholding_tax ? true : withholding > 0,
        notes: data.notes || '',
        items: data.items?.length > 0
          ? data.items.map(i => ({ item_name: i.item_name, quantity: i.quantity, price: i.price }))
          : [{ item_name: '', quantity: 1, price: 0 }],
        subtotal: data.subtotal || 0,
        tax_amount: data.tax_amount || 0,
        withholding_tax: withholding,
        total_amount: data.total_amount || 0,
        edit_count: data.edit_count || 0,
        deletion_requested: data.deletion_requested || false,
        deletion_reason: data.deletion_reason || '',
        discount_amount: data.discount_amount || 0,
        delivery_address: data.delivery_address || '',
        delivery_contact_person: data.delivery_contact_person || '',
        delivery_position: data.delivery_position || '',
        delivery_phone: data.delivery_phone || '',
      };

      originalStatus.value = data.status;
      showViewModal.value = true;
    } catch (error) {
      console.error('Error loading invoice details:', error);
      toast.error('Failed to load invoice details.');
    }
  };

  const addModalItem = () => {
    if (!modalForm.value.items) modalForm.value.items = [];
    modalForm.value.items.push({ item_name: '', quantity: 1, price: 0 });
  };

  const removeModalItem = (index) => {
    if (modalForm.value.items?.length > 1) {
      modalForm.value.items.splice(index, 1);
    }
  };

  const printInvoice = () => window.open(`/api/invoices/${modalForm.value.id}/pdf`, '_blank');

  const promptSaveModalChanges = () => { showEditConfirmModal.value = true; };

  const executeSaveModalChanges = async () => {
    if (!modalForm.value.id) return;
    isSubmitting.value = true;
    try {
      const payload = {
        invoice_number: modalForm.value.invoice_number,
        client_id: modalForm.value.client_id,
        issue_date: modalForm.value.issue_date,
        due_date: modalForm.value.due_date,
        tax_type: modalForm.value.vat_type,
        has_withholding_tax: modalForm.value.has_withholding_tax,
        delivery_address: modalForm.value.delivery_address,
        delivery_contact_person: modalForm.value.delivery_contact_person,
        delivery_position: modalForm.value.delivery_position,
        delivery_phone: modalForm.value.delivery_phone,
        notes: modalForm.value.notes,
        items: modalForm.value.items,
        status: modalForm.value.status,
      };

      await axios.put(`/api/invoices/${modalForm.value.id}`, payload);

      const message = (!isAdminOrSuper.value && originalStatus.value !== 'Draft')
        ? 'Invoice updated! Submitted for approval (Edit count updated).'
        : 'Invoice details saved successfully!';

      toast.success(message);
      showEditConfirmModal.value = false;
      showViewModal.value = false;
      await fetchInvoices();
    } catch (error) {
      console.error('Failed to save invoice edits:', error);
      toast.error(error.response?.data?.message || 'Failed to save invoice changes.');
    } finally {
      isSubmitting.value = false;
    }
  };

  const saveStatusChange = async () => {
    try {
      await axios.put(`/api/invoices/${modalForm.value.id}`, { status: modalForm.value.status });
      await fetchInvoices();
      toast.success(`Invoice status updated to "${modalForm.value.status}".`);
      originalStatus.value = modalForm.value.status;
      showViewModal.value = false;
    } catch (error) {
      toast.error(error.response?.data?.message || 'Failed to update status.');
    }
  };

  const promptDeleteInvoice = (inv) => {
    selectedInvoiceForDeletion.value = inv;
    deletionReason.value = '';
    showDeleteModal.value = true;
  };

  const executeDeletion = async () => {
    if (!selectedInvoiceForDeletion.value) return;
    isSubmitting.value = true;
    try {
      if (isAdminOrSuper.value) {
        await axios.delete(`/api/invoices/${selectedInvoiceForDeletion.value.id}`);
        invoices.value = invoices.value.filter(i => i.id !== selectedInvoiceForDeletion.value.id);
        toast.success('Invoice deleted successfully.');
      } else {
        await axios.post(`/api/invoices/${selectedInvoiceForDeletion.value.id}/delete-request`, {
          reason: deletionReason.value,
        });
        const inv = invoices.value.find(i => i.id === selectedInvoiceForDeletion.value.id);
        if (inv) { inv.deletion_requested = true; inv.deletion_reason = deletionReason.value; }
        toast.success('Deletion request submitted. Awaiting admin approval.');
      }
      showDeleteModal.value = false;
    } catch (error) {
      toast.error(error.response?.data?.message || 'Failed to process deletion request.');
    } finally {
      isSubmitting.value = false;
    }
  };

  const promptApproveDeletion = () => { showApproveDeleteModal.value = true; };

  const executeApproveDeletion = async () => {
    isSubmitting.value = true;
    try {
      await axios.delete(`/api/invoices/${modalForm.value.id}`);
      invoices.value = invoices.value.filter(i => i.id !== modalForm.value.id);
      toast.success('Invoice deleted successfully.');
      showApproveDeleteModal.value = false;
      showViewModal.value = false;
    } catch (error) {
      toast.error('Failed to delete invoice.');
    } finally {
      isSubmitting.value = false;
    }
  };

  // ── Lifecycle ─────────────────────────────────────────────────────────────
  const initInvoices = async () => {
    isLoading.value = true;
    await fetchUserData();
    await fetchInvoices();
    await fetchClients();
    if (isAdminOrSuper.value) {
      await fetchTeams();
    }
    isLoading.value = false;
  };

  return {
    // State
    invoices, clients, teams,
    activeTab, searchQuery, selectedTeamFilter,
    currentPage, perPage,
    isAdmin, isSuperAdmin, currentUserId,
    showViewModal, showDeleteModal, showEditConfirmModal, showApproveDeleteModal,
    selectedInvoiceForDeletion, deletionReason, isSubmitting, isLoading, originalStatus,
    modalForm,

    // Computeds
    isAdminOrSuper, availableTabs, pendingDeletionCount,
    totalInvoiced, totalCollected, outstandingBalance, overdueAmount,
    activeTeamQuota, quotaPercentage,
    filteredInvoices, totalPages, paginationStart, paginationEnd,
    paginatedInvoices, visiblePages,
    modalSubtotal, modalVatableSales, modalTaxAmount, modalWhtAmount, modalTotalAmount,
    canEditModalInvoice, isModalEditable,
    dynamicStatusBtnLabel, dynamicStatusBtnClass,

    // Helpers
    formatCurrency, getOverdueDays, getDisplayStatus, getBadgeClass,
    getClientName, formatDateForInput, handleImageError,

    // Actions
    initInvoices, fetchInvoices,
    openViewModal, addModalItem, removeModalItem, printInvoice,
    promptSaveModalChanges, executeSaveModalChanges, saveStatusChange,
    promptDeleteInvoice, executeDeletion,
    promptApproveDeletion, executeApproveDeletion,
  };
}
