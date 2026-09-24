<template>
  <app-layout>
    <div class="dashboard-page-wrapper">
      
      <div class="analytics-card-panel">
        <div class="panel-header-box">
          <div class="header-title-group">
            <h3>{{ isEditing ? 'Edit / Continue Draft Invoice' : 'Create Service Invoice' }}</h3>
            <span v-if="isEditing && invoiceEditCount > 0" class="edit-count-badge">
              Edit #{{ invoiceEditCount }}
            </span>
          </div>
          <a href="/invoices" class="btn-cancel-link">← Back to Invoices</a>
        </div>

        <form @submit.prevent="submitInvoice('Pending Approval')" class="panel-body-content">
          
          <div class="form-grid-columns mb-20">
            <div class="form-control-group">
              <label>Invoice Number</label>
              <!-- Superadmin: editable. Others: readonly -->
              <input
                v-model="form.invoice_number"
                type="text"
                :readonly="!isSuperAdmin"
                :class="isSuperAdmin ? 'superadmin-editable font-bold' : 'bg-gray-50 font-bold'"
                :title="isSuperAdmin ? 'Superadmin: you may enter a custom invoice number' : 'Auto-generated'"
              />
              <span v-if="isSuperAdmin" class="field-hint flex-hint">
                <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>
                Superadmin: editable manually
              </span>
            </div>
            
            <div class="form-control-group">
              <label>Client / Company</label>
              <select
                v-model="form.client_id"
                @change="onClientChange"
                required
                :disabled="isEditing && !isSuperAdmin"
                :class="{'bg-gray-50 cursor-not-allowed': isEditing && !isSuperAdmin}"
                :title="isEditing && !isSuperAdmin ? 'Only Superadmin can change the client of an existing invoice' : ''"
              >
                <option value="" disabled>-- Select Client --</option>
                <option v-for="client in clients" :key="client.id" :value="client.id">
                  {{ client.client_name }}
                </option>
              </select>
              <span v-if="isEditing && !isSuperAdmin" class="field-hint flex-hint text-slate-500">
                <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                Client cannot be changed once invoice is created (Superadmin only)
              </span>
            </div>

            <div class="form-control-group">
              <label>Billing Period Covered</label>
              <div class="date-range-group">
                <input v-model="form.service_period_start" type="date" title="Service Start" />
                <span class="to-text">to</span>
                <input v-model="form.service_period_end" type="date" title="Service End" />
              </div>
            </div>

            <div class="form-control-group">
              <label>Issue Date</label>
              <input v-model="form.issue_date" type="date" required @change="calculateDueDate" />
            </div>

            <div class="form-control-group">
              <label>Payment Terms</label>
              <select v-model="form.payment_terms" @change="calculateDueDate" required>
                <option value="0">Due on Receipt</option>
                <option value="15">Net 15 (15 Days)</option>
                <option value="30">Net 30 (30 Days)</option>
                <option value="60">Net 60 (60 Days)</option>
              </select>
            </div>
            
            <div class="form-control-group">
              <label>Due Date</label>
              <input v-model="form.due_date" type="date" required />
            </div>
          </div>

          <div v-if="selectedClient" class="dual-address-container mb-24">
            
            <div class="address-sub-card">
              <div class="sub-card-header flex-between-box">
                <span class="sub-card-title">Billing Information</span>
                <button type="button" @click="updateClientProfile" :disabled="isUpdatingClient" class="btn-inline-save">
                  {{ isUpdatingClient ? 'Saving...' : 'Save Profile' }}
                </button>
              </div>
              <div class="sub-card-body">
                <div class="compact-grid">
                  <div class="form-control-group">
                    <label>Contact Person</label>
                    <input v-model="editableClient.contact_person" type="text" placeholder="Contact person..." />
                  </div>
                  <div class="form-control-group">
                    <label>Position</label>
                    <input v-model="editableClient.position" type="text" placeholder="Position..." />
                  </div>
                  <div class="form-control-group">
                    <label>Contact Number / Email</label>
                    <input v-model="editableClient.contact_number" type="text" placeholder="Contact number..." />
                  </div>
                  <div class="form-control-group">
                    <label>TIN Number</label>
                    <input v-model="editableClient.tin_number" type="text" placeholder="TIN..." />
                  </div>
                </div>
                <div class="form-control-group mt-10">
                  <label>Billing Address</label>
                  <textarea v-model="editableClient.client_address" rows="2" placeholder="Complete billing address..."></textarea>
                </div>
              </div>
            </div>

            <div class="address-sub-card">
              <div class="sub-card-header flex-between-box">
                <span class="sub-card-title">Delivery Details</span>
                <label class="checkbox-label-inline">
                  <input type="checkbox" v-model="sameAsBilling" @change="handleSameAsBilling" />
                  <span>Same as billing</span>
                </label>
              </div>
              <div class="sub-card-body">
                <div class="compact-grid">
                  <div class="form-control-group">
                    <label>Contact Person</label>
                    <input v-model="editableDelivery.contact_person" :disabled="sameAsBilling" :class="{'bg-gray-50': sameAsBilling}" type="text" placeholder="Contact person..." />
                  </div>
                  <div class="form-control-group">
                    <label>Position</label>
                    <input v-model="editableDelivery.position" :disabled="sameAsBilling" :class="{'bg-gray-50': sameAsBilling}" type="text" placeholder="Position..." />
                  </div>
                  <div class="form-control-group">
                    <label>Contact Number / Email</label>
                    <input v-model="editableDelivery.contact_number" :disabled="sameAsBilling" :class="{'bg-gray-50': sameAsBilling}" type="text" placeholder="Contact number..." />
                  </div>
                  <div class="form-control-group">
                    <label>Reference / TIN</label>
                    <input v-model="editableDelivery.tin_number" :disabled="sameAsBilling" :class="{'bg-gray-50': sameAsBilling}" type="text" placeholder="TIN..." />
                  </div>
                </div>
                <div class="form-control-group mt-10">
                  <label>Delivery Address</label>
                  <textarea v-model="editableDelivery.client_address" :disabled="sameAsBilling" :class="{'bg-gray-50': sameAsBilling}" rows="2" placeholder="Complete delivery address..."></textarea>
                </div>
              </div>
            </div>

          </div>

          <div class="items-frame-container mb-24">
            <div class="table-header-title">Service Line Items &amp; Professional Fees</div>
            <table class="enterprise-data-table">
              <thead>
                <tr>
                  <th>Description</th>
                  <th style="width: 120px;">Quantity</th>
                  <th style="width: 180px;">Rate / Fee (₱)</th>
                  <th style="width: 160px;">Total</th>
                  <th style="width: 50px;"></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(item, index) in form.items" :key="index">
                  <td>
                    <textarea v-model="item.item_name" rows="2" placeholder="Enter service description..."></textarea>
                  </td>
                  <td>
                    <input v-model.number="item.quantity" type="number" min="0.1" step="any" />
                  </td>
                  <td>
                    <input v-model.number="item.price" type="number" min="0" step="any" />
                  </td>
                  <td class="font-bold text-slate-900 align-middle">
                    ₱{{ formatCurrency(item.quantity * item.price) }}
                  </td>
                  <td class="text-center align-middle">
                    <button type="button" @click="removeItem(index)" class="btn-delete-row" title="Remove">&times;</button>
                  </td>
                </tr>
              </tbody>
            </table>
            <div class="table-footer-bar">
              <button type="button" @click="addItem" class="btn-add-line">+ Add Service Item</button>
            </div>
          </div>

          <div class="bottom-split-section mb-24">
            <div class="form-control-group notes-box">
              <label>Service Terms &amp; Payment Instructions</label>
              <textarea v-model="form.notes" rows="4" placeholder="Enter bank details, GCash info, or payment remarks..."></textarea>
            </div>

            <div class="totals-calculation-card">
              <div class="calc-row-item">
                <span>Services Subtotal:</span>
                <span class="font-bold">₱{{ formatCurrency(subtotal) }}</span>
              </div>
              
              <div class="calc-row-item vat-selector-row">
                <span>Client Tax Type:</span>
                <select v-model="form.tax_type" class="select-inline-tax">
                  <option value="vat">VAT (12% Applicable)</option>
                  <option value="non-vat">Non-VAT (0% / Exempt)</option>
                </select>
              </div>

              <div class="calc-row-item" v-if="form.tax_type === 'vat'">
                <span>VAT Amount (12%):</span>
                <span>₱{{ formatCurrency(vatAmount) }}</span>
              </div>

              <div class="calc-row-item vat-selector-row">
                <span>2% Withholding Tax:</span>
                <select v-model="form.has_withholding_tax" class="select-inline-tax">
                  <option :value="false">No</option>
                  <option :value="true">Yes (Deduct 2%)</option>
                </select>
              </div>

              <div class="calc-row-item" v-if="form.has_withholding_tax">
                <span>WHT Amount (2%):</span>
                <span style="color: #dc2626;">-₱{{ formatCurrency(whtAmount) }}</span>
              </div>
              
              <div class="calc-row-item">
                <span>Discount / Adjustment (₱):</span>
                <input v-model.number="form.discount" type="number" min="0" step="any" class="input-inline-small" placeholder="0.00" />
              </div>
              
              <div class="calc-row-item final">
                <span>Total Due:</span>
                <span class="highlight-total-amount">₱{{ formatCurrency(grandTotal) }}</span>
              </div>
            </div>
          </div>

          <!-- Submit Row -->
          <div class="form-submit-row">
            <button type="button" @click="cancel" class="btn-cancel-action">Cancel</button>
            
            <button type="button" @click="submitInvoice('Draft')" :disabled="isSubmitting" class="btn-draft-action">
              Save as Draft
            </button>

            <button type="submit" :disabled="isSubmitting || !isFormComplete" class="btn-primary-action" :class="{'opacity-50 cursor-not-allowed': !isFormComplete}">
              {{ submitLabel }}
            </button>
          </div>
        </form>
      </div>

    </div>
  </app-layout>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import axios from 'axios';
import { useToast } from 'vue-toastification';
import AppLayout from '@/Layouts/AppLayout.vue';

const toast = useToast();
const isSubmitting = ref(false);
const isUpdatingClient = ref(false);
const isEditing = ref(false);
const isSuperAdmin = ref(false);
const currentUserId = ref(null);
const invoiceEditCount = ref(0);
const clients = ref([]);
const selectedClient = ref(null);
const sameAsBilling = ref(true);

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
  tax_type: 'vat',
  has_withholding_tax: false,
  discount: 0,
  notes: '',
  items: [{ item_name: '', quantity: 1, price: 0 }]
});

onMounted(async () => {
  try {
    // Load user role info
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
});

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

    // Check permission: Only owner or superadmin can edit
    const isOwner = currentUserId.value && Number(data.user_id) === Number(currentUserId.value);
    if (!isOwner && !isSuperAdmin.value) {
      toast.error('Hindi mo pwedeng i-edit ang invoice na ito. Ang creator/owner at Superadmin lamang ang pwedeng mag-edit.');
      setTimeout(() => { window.location.href = '/invoices'; }, 1000);
      return;
    }

    invoiceEditCount.value = data.edit_count || 0;

    form.value = {
      id: data.id,
      invoice_number: data.invoice_number,
      client_id: data.client_id || '',
      service_period_start: data.service_period_start || today,
      service_period_end: data.service_period_end || today,
      issue_date: data.issue_date || today,
      payment_terms: String(data.payment_terms || '30'),
      due_date: data.due_date || today,
      tax_type: data.tax_amount > 0 ? 'vat' : 'non-vat',
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

const onClientChange = () => {
  const found = clients.value.find(c => c.id === form.value.client_id) || null;
  selectedClient.value = found;
  if (found) {
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

const formatCurrency = (val) => Number(val || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

const subtotal = computed(() => form.value.items.reduce((acc, i) => acc + (Number(i.quantity || 0) * Number(i.price || 0)), 0));

const vatAmount = computed(() => form.value.tax_type === 'vat' ? subtotal.value * 0.12 : 0);

const whtAmount = computed(() => form.value.has_withholding_tax ? subtotal.value * 0.02 : 0);

const grandTotal = computed(() => Math.max(0, subtotal.value + vatAmount.value - (Number(form.value.discount) || 0) - whtAmount.value));

const isFormComplete = computed(() =>
  form.value.client_id &&
  form.value.issue_date &&
  form.value.due_date &&
  form.value.items.length > 0 &&
  form.value.items.some(i => i.item_name && i.quantity > 0 && i.price > 0)
);

// Dynamic submit button label
const submitLabel = computed(() => {
  if (isSubmitting.value) return 'Processing...';
  if (isEditing.value) return 'Update & Submit for Approval';
  return 'Create Invoice';
});

const addItem = () => form.value.items.push({ item_name: '', quantity: 1, price: 0 });

const removeItem = (idx) => {
  if (form.value.items.length > 1) form.value.items.splice(idx, 1);
};

const calculateDueDate = () => {
  if (!form.value.issue_date) return;
  const issueDate = new Date(form.value.issue_date);
  issueDate.setDate(issueDate.getDate() + parseInt(form.value.payment_terms || 0));
  form.value.due_date = issueDate.toISOString().split('T')[0];
};

const cancel = () => { window.location.href = '/invoices'; };

const submitInvoice = async (statusType) => {
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
</script>

<style scoped>
.dashboard-page-wrapper { max-width: 1400px; margin: 0 auto; padding-left: 1in; padding-right: 1in; padding-top: 24px; padding-bottom: 24px; box-sizing: border-box; }
.mt-10 { margin-top: 10px; }
.mb-20 { margin-bottom: 20px; }
.mb-24 { margin-bottom: 24px; }

.analytics-card-panel { background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }
.panel-header-box { background: #0f172a; padding: 18px 24px; display: flex; justify-content: space-between; align-items: center; }
.header-title-group { display: flex; align-items: center; gap: 12px; }
.panel-header-box h3 { color: #ffffff; font-size: 14px; font-weight: 700; text-transform: uppercase; margin: 0; letter-spacing: 0.5px; }
.btn-cancel-link { color: #94a3b8; font-size: 12px; font-weight: 600; text-decoration: none; }
.btn-cancel-link:hover { color: #ffffff; }

/* Edit count badge */
.edit-count-badge {
  background: linear-gradient(135deg, #f59e0b, #d97706);
  color: #ffffff;
  font-size: 10px;
  font-weight: 800;
  padding: 3px 10px;
  border-radius: 20px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  box-shadow: 0 2px 6px rgba(245, 158, 11, 0.4);
  animation: pulse-badge 2s infinite;
}
@keyframes pulse-badge {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.75; }
}

/* Superadmin editable field */
.superadmin-editable {
  border: 2px solid #7c3aed !important;
  background: #faf5ff !important;
  color: #4c1d95 !important;
  cursor: text !important;
}
.field-hint {
  font-size: 10px;
  color: #7c3aed;
  font-weight: 600;
  margin-top: 3px;
  display: block;
}
.flex-hint {
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

.panel-body-content { padding: 32px; }

.form-grid-columns { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
.form-control-group label { display: block; font-size: 10px; font-weight: 700; text-transform: uppercase; color: #64748b; margin-bottom: 4px; letter-spacing: 0.5px; }
.form-control-group input, .form-control-group textarea { width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 12px; color: #1e293b; outline: none; box-sizing: border-box; background: #ffffff; }
.form-control-group select {
  width: 100%;
  padding: 8px 34px 8px 12px;
  border: 1px solid #cbd5e1;
  border-radius: 6px;
  font-size: 12px;
  color: #1e293b;
  outline: none;
  box-sizing: border-box;
  background: #ffffff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E") no-repeat right 12px center;
  background-size: 12px;
  -webkit-appearance: none;
  -moz-appearance: none;
  appearance: none;
  cursor: pointer;
}
.form-control-group input:focus, .form-control-group select:focus, .form-control-group textarea:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
.bg-gray-50 { background-color: #f8fafc !important; cursor: not-allowed; color: #64748b !important; }

.dual-address-container { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
.address-sub-card { border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; background: #f8fafc; }
.sub-card-header { background: #f1f5f9; padding: 10px 14px; border-bottom: 1px solid #e2e8f0; }
.sub-card-title { font-size: 11px; font-weight: 700; text-transform: uppercase; color: #475569; letter-spacing: 0.5px; }
.sub-card-body { padding: 14px; background: #ffffff; }
.compact-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }

.flex-between-box { display: flex; justify-content: space-between; align-items: center; }
.btn-inline-save { background: #0284c7; color: #ffffff; border: none; padding: 4px 10px; border-radius: 4px; font-size: 10px; font-weight: 700; text-transform: uppercase; cursor: pointer; transition: background 0.2s; }
.btn-inline-save:hover:not(:disabled) { background: #0369a1; }
.btn-inline-save:disabled { opacity: 0.6; cursor: not-allowed; }

.checkbox-label-inline { display: flex; align-items: center; gap: 6px; font-size: 11px; font-weight: 600; color: #334155; text-transform: none; cursor: pointer; }
.checkbox-label-inline input { cursor: pointer; }

.date-range-group { display: flex; align-items: center; gap: 8px; }
.to-text { font-size: 12px; color: #64748b; font-weight: 600; }

.items-frame-container { border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; background: #f8fafc; }
.table-header-title { background: #f8fafc; padding: 12px 16px; font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; }
.enterprise-data-table { width: 100%; border-collapse: collapse; }
.enterprise-data-table th { background: #f1f5f9; padding: 12px 16px; text-align: left; font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; }
.enterprise-data-table td { padding: 12px 16px; border-bottom: 1px solid #e2e8f0; font-size: 13px; color: #334155; vertical-align: middle; background: #ffffff; }
.enterprise-data-table td textarea { width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; resize: vertical; outline: none; background: #ffffff; }
.enterprise-data-table td input { width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; outline: none; background: #ffffff; }
.font-bold { font-weight: 700; }
.text-slate-900 { color: #0f172a; }
.text-center { text-align: center; }
.align-middle { vertical-align: middle; }

.table-footer-bar { background: #f8fafc; padding: 12px 16px; border-top: 1px solid #e2e8f0; }
.btn-add-line { background: none; border: none; color: #3b82f6; font-size: 11px; font-weight: 700; text-transform: uppercase; cursor: pointer; }
.btn-delete-row { background: none; border: none; color: #ef4444; font-size: 20px; cursor: pointer; padding: 0 8px; }

.bottom-split-section { display: grid; grid-template-columns: 1fr 390px; gap: 24px; align-items: start; }
.notes-box textarea { resize: none; height: 160px; }

.totals-calculation-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px 20px; }
.calc-row-item { display: flex; justify-content: space-between; align-items: center; font-size: 13px; color: #475569; padding: 8px 0; border-bottom: 1px dashed #cbd5e1; }
.vat-selector-row { padding: 10px 0; }

.select-inline-tax {
  width: 185px;
  padding: 6px 34px 6px 10px;
  border: 1px solid #cbd5e1;
  border-radius: 6px;
  font-size: 12px;
  background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E") no-repeat right 12px center;
  background-size: 12px;
  -webkit-appearance: none;
  -moz-appearance: none;
  appearance: none;
  outline: none;
  font-weight: 600;
  color: #0f172a;
  cursor: pointer;
}

.calc-row-item.final { border: none; padding-top: 16px; margin-top: 8px; border-top: 2px solid #e2e8f0; }
.input-inline-small { width: 100px; padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 6px; text-align: right; font-size: 13px; outline: none; }
.highlight-total-amount { color: #2563eb; font-size: 20px; font-weight: 800; }

.form-submit-row { display: flex; justify-content: flex-end; gap: 16px; border-top: 1px solid #e2e8f0; padding-top: 24px; }
.btn-cancel-action { background: #f1f5f9; color: #475569; padding: 10px 20px; border-radius: 8px; font-size: 12px; font-weight: 700; border: none; cursor: pointer; text-transform: uppercase; }
.btn-draft-action { background: #64748b; color: #ffffff; padding: 10px 20px; border-radius: 8px; font-size: 12px; font-weight: 700; border: none; cursor: pointer; text-transform: uppercase; }
.btn-draft-action:hover:not(:disabled) { background: #475569; }
.btn-primary-action { background: #3b82f6; color: #ffffff; padding: 10px 24px; border-radius: 8px; font-size: 12px; font-weight: 700; border: none; cursor: pointer; text-transform: uppercase; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3); }
.btn-primary-action:hover:not(:disabled) { background: #2563eb; }
.opacity-50 { opacity: 0.5; }
.cursor-not-allowed { cursor: not-allowed; }
</style>