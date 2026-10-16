<template>
  <app-layout>
    <div class="dashboard-page-wrapper">

      <!-- HERO HEADER -->
      <div class="welcome-hero-banner mb-24">
        <div class="hero-text-content">
          <div class="hero-title-badge">
            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
            <span>USER MANAGEMENT</span>
          </div>
          <h1>System Users &amp; Directory</h1>
          <p>Manage user accounts, roles, and team assignments across the billing system.</p>
        </div>
        <button @click="openAddModal" class="btn-hero-action">
          <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
          Add New User
        </button>
      </div>

      <!-- KPI SUMMARY CARDS -->
      <div class="kpi-grid-container mb-24">
        <template v-if="isLoading">
          <SkeletonCard v-for="n in 4" :key="n" />
        </template>
        <template v-else>
        <div class="kpi-card-item border-l-4 border-l-slate-700">
          <div class="kpi-info">
            <span class="kpi-label">Total Users</span>
            <span class="kpi-number">{{ users.length }}</span>
            <small class="text-xs text-slate-500 mt-1">All system accounts</small>
          </div>
          <div class="kpi-icon-box bg-slate-100 text-slate-700">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
          </div>
        </div>

        <div class="kpi-card-item border-l-4 border-l-amber-500">
          <div class="kpi-info">
            <span class="kpi-label">Admin Accounts</span>
            <span class="kpi-number text-amber-700">{{ adminUsersCount }}</span>
            <small class="text-xs text-slate-500 mt-1">Elevated privileges</small>
          </div>
          <div class="kpi-icon-box bg-amber-50 text-amber-600">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
          </div>
        </div>

        <div class="kpi-card-item border-l-4 border-l-slate-700">
          <div class="kpi-info">
            <span class="kpi-label">Standard Users</span>
            <span class="kpi-number text-slate-800">{{ standardUsersCount }}</span>
            <small class="text-xs text-slate-500 mt-1">Regular access level</small>
          </div>
          <div class="kpi-icon-box bg-slate-100 text-slate-700">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
          </div>
        </div>

        <div class="kpi-card-item border-l-4 border-l-amber-500">
          <div class="kpi-info">
            <span class="kpi-label">Teams Covered</span>
            <span class="kpi-number text-amber-700">{{ teamsCount }}</span>
            <small class="text-xs text-slate-500 mt-1">Distinct team assignments</small>
          </div>
          <div class="kpi-icon-box bg-amber-50 text-amber-600">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>
          </div>
        </div>
        </template>
      </div>

      <!-- MAIN TABLE PANEL -->
      <div class="analytics-card-panel">
        <div class="panel-header-box flex-between-box">
          <div class="flex items-center gap-2">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" class="text-amber-400"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle></svg>
            <h3>User Directory</h3>
          </div>
          <span class="text-xs font-bold text-amber-400">{{ filteredUsers.length }} / {{ users.length }} Users</span>
        </div>

        <div class="panel-body-content">
          <!-- TOOLBAR -->
          <div class="table-controls-row mb-20">
            <div class="modern-tabs-container">
              <button
                v-for="roleFilter in ['All', 'admin', 'user']"
                :key="roleFilter"
                @click="selectedRole = roleFilter"
                :class="['modern-tab-btn', { active: selectedRole === roleFilter }]"
              >
                {{ roleFilter === 'All' ? 'All Roles' : (roleFilter.charAt(0).toUpperCase() + roleFilter.slice(1)) }}
                <span class="tab-count-badge">
                  {{ roleFilter === 'All' ? users.length : users.filter(u => u.role === roleFilter).length }}
                </span>
              </button>
            </div>

            <div class="search-input-box" style="max-width: 340px;">
              <svg class="search-icon" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
              <input v-model="searchQuery" type="text" placeholder="Search name, email, role, team..." />
            </div>
          </div>

          <!-- TABLE -->
          <div class="items-table-frame">
              <SkeletonTable v-if="isLoading" :rows="6" :cols="7" />
              <template v-else>
            <table class="enterprise-data-table">
              <thead>
                <tr>
                  <th style="width: 110px;">User ID</th>
                  <th style="width: 220px;">User</th>
                  <th>Email Address</th>
                  <th style="width: 110px;">Role</th>
                  <th style="width: 180px;">Assigned Team</th>
                  <th style="width: 120px;">Registered</th>
                  <th style="width: 100px; text-align: center;">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="user in filteredUsers" :key="user.id" class="user-row-item">
                  <td>
                    <span class="user-id-badge">#USR-{{ String(user.id).padStart(4, '0') }}</span>
                  </td>
                  <td>
                    <div class="user-profile-cell">
                      <div class="user-avatar" :class="user.role === 'admin' ? 'avatar-admin' : 'avatar-user'">
                        {{ getUserInitials(user.name) }}
                      </div>
                      <div class="user-profile-text">
                        <span class="user-full-name">{{ user.name }}</span>
                        <small class="user-role-hint">{{ user.role === 'admin' ? 'Administrator' : 'Standard User' }}</small>
                      </div>
                    </div>
                  </td>
                  <td>
                    <div class="email-cell">
                      <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" class="text-slate-400"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                      <span>{{ user.email }}</span>
                    </div>
                  </td>
                  <td>
                    <span :class="['role-pill', user.role === 'admin' ? 'pill-admin' : 'pill-user']">
                      <span class="role-dot"></span>
                      {{ user.role }}
                    </span>
                  </td>
                  <td>
                    <span v-if="user.team" class="team-cell">
                      <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" class="text-slate-400"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>
                      {{ user.team.team_name }}
                    </span>
                    <span v-else class="no-team-badge">No Team</span>
                  </td>
                  <td class="text-xs text-slate-500">{{ formatDate(user.created_at) }}</td>
                  <td>
                    <div class="action-buttons-group">
                      <button @click="openEditModal(user)" class="action-btn edit-btn" title="Edit User">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                      </button>
                      <button @click="promptDeleteUser(user)" class="action-btn delete-btn" title="Delete User">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                      </button>
                    </div>
                  </td>
                </tr>
                <tr v-if="filteredUsers.length === 0 && !isLoading">
                  <td colspan="7">
                    <div class="empty-state-box">
                      <svg viewBox="0 0 24 24" width="36" height="36" fill="none" stroke="currentColor" stroke-width="1.5" class="text-slate-300 mb-2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle></svg>
                      <p class="font-semibold text-slate-600 mb-1">No users found</p>
                      <span class="text-xs text-slate-400">Try adjusting your search or role filter.</span>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
              </template>
          </div>
        </div>
      </div>

      <!-- ADD USER MODAL -->
      <div v-if="showModal" class="modal-backdrop">
        <div class="modal-card-container">
          <div class="modal-header-bar">
            <div class="flex items-center gap-2">
              <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" class="text-amber-400"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
              <h3>Register New System User</h3>
            </div>
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
                  placeholder="user@lendell-local.xyz"
                />
                <span v-if="emailExists" class="password-match-status match-error">This email is already taken!</span>
                <span v-else-if="form.email && isCheckingEmail" class="password-match-status match-info">Checking email availability...</span>
                <span v-else-if="form.email && !emailExists" class="password-match-status match-success">Email address available!</span>
              </div>

              <div class="form-control-group">
                <label>Password</label>
                <input v-model="form.password" type="password" required placeholder="••••••••" />
              </div>

              <div class="form-control-group">
                <label>Confirm Password</label>
                <input v-model="form.password_confirmation" type="password" required placeholder="••••••••" />
                <span v-if="form.password && form.password_confirmation && !isPasswordMatch" class="password-match-status match-error">Passwords do not match!</span>
                <span v-else-if="form.password && form.password_confirmation && isPasswordMatch" class="password-match-status match-success">Passwords match!</span>
              </div>

              <div class="form-control-group">
                <label>Access Role</label>
                <select v-model="form.role" class="form-select-dropdown">
                  <option value="user">Standard User</option>
                  <option value="admin">Administrator</option>
                </select>
              </div>

              <div class="form-control-group">
                <label>Assigned Team</label>
                <select v-model="form.team_id" class="form-select-dropdown">
                  <option :value="null">No Assigned Team</option>
                  <option v-for="team in teams" :key="team.id" :value="team.id">
                    {{ team.team_name }}
                  </option>
                </select>
              </div>
            </div>

            <div class="form-submit-row">
              <button type="button" @click="showModal = false" class="btn-cancel-modal">Cancel</button>
              <button type="submit" :disabled="isSubmitting || emailExists || (form.password && !isPasswordMatch)" class="btn-primary-action">
                {{ isSubmitting ? 'Registering...' : 'Create Account' }}
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- EDIT USER MODAL -->
      <div v-if="showEditModal" class="modal-backdrop">
        <div class="modal-card-container">
          <div class="modal-header-bar">
            <div class="flex items-center gap-2">
              <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" class="text-amber-400"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
              <h3>Edit User Account #{{ editForm.id }}</h3>
            </div>
            <button @click="showEditModal = false" class="btn-close-modal">&times;</button>
          </div>

          <form @submit.prevent="promptUpdateUser" class="modal-body-content">
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
                <label>New Password (Optional)</label>
                <input v-model="editForm.password" type="password" placeholder="Leave blank to keep unchanged" />
              </div>

              <div class="form-control-group">
                <label>Confirm New Password</label>
                <input v-model="editForm.password_confirmation" type="password" placeholder="••••••••" />
                <span v-if="editForm.password && editForm.password_confirmation && !isEditPasswordMatch" class="password-match-status match-error">Passwords do not match!</span>
                <span v-else-if="editForm.password && editForm.password_confirmation && isEditPasswordMatch" class="password-match-status match-success">Passwords match!</span>
              </div>

              <div class="form-control-group">
                <label>Access Role</label>
                <select v-model="editForm.role" class="form-select-dropdown">
                  <option value="user">Standard User</option>
                  <option value="admin">Administrator</option>
                </select>
              </div>

              <div class="form-control-group">
                <label>Assigned Team</label>
                <select v-model="editForm.team_id" class="form-select-dropdown">
                  <option :value="null">No Assigned Team</option>
                  <option v-for="team in teams" :key="team.id" :value="team.id">
                    {{ team.team_name }}
                  </option>
                </select>
              </div>
            </div>

            <div class="form-submit-row">
              <button type="button" @click="showEditModal = false" class="btn-cancel-modal">Cancel</button>
              <button type="submit" :disabled="isSubmitting || (editForm.password && !isEditPasswordMatch)" class="btn-primary-action">
                {{ isSubmitting ? 'Saving...' : 'Update Account' }}
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- DELETE USER CONFIRMATION MODAL -->
      <div v-if="showDeleteModal" class="modal-backdrop" style="z-index: 1100;">
        <div class="confirm-card-container">
          <div class="confirm-icon-box" style="background: #fee2e2;">
            <svg viewBox="0 0 24 24" width="30" height="30" fill="none" stroke="#dc2626" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
          </div>
          <h3>Confirm Delete User</h3>
          <p>Are you sure you want to permanently delete user <strong>{{ selectedUserForDeletion?.name }}</strong>? This action cannot be undone.</p>
          <div class="confirm-buttons-row">
            <button type="button" @click="showDeleteModal = false" class="btn-cancel-modal">Cancel</button>
            <button type="button" @click="executeDeleteUser" :disabled="isSubmitting" class="btn-primary-action bg-red-600 border-red-600 hover:bg-red-700">
              {{ isSubmitting ? 'Deleting...' : 'Yes, Delete User' }}
            </button>
          </div>
        </div>
      </div>

      <!-- EDIT USER CONFIRMATION MODAL -->
      <div v-if="showEditConfirmModal" class="modal-backdrop" style="z-index: 1100;">
        <div class="confirm-card-container">
          <div class="confirm-icon-box" style="background: #fef3c7;">
            <svg viewBox="0 0 24 24" width="30" height="30" fill="none" stroke="#d97706" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
          </div>
          <h3>Confirm User Updates</h3>
          <p>Are you sure you want to save the updates to <strong>{{ editForm.name }}</strong>'s account?</p>
          <div class="confirm-buttons-row">
            <button type="button" @click="showEditConfirmModal = false" class="btn-cancel-modal">Cancel</button>
            <button type="button" @click="executeUpdateUser" :disabled="isSubmitting" class="btn-primary-action bg-emerald-600 border-emerald-600 hover:bg-emerald-700">
              {{ isSubmitting ? 'Saving...' : 'Yes, Save Changes' }}
            </button>
          </div>
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
import SkeletonCard from '@/Components/Skeleton/SkeletonCard.vue';
import SkeletonTable from '@/Components/Skeleton/SkeletonTable.vue';

const toast = useToast();

const users = ref([]);
const teams = ref([]);
const searchQuery = ref('');
const selectedRole = ref('All');
const showModal = ref(false);
const showEditModal = ref(false);
const showDeleteModal = ref(false);
const selectedUserForDeletion = ref(null);
const showEditConfirmModal = ref(false);
const isSubmitting = ref(false);
const isCheckingEmail = ref(false);
const emailExists = ref(false);
const isLoading = ref(true);

const form = ref({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  role: 'user',
  team_id: null
});

const editForm = ref({
  id: null,
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  role: 'user',
  team_id: null
});

const adminUsersCount = computed(() => users.value.filter(u => u.role === 'admin').length);
const standardUsersCount = computed(() => users.value.filter(u => u.role === 'user').length);
const teamsCount = computed(() => {
  const teamIds = new Set(users.value.map(u => u.team_id).filter(Boolean));
  return teamIds.size;
});

const filteredUsers = computed(() => {
  return users.value.filter(user => {
    const matchesRole = selectedRole.value === 'All' || user.role === selectedRole.value;
    const query = searchQuery.value.toLowerCase().trim();
    if (!query) return matchesRole;

    const matchesName = user.name?.toLowerCase().includes(query);
    const matchesEmail = user.email?.toLowerCase().includes(query);
    const matchesRoleText = user.role?.toLowerCase().includes(query);
    const matchesTeam = user.team?.team_name?.toLowerCase().includes(query);

    return matchesRole && (matchesName || matchesEmail || matchesRoleText || matchesTeam);
  });
});

const isPasswordMatch = computed(() => form.value.password === form.value.password_confirmation);
const isEditPasswordMatch = computed(() => editForm.value.password === editForm.value.password_confirmation);

const getUserInitials = (name) => {
  if (!name) return 'US';
  const parts = name.trim().split(' ');
  if (parts.length >= 2) return (parts[0][0] + parts[1][0]).toUpperCase();
  return name.substring(0, 2).toUpperCase();
};

const fetchData = async () => {
  isLoading.value = true;
  try {
    const [usersRes, teamsRes] = await Promise.all([
      axios.get('/api/users'),
      axios.get('/api/teams')
    ]);
    users.value = usersRes.data.data || usersRes.data || [];
    teams.value = teamsRes.data.data || teamsRes.data || [];
  } catch (error) {
    toast.error('Failed to load user data.');
  } finally {
    isLoading.value = false;
  }
};

let emailTimeout = null;
const checkEmailAvailability = () => {
  if (!form.value.email) { emailExists.value = false; return; }
  clearTimeout(emailTimeout);
  isCheckingEmail.value = true;
  emailTimeout = setTimeout(async () => {
    try {
      const res = await axios.post('/api/users/check-email', { email: form.value.email });
      emailExists.value = res.data.exists;
    } catch (error) {
      console.error(error);
    } finally {
      isCheckingEmail.value = false;
    }
  }, 400);
};

const openAddModal = () => {
  form.value = { name: '', email: '', password: '', password_confirmation: '', role: 'user', team_id: null };
  emailExists.value = false;
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
    team_id: user.team_id || null
  };
  showEditModal.value = true;
};

const promptDeleteUser = (user) => {
  selectedUserForDeletion.value = user;
  showDeleteModal.value = true;
};

const executeDeleteUser = async () => {
  if (!selectedUserForDeletion.value) return;
  isSubmitting.value = true;
  try {
    const id = selectedUserForDeletion.value.id;
    await axios.delete(`/api/users/${id}`);
    users.value = users.value.filter(u => u.id !== id);
    toast.success('User deleted successfully.');
    showDeleteModal.value = false;
  } catch (error) {
    toast.error('Failed to delete user.');
  } finally {
    isSubmitting.value = false;
  }
};

const saveUser = async () => {
  if (emailExists.value) { toast.error('Email is already taken!'); return; }
  if (!isPasswordMatch.value) { toast.error('Passwords do not match!'); return; }
  isSubmitting.value = true;
  try {
    await axios.post('/api/users', form.value);
    toast.success('User created successfully!');
    showModal.value = false;
    await fetchData();
  } catch (error) {
    toast.error(error.response?.data?.message || 'Failed to create user.');
  } finally {
    isSubmitting.value = false;
  }
};

const promptUpdateUser = () => {
  if (!isEditPasswordMatch.value) { toast.error('Passwords do not match!'); return; }
  showEditConfirmModal.value = true;
};

const executeUpdateUser = async () => {
  isSubmitting.value = true;
  try {
    const payload = { ...editForm.value };
    if (!payload.password) { delete payload.password; delete payload.password_confirmation; }
    await axios.put(`/api/users/${editForm.value.id}`, payload);
    toast.success('User updated successfully!');
    showEditConfirmModal.value = false;
    showEditModal.value = false;
    await fetchData();
  } catch (error) {
    toast.error(error.response?.data?.message || 'Failed to update user.');
  } finally {
    isSubmitting.value = false;
  }
};

const formatDate = (dateStr) => (dateStr ? new Date(dateStr).toISOString().split('T')[0] : '');

onMounted(fetchData);
</script>

<style scoped>
.dashboard-page-wrapper { max-width: 1400px; margin: 0 auto; padding: 24px 32px; box-sizing: border-box; }
.mb-24 { margin-bottom: 24px; } .mb-20 { margin-bottom: 20px; }

/* HERO */
.welcome-hero-banner { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px 28px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.04); display: flex; justify-content: space-between; align-items: center; }
.hero-title-badge { display: inline-flex; align-items: center; gap: 6px; font-size: 11px; font-weight: 800; color: #d97706; background: #fffbeb; border: 1px solid #fde68a; padding: 4px 10px; border-radius: 20px; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; }
.hero-text-content h1 { font-size: 22px; font-weight: 800; color: #0f172a; margin: 0 0 4px 0; letter-spacing: -0.5px; }
.hero-text-content p { font-size: 13px; color: #64748b; margin: 0; }
.btn-hero-action { display: inline-flex; align-items: center; gap: 8px; background: #0f172a; color: #ffffff; padding: 12px 22px; border-radius: 8px; font-size: 13px; font-weight: 700; border: none; cursor: pointer; transition: all 0.2s ease; flex-shrink: 0; }
.btn-hero-action:hover { background: #1e293b; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(15,23,42,0.15); }

/* KPI */
.kpi-grid-container { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; }
.kpi-card-item { background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 18px 20px; display: flex; justify-content: space-between; align-items: flex-start; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
.kpi-info { display: flex; flex-direction: column; }
.kpi-label { font-size: 11px; font-weight: 700; text-transform: uppercase; color: #64748b; margin-bottom: 4px; letter-spacing: 0.5px; }
.kpi-number { font-size: 24px; font-weight: 800; color: #0f172a; }
.kpi-icon-box { width: 42px; height: 42px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }

/* PANEL */
.analytics-card-panel { background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.04); }
.panel-header-box { background: #0f172a; padding: 16px 24px; display: flex; justify-content: space-between; align-items: center; }
.panel-header-box h3 { color: #ffffff; font-size: 13px; font-weight: 700; text-transform: uppercase; margin: 0; letter-spacing: 0.3px; }
.panel-body-content { padding: 24px; }

/* TOOLBAR */
.table-controls-row { display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap; }
.modern-tabs-container { display: flex; gap: 4px; background: #f8fafc; padding: 4px; border-radius: 8px; border: 1px solid #e2e8f0; }
.modern-tab-btn { display: inline-flex; align-items: center; gap: 6px; background: transparent; border: none; padding: 7px 14px; font-size: 12px; font-weight: 700; color: #64748b; border-radius: 6px; cursor: pointer; transition: all 0.2s ease; }
.modern-tab-btn:hover { color: #0f172a; background: #f1f5f9; }
.modern-tab-btn.active { background: #0f172a; color: #ffffff; }
.tab-count-badge { background: rgba(255,255,255,0.2); color: inherit; font-size: 10px; font-weight: 800; padding: 1px 6px; border-radius: 10px; }
.modern-tab-btn:not(.active) .tab-count-badge { background: #e2e8f0; color: #475569; }

/* SEARCH */
.search-input-box { display: flex; align-items: center; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 14px; width: 100%; height: 40px; transition: all 0.2s; }
.search-input-box:focus-within { border-color: #f59e0b; box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.15); }
.search-icon { stroke: #94a3b8; margin-right: 10px; flex-shrink: 0; }
.search-input-box input { border: none; background: transparent; outline: none; font-size: 13px; color: #1e293b; width: 100%; }

/* TABLE */
.items-table-frame { border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; }
.enterprise-data-table { width: 100%; border-collapse: collapse; text-align: left; }
.enterprise-data-table th { background: #f8fafc; padding: 14px 18px; font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; letter-spacing: 0.4px; }
.enterprise-data-table td { padding: 16px 18px; border-bottom: 1px solid #f1f5f9; font-size: 13px; color: #334155; vertical-align: middle; }
.user-row-item:last-child td { border-bottom: none; }
.user-row-item:hover { background-color: #fafafa; }

/* USER CELL */
.user-profile-cell { display: flex; align-items: center; gap: 12px; }
.user-avatar { width: 34px; height: 34px; border-radius: 50%; font-size: 11px; font-weight: 800; display: flex; align-items: center; justify-content: center; flex-shrink: 0; border: 1px solid; }
.avatar-admin { background: #fffbeb; color: #92400e; border-color: #fde68a; }
.avatar-user { background: #f1f5f9; color: #334155; border-color: #cbd5e1; }
.user-profile-text { display: flex; flex-direction: column; }
.user-full-name { font-weight: 700; color: #0f172a; font-size: 13px; }
.user-role-hint { font-size: 11px; color: #94a3b8; }
.user-id-badge { background: #f8fafc; border: 1px solid #e2e8f0; color: #475569; padding: 3px 8px; border-radius: 6px; font-family: monospace; font-size: 11px; font-weight: 700; }
.email-cell { display: flex; align-items: center; gap: 7px; font-size: 13px; color: #334155; }
.role-pill { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.3px; }
.role-dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
.pill-admin { background: #fffbeb; color: #b45309; border: 1px solid #fde68a; }
.pill-user { background: #f8fafc; color: #475569; border: 1px solid #e2e8f0; }
.team-cell { display: inline-flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 600; color: #0f172a; }
.no-team-badge { font-size: 12px; color: #94a3b8; font-style: italic; }

/* ACTIONS */
.action-buttons-group { display: flex; justify-content: center; gap: 6px; }
.action-btn { width: 32px; height: 32px; border-radius: 8px; border: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s; background: #ffffff; color: #64748b; }
.action-btn:hover { background: #f1f5f9; border-color: #cbd5e1; color: #0f172a; }
.edit-btn:hover { color: #d97706; border-color: #fde68a; background: #fffbeb; }
.delete-btn:hover { color: #dc2626; border-color: #fecaca; background: #fef2f2; }

/* EMPTY */
.empty-state-box { padding: 48px 20px; text-align: center; display: flex; flex-direction: column; align-items: center; }
.font-semibold { font-weight: 600; }

/* MODAL */
.modal-backdrop { position: fixed; inset: 0; background: rgba(15,23,42,0.6); display: flex; align-items: center; justify-content: center; z-index: 1000; backdrop-filter: blur(3px); }
.modal-card-container { background: #ffffff; width: 680px; max-height: 90vh; overflow-y: auto; border-radius: 12px; box-shadow: 0 20px 35px rgba(0,0,0,0.15); border: 1px solid #e2e8f0; }
.modal-header-bar { background: #0f172a; padding: 18px 24px; display: flex; justify-content: space-between; align-items: center; color: #ffffff; position: sticky; top: 0; z-index: 10; }
.modal-header-bar h3 { font-size: 13px; font-weight: 700; text-transform: uppercase; margin: 0; letter-spacing: 0.4px; color: #ffffff; }
.btn-close-modal { background: none; border: none; color: #94a3b8; font-size: 24px; cursor: pointer; line-height: 1; transition: color 0.2s; }
.btn-close-modal:hover { color: #ffffff; }
.modal-body-content { padding: 24px; }
.form-grid-columns { display: grid; grid-template-columns: repeat(2, 1fr); gap: 18px; margin-bottom: 20px; }
.form-control-group { display: flex; flex-direction: column; }
.form-control-group.full-width { grid-column: span 2; }
.form-control-group label { display: block; font-size: 11px; font-weight: 700; text-transform: uppercase; color: #64748b; margin-bottom: 6px; letter-spacing: 0.5px; }
.form-control-group input, .form-select-dropdown { width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px; color: #1e293b; outline: none; transition: all 0.2s; box-sizing: border-box; background: #ffffff; }
.form-control-group input:focus, .form-select-dropdown:focus { border-color: #f59e0b; box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.15); }
.password-match-status { margin-top: 6px; font-size: 11px; font-weight: 700; }
.match-success { color: #059669; } .match-error { color: #dc2626; } .match-info { color: #d97706; }
.form-submit-row { display: flex; justify-content: flex-end; gap: 12px; border-top: 1px solid #e2e8f0; padding-top: 18px; margin-top: 4px; }
.btn-cancel-modal { background: #f8fafc; color: #475569; padding: 10px 20px; border-radius: 8px; font-size: 12px; font-weight: 700; border: 1px solid #cbd5e1; cursor: pointer; text-transform: uppercase; transition: all 0.2s; }
.btn-cancel-modal:hover { background: #f1f5f9; color: #0f172a; }
.btn-primary-action { background: #0f172a; color: #ffffff; padding: 10px 22px; border-radius: 8px; font-size: 12px; font-weight: 700; text-transform: uppercase; border: none; cursor: pointer; transition: all 0.2s; }
.btn-primary-action:hover:not(:disabled) { background: #1e293b; }
.btn-primary-action:disabled { opacity: 0.5; cursor: not-allowed; }

/* UTIL */
.flex { display: flex; } .items-center { align-items: center; } .gap-2 { gap: 8px; }
.text-xs { font-size: 11px; } .text-slate-500 { color: #64748b; } .text-slate-300 { color: #cbd5e1; }
.font-bold { font-weight: 700; } .mt-1 { margin-top: 4px; }
.border-l-4 { border-left-width: 4px; border-left-style: solid; }
.border-l-amber-500 { border-left-color: #f59e0b; }
.border-l-slate-700 { border-left-color: #334155; }
.bg-amber-50 { background: #fffbeb; } .text-amber-600 { color: #d97706; } .text-amber-700 { color: #b45309; } .text-amber-400 { color: #fbbf24; }
.text-slate-400 { color: #94a3b8; }
.flex-between-box { display: flex; justify-content: space-between; align-items: center; }

/* CONFIRM MODALS */
.confirm-card-container { background: #ffffff; width: 440px; border-radius: 12px; padding: 32px; text-align: center; box-shadow: 0 20px 30px rgba(0,0,0,0.2); }
.confirm-icon-box { width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px auto; }
.confirm-card-container h3 { font-size: 16px; font-weight: 800; color: #0f172a; margin: 0 0 8px 0; text-transform: uppercase; }
.confirm-card-container p { font-size: 13px; color: #64748b; margin: 0 0 16px 0; line-height: 1.5; text-align: center; }
.confirm-buttons-row { display: flex; justify-content: center; gap: 12px; margin-top: 20px; }
.bg-red-600 { background: #dc2626 !important; border-color: #dc2626 !important; }
.bg-emerald-600 { background: #059669 !important; border-color: #059669 !important; }
</style>