<template>
  <app-layout>
    <div class="dashboard-page-wrapper">
      
      <div class="kpi-grid-container mb-24">
        <div class="kpi-card-item">
          <div class="kpi-info">
            <span class="kpi-label">Admin Accounts</span>
            <span class="kpi-number">{{ adminUsersCount }}</span>
          </div>
          <div class="kpi-icon-box">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
          </div>
        </div>
        <div class="kpi-card-item">
          <div class="kpi-info">
            <span class="kpi-label">Standard Users</span>
            <span class="kpi-number">{{ standardUsersCount }}</span>
          </div>
          <div class="kpi-icon-box">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
          </div>
        </div>
        <div class="kpi-card-item">
          <div class="kpi-info">
            <span class="kpi-label">Total System Users</span>
            <span class="kpi-number">{{ users.length }}</span>
          </div>
          <div class="kpi-icon-box">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>
          </div>
        </div>
      </div>

      <div class="analytics-card-panel">
        <div class="panel-header-box">
          <h3>Users Directory</h3>
          <button @click="openAddModal" class="btn-primary-action">+ Add New User</button>
        </div>

        <div class="panel-body-content">
          <div class="table-controls-row mb-20">
            <div class="modern-tabs-container">
              <button 
                v-for="roleFilter in ['All', 'admin', 'user']" 
                :key="roleFilter"
                @click="selectedRole = roleFilter"
                :class="['modern-tab-btn', { active: selectedRole === roleFilter }]"
              >
                {{ roleFilter === 'All' ? 'All Roles' : (roleFilter.charAt(0).toUpperCase() + roleFilter.slice(1)) }}
              </button>
            </div>

            <div class="search-input-box">
              <svg class="search-icon" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
              <input v-model="searchQuery" type="text" placeholder="Search name, email, role, team..." />
            </div>
          </div>

          <div class="items-table-frame">
            <table class="enterprise-data-table">
              <thead>
                <tr>
                  <th>User ID</th>
                  <th>Full Name</th>
                  <th>Email Address</th>
                  <th>Role</th>
                  <th>Assigned Team</th>
                  <th>Registered Date</th>
                  <th style="width: 120px; text-align: center;">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="user in filteredUsers" :key="user.id">
                  <td class="font-mono font-bold text-slate-700">#USR-{{ String(user.id).padStart(4, '0') }}</td>
                  <td class="font-bold text-slate-900">{{ user.name }}</td>
                  <td>
                    <div class="contact-meta">
                      <span class="main-contact-email">{{ user.email }}</span>
                    </div>
                  </td>
                  <td>
                    <span :class="['status-pill', user.role === 'admin' ? 'pill-danger' : 'pill-success']">
                      {{ user.role }}
                    </span>
                  </td>
                  <td>
                    <span class="font-bold text-slate-700">{{ user.team ? user.team.team_name : 'No Team Assigned' }}</span>
                  </td>
                  <td>{{ formatDate(user.created_at) }}</td>
                  <td class="text-center">
                    <div class="action-buttons-group">
                      <button @click="openEditModal(user)" class="action-btn edit-btn" title="View & Edit User">
                        <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                      </button>
                      <button @click="deleteUser(user.id)" class="action-btn delete-btn" title="Delete User">
                        <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                      </button>
                    </div>
                  </td>
                </tr>
                <tr v-if="filteredUsers.length === 0">
                  <td colspan="7" class="empty-data-message">No matching user records found.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div v-if="showModal" class="modal-backdrop">
        <div class="modal-card-container">
          <div class="modal-header-bar">
            <h3>Register New System User</h3>
            <button @click="showModal = false" class="btn-close-modal">&times;</button>
          </div>

          <form @submit.prevent="saveUser" class="modal-body-content">
            <div class="form-grid-columns">
              <div class="form-control-group full-width">
                <label>Full Name</label>
                <input v-model="form.name" type="text" required placeholder="e.g. Juan Dela Cruz" />
              </div>

              <div class="form-control-group full-width">
                <label>Email Address</label>
                <input 
                  v-model="form.email" 
                  @input="checkEmailAvailability" 
                  type="email" 
                  required 
                  placeholder="admin@billingpro.com" 
                />
                <div v-if="form.email" class="password-match-status">
                  <span v-if="isCheckingEmail" class="match-info">Checking email database...</span>
                  <span v-else-if="emailExists" class="match-error">✕ Email address is already taken / existing</span>
                  <span v-else class="match-success">✓ Email address is available</span>
                </div>
              </div>

              <div class="form-control-group">
                <label>Password</label>
                <input v-model="form.password" type="password" required placeholder="••••••••" />
              </div>

              <div class="form-control-group">
                <label>Confirm Password</label>
                <input v-model="form.password_confirmation" type="password" required placeholder="••••••••" />
                <div v-if="form.password || form.password_confirmation" class="password-match-status">
                  <span v-if="isPasswordMatch" class="match-success">✓ Passwords match</span>
                  <span v-else class="match-error">✕ Passwords do not match</span>
                </div>
              </div>

              <div class="form-control-group">
                <label>System Role</label>
                <select v-model="form.role" required class="form-select-dropdown">
                  <option value="user">User</option>
                  <option value="admin">Admin</option>
                </select>
              </div>

              <div class="form-control-group">
                <label>Assigned Team</label>
                <select v-model="form.team_id" class="form-select-dropdown">
                  <option value="">-- No Team Assigned --</option>
                  <option v-for="team in teams" :key="team.id" :value="team.id">{{ team.team_name }}</option>
                </select>
              </div>
            </div>

            <div class="form-submit-row">
              <button type="button" @click="showModal = false" class="btn-cancel-modal">Cancel</button>
              <button type="submit" :disabled="isSubmitting || emailExists || !isPasswordMatch" class="btn-primary-action">
                {{ isSubmitting ? 'Saving...' : 'Create User' }}
              </button>
            </div>
          </form>
        </div>
      </div>

      <div v-if="showEditModal" class="modal-backdrop">
        <div class="modal-card-container">
          <div class="modal-header-bar">
            <h3>Update User: <span style="color: #38bdf8;">{{ editForm.name }}</span></h3>
            <button @click="showEditModal = false" class="btn-close-modal">&times;</button>
          </div>

          <form @submit.prevent="updateUser" class="modal-body-content">
            <div class="form-grid-columns">
              <div class="form-control-group full-width">
                <label>Full Name</label>
                <input v-model="editForm.name" type="text" required />
              </div>

              <div class="form-control-group full-width">
                <label>Email Address</label>
                <input v-model="editForm.email" type="email" required />
              </div>

              <div class="form-control-group">
                <label>New Password (Leave blank to keep current)</label>
                <input v-model="editForm.password" type="password" placeholder="••••••••" />
              </div>

              <div class="form-control-group">
                <label>Confirm New Password</label>
                <input v-model="editForm.password_confirmation" type="password" placeholder="••••••••" />
                <div v-if="editForm.password || editForm.password_confirmation" class="password-match-status">
                  <span v-if="isEditPasswordMatch" class="match-success">✓ Passwords match</span>
                  <span v-else class="match-error">✕ Passwords do not match</span>
                </div>
              </div>

              <div class="form-control-group">
                <label>System Role</label>
                <select v-model="editForm.role" required class="form-select-dropdown">
                  <option value="user">User</option>
                  <option value="admin">Admin</option>
                </select>
              </div>

              <div class="form-control-group">
                <label>Assigned Team</label>
                <select v-model="editForm.team_id" class="form-select-dropdown">
                  <option value="">-- No Team Assigned --</option>
                  <option v-for="team in teams" :key="team.id" :value="team.id">{{ team.team_name }}</option>
                </select>
              </div>
            </div>

            <div class="form-submit-row">
              <button type="button" @click="showEditModal = false" class="btn-cancel-modal">Cancel</button>
              <button type="submit" :disabled="isSubmitting || !isEditPasswordMatch" class="btn-primary-action">
                {{ isSubmitting ? 'Saving Changes...' : 'Save User Updates' }}
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
const users = ref([]);
const teams = ref([]);
const selectedRole = ref('All');
const searchQuery = ref('');
const showModal = ref(false);
const showEditModal = ref(false);
const isSubmitting = ref(false);

const emailExists = ref(false);
const isCheckingEmail = ref(false);
let emailCheckTimeout = null;

const form = ref({
  id: null,
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  role: 'user',
  team_id: ''
});

const editForm = ref({
  id: null,
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  role: 'user',
  team_id: ''
});

const isPasswordMatch = computed(() => {
  return form.value.password === form.value.password_confirmation;
});

const isEditPasswordMatch = computed(() => {
  if (!editForm.value.password && !editForm.value.password_confirmation) return true;
  return editForm.value.password === editForm.value.password_confirmation;
});

const checkEmailAvailability = () => {
  emailExists.value = false;
  if (!form.value.email || !form.value.email.includes('@')) return;

  isCheckingEmail.value = true;
  clearTimeout(emailCheckTimeout);

  emailCheckTimeout = setTimeout(async () => {
    try {
      const res = await axios.get('/api/users');
      const allUsers = res.data.data || res.data;
      const found = allUsers.find(u => u.email.toLowerCase() === form.value.email.toLowerCase());
      emailExists.value = !!found;
    } catch (err) {
      console.error('Error checking email:', err);
    } finally {
      isCheckingEmail.value = false;
    }
  }, 400);
};

onMounted(async () => {
  await fetchData();
});

const fetchData = async () => {
  try {
    const [userRes, teamRes] = await Promise.all([
      axios.get('/api/users'),
      axios.get('/api/teams')
    ]);
    users.value = userRes.data.data || userRes.data;
    teams.value = teamRes.data.data || teamRes.data;
  } catch (error) {
    console.error('Error fetching data:', error);
    toast.error('Failed to load users data.');
  }
};

const adminUsersCount = computed(() => users.value.filter(u => u.role === 'admin').length);
const standardUsersCount = computed(() => users.value.filter(u => u.role === 'user').length);

const filteredUsers = computed(() => {
  return users.value.filter(u => {
    const matchesRole = selectedRole.value === 'All' || u.role === selectedRole.value;
    const name = u.name || '';
    const email = u.email || '';
    const role = u.role || '';
    const teamName = u.team ? u.team.team_name : '';

    const query = searchQuery.value.toLowerCase();
    const matchesSearch = name.toLowerCase().includes(query) ||
                          email.toLowerCase().includes(query) ||
                          role.toLowerCase().includes(query) ||
                          teamName.toLowerCase().includes(query);

    return matchesRole && matchesSearch;
  });
});

const openAddModal = () => {
  emailExists.value = false;
  form.value = {
    id: null,
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'user',
    team_id: ''
  };
  showModal.value = true;
};

const openEditModal = (user) => {
  editForm.value = {
    id: user.id,
    name: user.name,
    email: user.email,
    password: '',
    password_confirmation: '',
    role: user.role,
    team_id: user.team_id || ''
  };
  showEditModal.value = true;
};

const deleteUser = async (id) => {
  if (confirm('Are you sure you want to delete this user?')) {
    try {
      await axios.delete(`/api/users/${id}`);
      users.value = users.value.filter(u => u.id !== id);
      toast.success('User deleted successfully.');
    } catch (error) {
      console.error('Error deleting user:', error);
      toast.error('Failed to delete user.');
    }
  }
};

const saveUser = async () => {
  if (emailExists.value) {
    toast.error('Email address is already in use by another account!');
    return;
  }
  if (!isPasswordMatch.value) {
    toast.error('Passwords do not match!');
    return;
  }

  isSubmitting.value = true;
  try {
    await axios.post('/api/users', form.value);
    toast.success('User created successfully!');
    showModal.value = false;
    await fetchData();
  } catch (error) {
    console.error('Error saving user:', error.response?.data || error.message);
    toast.error(error.response?.data?.message || 'Failed to save user.');
  } finally {
    isSubmitting.value = false;
  }
};

const updateUser = async () => {
  if (!isEditPasswordMatch.value) {
    toast.error('Passwords do not match!');
    return;
  }

  isSubmitting.value = true;
  try {
    const payload = { ...editForm.value };
    if (!payload.password) {
      delete payload.password;
      delete payload.password_confirmation;
    }

    await axios.put(`/api/users/${editForm.value.id}`, payload);
    toast.success('User updated successfully!');
    showEditModal.value = false;
    await fetchData();
  } catch (error) {
    console.error('Error updating user:', error.response?.data || error.message);
    toast.error(error.response?.data?.message || 'Failed to update user.');
  } finally {
    isSubmitting.value = false;
  }
};

const formatDate = (dateStr) => (dateStr ? new Date(dateStr).toISOString().split('T')[0] : '');
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
.font-bold { font-weight: 700; } .font-mono { font-family: monospace; font-size: 12px; }
.text-slate-900 { color: #0f172a; font-size: 13px; } .text-center { text-align: center; }
.empty-data-message { text-align: center; color: #94a3b8; padding: 40px !important; font-size: 13px; }

.status-pill { padding: 4px 10px; border-radius: 6px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
.pill-success { background: #d1fae5; color: #059669; }
.pill-danger { background: #fee2e2; color: #dc2626; }

.action-buttons-group { display: flex; justify-content: center; gap: 8px; }
.action-btn { width: 32px; height: 32px; border-radius: 6px; border: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s; background: #ffffff; color: #334155; }
.action-btn:hover { background: #f1f5f9; border-color: #94a3b8; color: #0f172a; }
.edit-btn:hover { color: #2563eb; }
.delete-btn:hover { color: #dc2626; }

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
.form-control-group input:focus,
.form-select-dropdown:focus { border-color: #0f172a; box-shadow: 0 0 0 3px rgba(15, 23, 42, 0.1); }

.password-match-status { margin-top: 6px; font-size: 11px; font-weight: 700; }
.match-success { color: #059669; }
.match-error { color: #dc2626; }
.match-info { color: #0284c7; }

.form-submit-row { display: flex; justify-content: flex-end; gap: 12px; border-top: 1px solid #e2e8f0; padding-top: 20px; }
.btn-cancel-modal { background: #f1f5f9; color: #475569; padding: 10px 18px; border-radius: 8px; font-size: 11px; font-weight: 700; border: none; cursor: pointer; text-transform: uppercase; }
.btn-cancel-modal:hover { background: #e2e8f0; }
</style>