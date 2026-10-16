import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import { useToast } from 'vue-toastification';

export function useInvoiceCreate() {
  const toast = useToast();

  // ── State ──────────────────────────────────────────────────────────────────
  const isSubmitting = ref(false);
  const isUpdatingClient = ref(false);
  const isEditing = ref(false);
  const isSuperAdmin = ref(false);
  const currentUserId = ref(null);
  const invoiceEditCount = ref(0);
  const clients = ref([]);
  const selectedClient = ref(null);
  const sameAsBilling = ref(true);

  // Client search bar & autocomplete suggestions state
  const clientSearchQuery = ref('');
  const showClientDropdown = ref(false);
  const clientDropdownRef = ref(null);

  const editableClient = ref({
    contact_person: '',
    position: '',
    contact_number: '',
    client_address: '',
    tin_number: ''
  });

  const editableDelivery = ref({
    contact_person: '',
    position: '',
    contact_number: '',
    client_address: '',
    tin_number: ''
  });

  const today = new Date().toISOString().split('T')[0];

  const form = ref({
    id: null,
    invoice_number: 'Loading...',
    client_id: '',
    service_period_start: today,
    service_period_end: today,
    issue_date: today,
    payment_terms: '30',
    due_date: today,
    tax_type: 'vat_exclusive', // Default
    has_withholding_tax: false,
    discount: 0,
    notes: '',
    items: [{ item_name: '', quantity: 1, price: 0 }]
  });

  // ── Suggestions Filtering ──────────────────────────────────────────────────
  const filteredSuggestedClients = computed(() => {
    const q = clientSearchQuery.value.trim().toLowerCase();
    if (!q) {
      return clients.value.slice(0, 25);
    }
    return clients.value.filter(c => {
      const nameMatch = c.client_name && c.client_name.toLowerCase().includes(q);
      const tinMatch = c.tin_number && String(c.tin_number).toLowerCase().includes(q);
      const contactMatch = c.contact_person && c.contact_person.toLowerCase().includes(q);
      return nameMatch || tinMatch || contactMatch;
    });
  });

  // ── Client Interactions ────────────────────────────────────────────────────
  const selectClient = (client) => {
    form.value.client_id = client.id;
    clientSearchQuery.value = client.client_name;
    showClientDropdown.value = false;
    onClientChange();
  };

  const clearClientSelection = () => {
    if (isEditing.value && !isSuperAdmin.value) return;
    form.value.client_id = '';
    clientSearchQuery.value = '';
    selectedClient.value = null;
    editableClient.value = {
      contact_person: '',
      position: '',
      contact_number: '',
      client_address: '',
      tin_number: ''
    };
    if (sameAsBilling.value) {
      editableDelivery.value = { ...editableClient.value };
    }
    showClientDropdown.value = true;
  };

  const handleClientInputFocus = () => {
    if (isEditing.value && !isSuperAdmin.value) return;
    showClientDropdown.value = true;
  };

  const handleClickOutside = (event) => {
    if (clientDropdownRef.value && !clientDropdownRef.value.contains(event.target)) {
      showClientDropdown.value = false;
      if (selectedClient.value) {
        clientSearchQuery.value = selectedClient.value.client_name;
      } else if (!form.value.client_id) {
        clientSearchQuery.value = '';
      }
    }
  };

  const onClientChange = () => {
    const found = clients.value.find(c => c.id === form.value.client_id) || null;
    selectedClient.value = found;
    if (found) {
      clientSearchQuery.value = found.client_name;
      showClientDropdown.value = false;
      editableClient.value = {
        contact_person: found.contact_person || '',
        position: found.position || '',
        contact_number: found.contact_number || '',
        client_address: found.client_address || '',
        tin_number: found.tin_number || ''
      };
      if (sameAsBilling.value) {
        editableDelivery.value = { ...editableClient.value };
      }
    }
  };

  const handleSameAsBilling = () => {
    if (sameAsBilling.value) {
      editableDelivery.value = { ...editableClient.value };
    }
  };

  watch(editableClient, (newVal) => {
    if (sameAsBilling.value) {
      editableDelivery.value = { ...newVal };
    }
  }, { deep: true });

  const updateClientProfile = async () => {
    if (!form.value.client_id) return;
    isUpdatingClient.value = true;
    try {
      const res = await axios.put(`/api/clients/${form.value.client_id}`, editableClient.value);
      const updated = res.data.data || res.data;
      const index = clients.value.findIndex(c => c.id === form.value.client_id);
      if (index !== -1) clients.value[index] = updated;
      selectedClient.value = updated;
      toast.success('Billing information updated successfully!');
    } catch (error) {
      console.error('Failed to update client profile:', error);
      toast.error('Failed to update billing information.');
    } finally {
      isUpdatingClient.value = false;
    }
  };

  // ── Calculation Computeds ──────────────────────────────────────────────────
  const formatCurrency = (val) => Number(val || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

  const subtotal = computed(() => form.value.items.reduce((acc, i) => acc + (Number(i.quantity || 0) * Number(i.price || 0)), 0));

  const vatableSales = computed(() => {
    if (form.value.tax_type === 'vat_inclusive') return subtotal.value / 1.12;
    if (form.value.tax_type === 'vat_exclusive') return subtotal.value;
    return 0;
  });

  const vatAmount = computed(() => {
    if (form.value.tax_type === 'vat_inclusive') return subtotal.value - vatableSales.value;
    if (form.value.tax_type === 'vat_exclusive') return subtotal.value * 0.12;
    return 0;
  });

  const whtAmount = computed(() => {
    if (!form.value.has_withholding_tax) return 0;
    const baseForWht = form.value.tax_type === 'vat_exempt' ? subtotal.value : vatableSales.value;
    return baseForWht * 0.02;
  });

  const grandTotal = computed(() => {
    const discount = Number(form.value.discount) || 0;
    if (form.value.tax_type === 'vat_exclusive') {
      return Math.max(0, subtotal.value + vatAmount.value - discount - whtAmount.value);
    } else {
      return Math.max(0, subtotal.value - discount - whtAmount.value);
    }
  });

  const isFormComplete = computed(() =>
    form.value.client_id &&
    form.value.issue_date &&
    form.value.due_date &&
    form.value.items.length > 0 &&
    form.value.items.some(i => i.item_name && i.quantity > 0 && i.price > 0)
  );

  const submitLabel = computed(() => {
    if (isSubmitting.value) return 'Processing...';
    if (isEditing.value) return 'Update & Submit for Approval';
    return 'Create Invoice';
  });

  // ── Line Items Actions ────────────────────────────────────────────────────
  const addItem = () => form.value.items.push({ item_name: '', quantity: 1, price: 0 });

  const removeItem = (idx) => {
    if (form.value.items.length > 1) {
      const itemName = form.value.items[idx].item_name;
      if (itemName && !confirm(`Are you sure you want to remove item "${itemName}"?`)) {
        return;
      }
      form.value.items.splice(idx, 1);
    }
  };

  const calculateDueDate = () => {
    if (!form.value.issue_date) return;
    const issueDate = new Date(form.value.issue_date);
    issueDate.setDate(issueDate.getDate() + parseInt(form.value.payment_terms || 0));
    form.value.due_date = issueDate.toISOString().split('T')[0];
  };

  const cancel = () => { window.location.href = '/invoices'; };

  // ── Fetch Operations ──────────────────────────────────────────────────────
  const fetchNextInvoiceNumber = async () => {
    try {
      const res = await axios.get('/api/invoices/next-number');
      form.value.invoice_number = res.data.next_number;
    } catch (error) {
      console.error('Error fetching next invoice number:', error);
      form.value.invoice_number = '1000';
    }
  };

  const fetchInvoiceDetails = async (id) => {
    try {
      const res = await axios.get(`/api/invoices/${id}`);
      const data = res.data.data || res.data;

      const isOwner = currentUserId.value && Number(data.user_id) === Number(currentUserId.value);
      if (!isOwner && !isSuperAdmin.value) {
        toast.error('Hindi mo pwedeng i-edit ang invoice na ito. Ang creator/owner at Superadmin lamang ang pwedeng mag-edit.');
        setTimeout(() => { window.location.href = '/invoices'; }, 1000);
        return;
      }

      invoiceEditCount.value = data.edit_count || 0;

      let oldTaxType = 'vat_exclusive';
      if (data.tax_type === 'vat_inclusive') {
        oldTaxType = 'vat_inclusive';
      } else if (data.tax_type === 'vat_exempt' || data.tax_type === 'non-vat') {
        oldTaxType = 'vat_exempt';
      } else if (data.tax_amount == 0 && data.tax_type !== 'vat_inclusive' && data.tax_type !== 'vat_exclusive') {
        oldTaxType = 'vat_exempt';
      }

      const formatDateForInput = (d) => {
        if (!d) return '';
        const s = String(d);
        if (s.includes('T')) return s.split('T')[0];
        if (s.includes(' ')) return s.split(' ')[0];
        return s.substring(0, 10);
      };

      form.value = {
        id: data.id,
        invoice_number: data.invoice_number,
        client_id: data.client_id || '',
        service_period_start: formatDateForInput(data.service_period_start) || today,
        service_period_end: formatDateForInput(data.service_period_end) || today,
        issue_date: formatDateForInput(data.issue_date) || today,
        payment_terms: String(data.payment_terms || '30'),
        due_date: formatDateForInput(data.due_date) || today,
        tax_type: oldTaxType,
        has_withholding_tax: data.has_withholding_tax ? true : false,
        discount: data.discount_amount || 0,
        notes: data.notes || '',
        items: data.items && data.items.length > 0
          ? data.items.map(i => ({ item_name: i.item_name, quantity: i.quantity, price: i.price }))
          : [{ item_name: '', quantity: 1, price: 0 }]
      };

      if (form.value.client_id) {
        onClientChange();
        if (data.delivery_address || data.delivery_contact_person) {
          sameAsBilling.value = false;
          editableDelivery.value = {
            contact_person: data.delivery_contact_person || '',
            position: data.delivery_position || '',
            contact_number: data.delivery_phone || '',
            client_address: data.delivery_address || '',
            tin_number: data.delivery_tin || (selectedClient.value ? selectedClient.value.tin_number : '')
          };
        }
      }
    } catch (error) {
      console.error('Error loading draft invoice details:', error);
      toast.error('Hindi ma-load ang detalye ng draft invoice.');
    }
  };

  // ── Form Submission ───────────────────────────────────────────────────────
  const submitInvoice = async (statusType) => {
    if (isEditing.value) {
      if (!confirm(`Are you sure you want to save revisions to invoice #${form.value.invoice_number || form.value.id}?`)) {
        return;
      }
    }
    isSubmitting.value = true;
    try {
      const payload = {
        ...form.value,
        status: statusType,
        subtotal: subtotal.value,
        tax_amount: vatAmount.value,
        has_withholding_tax: form.value.has_withholding_tax,
        withholding_tax_amount: whtAmount.value,
        discount_amount: form.value.discount || 0,
        total_amount: grandTotal.value,
        delivery_address: editableDelivery.value.client_address,
        delivery_contact_person: editableDelivery.value.contact_person,
        delivery_position: editableDelivery.value.position,
        delivery_phone: editableDelivery.value.contact_number
      };

      if (isEditing.value && form.value.id) {
        await axios.put(`/api/invoices/${form.value.id}`, payload);
        toast.success(statusType === 'Draft' ? 'Draft updated successfully!' : 'Invoice updated and submitted for approval!');
      } else {
        await axios.post('/api/invoices', payload);
        toast.success(statusType === 'Draft' ? 'Invoice saved as draft!' : 'Invoice created and submitted for approval!');
      }

      setTimeout(() => { window.location.href = '/invoices'; }, 1000);

    } catch (error) {
      console.error('Submission error:', error.response?.data || error.message);
      toast.error(error.response?.data?.message || 'Failed to save service invoice.');
    } finally {
      isSubmitting.value = false;
    }
  };

  // ── Initialization & Cleanup ──────────────────────────────────────────────
  const initInvoiceCreate = async () => {
    document.addEventListener('click', handleClickOutside);

    try {
      const userRes = await axios.get('/api/user');
      const user = userRes.data.user || userRes.data;
      currentUserId.value = user?.id || null;
      isSuperAdmin.value = user && user.role && user.role.toLowerCase().trim() === 'superadmin';
    } catch (e) {
      console.error('Failed to load user info:', e);
    }

    try {
      const response = await axios.get('/api/clients');
      clients.value = response.data.data || response.data;
    } catch (error) {
      console.error('Failed to load clients list:', error);
      toast.error('Failed to load client list.');
    }

    const urlParams = new URLSearchParams(window.location.search);
    const invoiceId = urlParams.get('id');

    if (invoiceId) {
      isEditing.value = true;
      await fetchInvoiceDetails(invoiceId);
    } else {
      await fetchNextInvoiceNumber();
    }
  };

  const destroyInvoiceCreate = () => {
    document.removeEventListener('click', handleClickOutside);
  };

  onMounted(() => {
    initInvoiceCreate();
  });

  onUnmounted(() => {
    destroyInvoiceCreate();
  });

  return {
    // State
    isSubmitting, isUpdatingClient, isEditing, isSuperAdmin,
    currentUserId, invoiceEditCount, clients, selectedClient,
    sameAsBilling, clientSearchQuery, showClientDropdown, clientDropdownRef,
    editableClient, editableDelivery, form,

    // Computeds
    filteredSuggestedClients, subtotal, vatableSales, vatAmount,
    whtAmount, grandTotal, isFormComplete, submitLabel,

    // Helpers & Actions
    formatCurrency, selectClient, clearClientSelection,
    handleClientInputFocus, onClientChange, handleSameAsBilling,
    updateClientProfile, addItem, removeItem, calculateDueDate,
    cancel, submitInvoice, initInvoiceCreate
  };
}
