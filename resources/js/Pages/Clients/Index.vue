<template>
  <app-layout>
    <div class="dashboard-page-wrapper">
      
      <div class="kpi-grid-container mb-24">
        <div class="kpi-card-item">
          <div class="kpi-info">
            <span class="kpi-label">Active Clients</span>
            <span class="kpi-number">{{ activeClientsCount }}</span>
          </div>
          <div class="kpi-icon-box">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
          </div>
        </div>
        <div class="kpi-card-item">
          <div class="kpi-info">
            <span class="kpi-label">Pending Approvals</span>
            <span class="kpi-number">{{ pendingClientsCount }}</span>
          </div>
          <div class="kpi-icon-box">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
          </div>
        </div>
        <div class="kpi-card-item">
          <div class="kpi-info">
            <span class="kpi-label">Total Registered</span>
            <span class="kpi-number">{{ clients.length }}</span>
          </div>
          <div class="kpi-icon-box">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>
          </div>
        </div>
      </div>

      <div class="analytics-card-panel">
        <div class="panel-header-box">
          <h3>Client Directory</h3>
          <button @click="openAddModal" class="btn-primary-action">+ Add Client</button>
        </div>

        <div class="panel-body-content">
          <div class="table-controls-row mb-20">
            <div class="modern-tabs-container">
              <button 
                v-for="status in ['All', 'Approved', 'Pending Approval', 'Suspended']" 
                :key="status"
                @click="selectedStatus = status"
                :class="['modern-tab-btn', { active: selectedStatus === status }]"
              >
                {{ status }}
              </button>
            </div>

            <div class="search-input-box">
              <svg class="search-icon" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
              <input v-model="searchQuery" type="text" placeholder="Search code, company, contact person, supervisor..." />
            </div>
          </div>

          <div class="items-table-frame">
            <table class="enterprise-data-table">
              <thead>
                <tr>
                  <th>Client Code</th>
                  <th>Company Name</th>
                  <th>Contact Person & Position</th>
                  <th>Assigned Supervisor / POC</th>
                  <th>Status</th>
                  <th style="width: 140px; text-align: center;">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="client in filteredClients" :key="client.id">
                  <td class="font-mono font-bold text-slate-700">{{ client.client_code || 'CL-' + String(client.id).padStart(4, '0') }}</td>
                  <td class="font-bold text-slate-900">{{ client.client_name }}</td>
                  <td>
                    <div class="contact-meta">
                      <span class="main-contact-email">{{ client.contact_person }}</span>
                      <small class="sub-contact-num">{{ client.position || 'N/A' }}</small>
                    </div>
                  </td>
                  <td>
                    <span class="font-bold text-indigo-700">
                      {{ client.user ? client.user.name : (getUserName(client.user_id) || 'Unassigned') }}
                    </span>
                  </td>
                  <td>
                    <span :class="['status-pill', getStatusClass(client.client_status || client.status)]">
                      {{ client.client_status || client.status }}
                    </span>
                  </td>
                  <td class="text-center">
                    <div class="action-buttons-group">
                      <button @click="openEditModal(client)" class="action-btn view-edit-btn" title="View & Update Details">
                        <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                      </button>
                      <button @click="deleteClient(client.id)" class="action-btn delete-btn" title="Delete Client">
                        <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                      </button>
                    </div>
                  </td>
                </tr>

                <tr v-if="filteredClients.length === 0">
                  <td colspan="6" class="empty-data-message">No matching client records found.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div v-if="showModal" class="modal-backdrop">
        <div class="modal-card-container">
          <div class="modal-header-bar">
            <h3>New Client Registration</h3>
            <button @click="showModal = false" class="btn-close-modal">&times;</button>
          </div>

          <form @submit.prevent="saveClient" class="modal-body-content">
            <div class="form-grid-columns">
              <div class="form-control-group">
                <label>Client Code</label>
                <input v-model="form.client_code" type="text" readonly class="bg-gray-50 font-mono font-bold" placeholder="Auto-generated" />
              </div>

              <div class="form-control-group">
                <label>TIN Number</label>
                <input v-model="form.tin_number" type="text" placeholder="000-000-000-000" />
              </div>

              <div class="form-control-group full-width">
                <label>Company / Client Name</label>
                <input v-model="form.client_name" type="text" required placeholder="e.g. Acme Corporation Philippines Inc." />
              </div>

              <div class="form-control-group">
                <label>Contact Person</label>
                <input v-model="form.contact_person" type="text" required placeholder="e.g. Juan Dela Cruz" />
              </div>

              <div class="form-control-group">
                <label>Position</label>
                <input v-model="form.position" type="text" placeholder="e.g. Procurement Manager" />
              </div>

              <div class="form-control-group">
                <label>Assigned Supervisor / POC</label>
                <select v-model="form.user_id" required class="form-select-dropdown">
                  <option disabled value="">Select Supervisor / User</option>
                  <option v-for="user in users" :key="user.id" :value="user.id">
                    {{ user.name }} ({{ user.role }})
                  </option>
                </select>
              </div>

              <div class="form-control-group">
                <label>Phone / Mobile</label>
                <input v-model="form.contact_number" type="text" required placeholder="0917XXXXXXX" />
              </div>

              <div class="form-control-group full-width">
                <label>Client Address</label>
                <input v-model="form.client_address" type="text" placeholder="e.g. Makati City, Metro Manila" />
              </div>
              
              <div class="form-control-group full-width">
                <label>Business Nature / Style</label>
                <select v-model="form.business_style" required class="form-select-dropdown">
                  <option disabled value="">Select Business Nature</option>
                  <option value="Information Technology & Software Services">Information Technology & Software Services</option>
                  <option value="Freight, Logistics & Forwarding">Freight, Logistics & Forwarding</option>
                  <option value="Manufacturing & Distribution">Manufacturing & Distribution</option>
                  <option value="Professional Services & Consultancy">Professional Services & Consultancy</option>
                  <option value="Construction & Engineering">Construction & Engineering</option>
                  <option value="Healthcare & Pharmaceuticals">Healthcare & Pharmaceuticals</option>
                  <option value="Retail & E-Commerce">Retail & E-Commerce</option>
                  <option value="Others">Others</option>
                </select>
              </div>
            </div>

            <div class="form-submit-row">
              <button type="button" @click="showModal = false" class="btn-cancel-modal">Cancel</button>
              <button type="submit" :disabled="isSubmitting" class="btn-primary-action">
                {{ isSubmitting ? 'Saving...' : 'Create Client' }}
              </button>
            </div>
          </form>
        </div>
      </div>

      <div v-if="showEditModal" class="modal-backdrop">
        <div class="modal-card-container">
          <div class="modal-header-bar">
            <h3>Update Client Profile: <span style="color: #38bdf8;">{{ editForm.client_name }}</span></h3>
            <button @click="showEditModal = false" class="btn-close-modal">&times;</button>
          </div>

          <form @submit.prevent="updateClient" class="modal-body-content">
            <div class="form-grid-columns">
              <div class="form-control-group">
                <label>Client Code</label>
                <input :value="editForm.client_code" type="text" readonly class="bg-gray-50 font-mono font-bold" />
              </div>

              <div class="form-control-group">
                <label>TIN Number</label>
                <input v-model="editForm.tin_number" type="text" placeholder="000-000-000-000" />
              </div>

              <div class="form-control-group full-width">
                <label>Company / Client Name</label>
                <input v-model="editForm.client_name" type="text" required />
              </div>

              <div class="form-control-group">
                <label>Contact Person</label>
                <input v-model="editForm.contact_person" type="text" required />
              </div>

              <div class="form-control-group">
                <label>Position</label>
                <input v-model="editForm.position" type="text" />
              </div>

              <div class="form-control-group">
                <label>Assigned Supervisor / POC</label>
                <select v-model="editForm.user_id" required class="form-select-dropdown">
                  <option disabled value="">Select Supervisor / User</option>
                  <option v-for="user in users" :key="user.id" :value="user.id">
                    {{ user.name }} ({{ user.role }})
                  </option>
                </select>
              </div>

              <div class="form-control-group">
                <label>Phone / Mobile</label>
                <input v-model="editForm.contact_number" type="text" required />
              </div>

              <div class="form-control-group full-width">
                <label>Client Address</label>
                <input v-model="editForm.client_address" type="text" />
              </div>
              
              <div class="form-control-group">
                <label>Business Nature / Style</label>
                <select v-model="editForm.business_style" required class="form-select-dropdown">
                  <option value="Information Technology & Software Services">Information Technology & Software Services</option>
                  <option value="Freight, Logistics & Forwarding">Freight, Logistics & Forwarding</option>
                  <option value="Manufacturing & Distribution">Manufacturing & Distribution</option>
                  <option value="Professional Services & Consultancy">Professional Services & Consultancy</option>
                  <option value="Construction & Engineering">Construction & Engineering</option>
                  <option value="Healthcare & Pharmaceuticals">Healthcare & Pharmaceuticals</option>
                  <option value="Retail & E-Commerce">Retail & E-Commerce</option>
                  <option value="Others">Others</option>
                </select>
              </div>

              <div class="form-control-group">
                <label>Client Status</label>
                <select v-model="editForm.client_status" required class="form-select-dropdown">
                  <option value="Approved">Approved</option>
                  <option value="Pending Approval">Pending Approval</option>
                  <option value="Suspended">Suspended</option>
                </select>
              </div>
            </div>

            <div class="form-submit-row">
              <button type="button" @click="showEditModal = false" class="btn-cancel-modal">Cancel</button>
              <button type="submit" :disabled="isSubmitting" class="btn-primary-action">
                {{ isSubmitting ? 'Saving Changes...' : 'Save Client Updates' }}
              </button>
            </div>
          </form>
        </div>
      </div>

    </div>
  </app-layout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import { useToast } from 'vue-toastification';
import AppLayout from '@/Layouts/AppLayout.vue';

const toast = useToast();
const selectedStatus = ref('All');
const searchQuery = ref('');
const showModal = ref(false);
const showEditModal = ref(false);
const isSubmitting = ref(false);

const clients = ref([]);
const users = ref([]);

const form = ref({ 
  id: null, 
  client_code: '', 
  client_name: '', 
  contact_person: '', 
  position: '',
  user_id: '', 
  contact_number: '', 
  client_address: '',
  tin_number: '', 
  business_style: '' 
});

const editForm = ref({
  id: null,
  client_code: '',
  client_name: '',
  contact_person: '',
  position: '',
  user_id: '',
  contact_number: '',
  client_address: '',
  tin_number: '',
  business_style: '',
  client_status: 'Approved'
});

onMounted(async () => {
  await fetchData();
});

const fetchData = async () => {
  try {
    const [clientRes, userRes] = await Promise.all([
      axios.get('/api/clients'),
      axios.get('/api/users')
    ]);
    clients.value = clientRes.data.data || clientRes.data;
    users.value = userRes.data.data || userRes.data;
  } catch (error) {
    console.error('Error fetching data:', error);
    toast.error('Failed to load data.');
  }
};

const getUserName = (userId) => {
  const found = users.value.find(u => u.id === userId);
  return found ? found.name : '';
};

const activeClientsCount = computed(() => {
  return clients.value.filter(c => (c.client_status || c.status) === 'Approved').length;
});

const pendingClientsCount = computed(() => {
  return clients.value.filter(c => (c.client_status || c.status) === 'Pending Approval').length;
});

const getStatusClass = (status) => {
  switch (status) {
    case 'Approved': return 'pill-success';
    case 'Pending Approval': return 'pill-warning';
    case 'Suspended': return 'pill-danger';
    default: return 'pill-info';
  }
};

const filteredClients = computed(() => {
  return clients.value.filter(c => {
    const currentStatus = c.client_status || c.status;
    const matchesStatus = selectedStatus.value === 'All' || currentStatus === selectedStatus.value;
    
    const clientCode = c.client_code || '';
    const clientName = c.client_name || '';
    const contactPerson = c.contact_person || '';
    const tinNum = c.tin_number || '';
    const supervisorName = c.user ? c.user.name : (getUserName(c.user_id) || '');

    const matchesSearch = clientCode.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
                          clientName.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
                          contactPerson.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
                          supervisorName.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
                          tinNum.includes(searchQuery.value);
    return matchesStatus && matchesSearch;
  });
});

const openAddModal = () => {
  const nextNum = clients.value.length + 1;
  const tempCode = 'CL-' + String(nextNum).padStart(4, '0');

  form.value = { 
    id: null, 
    client_code: tempCode, 
    client_name: '', 
    contact_person: '', 
    position: '',
    user_id: '', 
    contact_number: '', 
    client_address: '',
    tin_number: '', 
    business_style: '' 
  };
  showModal.value = true;
};

const openEditModal = (c) => {
  editForm.value = {
    id: c.id,
    client_code: c.client_code || ('CL-' + String(c.id).padStart(4, '0')),
    client_name: c.client_name,
    contact_person: c.contact_person,
    position: c.position || '',
    user_id: c.user_id || '',
    contact_number: c.contact_number,
    client_address: c.client_address || '',
    tin_number: c.tin_number || '',
    business_style: c.business_style || c.business_nature || '',
    client_status: c.client_status || c.status || 'Approved'
  };
  showEditModal.value = true;
};

const deleteClient = async (id) => {
  if (confirm('Are you sure you want to delete this client?')) {
    try {
      await axios.delete(`/api/clients/${id}`);
      clients.value = clients.value.filter(c => c.id !== id);
      toast.success('Client deleted successfully.');
    } catch (error) {
      console.error('Error deleting client:', error);
      toast.error('Failed to delete client.');
    }
  }
};

const saveClient = async () => {
  isSubmitting.value = true;
  try {
    const response = await axios.post('/api/clients', form.value);
    const created = response.data.data || response.data;
    clients.value.unshift(created);
    toast.success('Client created successfully!');
    showModal.value = false;
    await fetchData();
  } catch (error) {
    console.error('Error saving client:', error.response?.data || error.message);
    toast.error(error.response?.data?.message || 'Failed to save client.');
  } finally {
    isSubmitting.value = false;
  }
};

const updateClient = async () => {
  isSubmitting.value = true;
  try {
    const response = await axios.put(`/api/clients/${editForm.value.id}`, editForm.value);
    const updated = response.data.data || response.data;
    const index = clients.value.findIndex(c => c.id === editForm.value.id);
    if (index !== -1) {
      clients.value[index] = updated;
    }
    toast.success('Client updated successfully!');
    showEditModal.value = false;
    await fetchData();
  } catch (error) {
    console.error('Error updating client:', error.response?.data || error.message);
    toast.error(error.response?.data?.message || 'Failed to update client.');
  } finally {
    isSubmitting.value = false;
  }
};
</script>

<style scoped>
.dashboard-page-wrapper { max-width: 1400px; margin: 0 auto; padding: 24px 32px; box-sizing: border-box; }
.mb-24 { margin-bottom: 24px; } .mb-20 { margin-bottom: 20px; }

.kpi-grid-container { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
.kpi-card-item { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px 24px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 1px 3px rgba(0,0,0,0.02); }
.kpi-info { display: flex; flex-direction: column; gap: 4px; }
.kpi-label { font-size: 11px; font-weight: 700; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px; }
.kpi-number { font-size: 24px; font-weight: 800; color: #0f172a; }
.kpi-icon-box { width: 40px; height: 40px; border-radius: 8px; background: #f8fafc; border: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: center; color: #64748b; }

.analytics-card-panel { background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.02); }
.panel-header-box { background: #0f172a; padding: 20px 28px; display: flex; justify-content: space-between; align-items: center; }
.panel-header-box h3 { color: #ffffff; font-size: 15px; font-weight: 700; text-transform: uppercase; margin: 0; letter-spacing: 0.5px; }
.btn-primary-action { background: #0f172a; color: #ffffff; padding: 10px 20px; border-radius: 8px; font-size: 12px; font-weight: 700; text-transform: uppercase; transition: background 0.2s; border: 1px solid #334155; cursor: pointer; }
.btn-primary-action:hover:not(:disabled) { background: #1e293b; }
.btn-primary-action:disabled { opacity: 0.6; cursor: not-allowed; }
.panel-body-content { padding: 28px; }

.table-controls-row { display: flex; justify-content: space-between; align-items: center; gap: 20px; flex-wrap: wrap; }
.modern-tabs-container { display: flex; gap: 6px; background: #f8fafc; padding: 6px; border-radius: 10px; border: 1px solid #e2e8f0; }
.modern-tab-btn { background: transparent; border: none; padding: 8px 16px; font-size: 12px; font-weight: 700; color: #64748b; border-radius: 6px; cursor: pointer; transition: all 0.2s ease; }
.modern-tab-btn:hover { color: #0f172a; background: #f1f5f9; }
.modern-tab-btn.active { background: #0f172a; color: #ffffff; }

.search-input-box { display: flex; align-items: center; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 14px; width: 320px; height: 40px; }
.search-icon { stroke: #64748b; margin-right: 10px; flex-shrink: 0; }
.search-input-box input { border: none; background: transparent; outline: none; font-size: 13px; color: #1e293b; width: 100%; }

.items-table-frame { border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden; }
.enterprise-data-table { width: 100%; border-collapse: collapse; text-align: left; }
.enterprise-data-table th { background: #f8fafc; padding: 14px 18px; font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; letter-spacing: 0.5px; }
.enterprise-data-table td { padding: 16px 18px; border-bottom: 1px solid #e2e8f0; font-size: 13px; color: #334155; vertical-align: middle; }

.contact-meta { display: flex; flex-direction: column; gap: 2px; }
.main-contact-email { font-size: 13px; color: #1e293b; font-weight: 500; }
.sub-contact-num { color: #64748b; font-size: 11px; }
.font-bold { font-weight: 700; } .font-mono { font-family: monospace; font-size: 12px; }
.text-slate-900 { color: #0f172a; font-size: 13px; } .text-center { text-align: center; }
.text-indigo-700 { color: #4338ca; }
.empty-data-message { text-align: center; color: #94a3b8; padding: 40px !important; font-size: 13px; }

/* Status Pills */
.status-pill { padding: 4px 10px; border-radius: 6px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
.pill-success { background: #d1fae5; color: #059669; }
.pill-warning { background: #fef3c7; color: #d97706; }
.pill-danger { background: #fee2e2; color: #dc2626; }
.pill-info { background: #e0f2fe; color: #0284c7; }

.action-buttons-group { display: flex; justify-content: center; gap: 8px; }
.action-btn { width: 32px; height: 32px; border-radius: 6px; border: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s; background: #ffffff; color: #334155; }
.action-btn:hover { background: #f1f5f9; border-color: #94a3b8; color: #0f172a; }
.view-edit-btn:hover { color: #0284c7; }
.delete-btn:hover { color: #dc2626; }

/* Modal Styles */
.modal-backdrop { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.65); display: flex; align-items: center; justify-content: center; z-index: 1000; backdrop-filter: blur(2px); }
.modal-card-container { background: #ffffff; width: 680px; max-height: 90vh; overflow-y: auto; border-radius: 12px; box-shadow: 0 20px 30px rgba(0,0,0,0.15); }
.modal-header-bar { background: #0f172a; padding: 18px 24px; display: flex; justify-content: space-between; align-items: center; color: #ffffff; position: sticky; top: 0; z-index: 10; }
.modal-header-bar h3 { font-size: 14px; font-weight: 700; text-transform: uppercase; margin: 0; letter-spacing: 0.5px; }
.btn-close-modal { background: none; border: none; color: #ffffff; font-size: 24px; cursor: pointer; }
.modal-body-content { padding: 28px; }

.form-grid-columns { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 24px; }
.form-control-group { display: flex; flex-direction: column; }
.form-control-group.full-width { grid-column: span 2; }
.form-control-group label { display: block; font-size: 11px; font-weight: 700; text-transform: uppercase; color: #64748b; margin-bottom: 6px; letter-spacing: 0.5px; }
.form-control-group input,
.form-select-dropdown { width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px; color: #1e293b; outline: none; transition: border-color 0.2s; box-sizing: border-box; background-color: #ffffff; }
.bg-gray-50 { background-color: #f8fafc; cursor: not-allowed; color: #64748b !important; }
.form-control-group input:focus,
.form-select-dropdown:focus { border-color: #0f172a; box-shadow: 0 0 0 3px rgba(15, 23, 42, 0.1); }

.form-submit-row { display: flex; justify-content: flex-end; gap: 12px; border-top: 1px solid #e2e8f0; padding-top: 20px; margin-top: 0; }
.btn-cancel-modal { background: #f1f5f9; color: #475569; padding: 10px 18px; border-radius: 8px; font-size: 11px; font-weight: 700; border: none; cursor: pointer; text-transform: uppercase; }
.btn-cancel-modal:hover { background: #e2e8f0; }
</style>