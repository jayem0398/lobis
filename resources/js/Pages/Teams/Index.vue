<template>
  <app-layout>
    <div class="dashboard-page-wrapper">
      
      <div class="kpi-grid-container mb-24">
        <div class="kpi-card-item">
          <div class="kpi-info">
            <span class="kpi-label">Active Teams</span>
            <span class="kpi-number">{{ teams.length }}</span>
          </div>
          <div class="kpi-icon-box">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
          </div>
        </div>
        <div class="kpi-card-item">
          <div class="kpi-info">
            <span class="kpi-label">Total Assigned Members</span>
            <span class="kpi-number">{{ totalMembersCount }}</span>
          </div>
          <div class="kpi-icon-box">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
          </div>
        </div>
        <div class="kpi-card-item">
          <div class="kpi-info">
            <span class="kpi-label">Total Monthly Quota</span>
            <span class="kpi-number">₱{{ formatCurrency(totalQuotaSum) }}</span>
          </div>
          <div class="kpi-icon-box">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>
          </div>
        </div>
      </div>

      <div class="analytics-card-panel">
        <div class="panel-header-box">
          <h3>Team Management Directory</h3>
          <button @click="openAddModal" class="btn-primary-action">+ Add New Team</button>
        </div>

        <div class="panel-body-content">
          <div class="table-controls-row mb-20">
            <div class="modern-tabs-container">
              <span class="active-tab-indicator">All Teams Directory</span>
            </div>

            <div class="search-input-box">
              <svg class="search-icon" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
              <input v-model="searchQuery" type="text" placeholder="Search team name..." />
            </div>
          </div>

          <div class="items-table-frame">
            <table class="enterprise-data-table">
              <thead>
                <tr>
                  <th>Team ID</th>
                  <th>Team Name</th>
                  <th>Monthly Target Quota</th>
                  <th>Total Members</th>
                  <th>Created Date</th>
                  <th style="width: 120px; text-align: center;">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="team in filteredTeams" :key="team.id">
                  <td class="font-mono font-bold text-slate-700">#TEAM-{{ String(team.id).padStart(4, '0') }}</td>
                  <td class="font-bold text-slate-900">{{ team.team_name }}</td>
                  <td class="font-bold">₱{{ formatCurrency(team.quota) }}</td>
                  <td>
                    <span class="status-pill pill-info">{{ team.users_count || 0 }} Members</span>
                  </td>
                  <td>{{ formatDate(team.created_at) }}</td>
                  <td class="text-center">
                    <div class="action-buttons-group">
                      <button @click="openEditModal(team)" class="action-btn edit-btn" title="View & Edit Team">
                        <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                      </button>
                      <button @click="deleteTeam(team.id)" class="action-btn delete-btn" title="Delete Team">
                        <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                      </button>
                    </div>
                  </td>
                </tr>
                <tr v-if="filteredTeams.length === 0">
                  <td colspan="6" class="empty-data-message">No matching team records found.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div v-if="showModal" class="modal-backdrop">
        <div class="modal-card-container">
          <div class="modal-header-bar">
            <h3>Create New Team</h3>
            <button @click="showModal = false" class="btn-close-modal">&times;</button>
          </div>

          <form @submit.prevent="saveTeam" class="modal-body-content">
            <div class="form-grid-columns">
              <div class="form-control-group full-width">
                <label>Team Name</label>
                <input v-model="form.team_name" type="text" required placeholder="e.g. Team Jeremiah" />
              </div>
              <div class="form-control-group full-width">
                <label>Monthly Target Quota (₱)</label>
                <input v-model="form.quota" type="number" min="0" step="0.01" required placeholder="0.00" />
              </div>
            </div>

            <div class="form-submit-row">
              <button type="button" @click="showModal = false" class="btn-cancel-modal">Cancel</button>
              <button type="submit" :disabled="isSubmitting" class="btn-primary-action">
                {{ isSubmitting ? 'Saving...' : 'Create Team' }}
              </button>
            </div>
          </form>
        </div>
      </div>

      <div v-if="showEditModal" class="modal-backdrop">
        <div class="modal-card-container">
          <div class="modal-header-bar">
            <h3>Update Team: <span style="color: #38bdf8;">{{ editForm.team_name }}</span></h3>
            <button @click="showEditModal = false" class="btn-close-modal">&times;</button>
          </div>

          <form @submit.prevent="updateTeam" class="modal-body-content">
            <div class="form-grid-columns">
              <div class="form-control-group full-width">
                <label>Team Name</label>
                <input v-model="editForm.team_name" type="text" required />
              </div>
              <div class="form-control-group full-width">
                <label>Monthly Target Quota (₱)</label>
                <input v-model="editForm.quota" type="number" min="0" step="0.01" required />
              </div>
            </div>

            <div class="form-submit-row">
              <button type="button" @click="showEditModal = false" class="btn-cancel-modal">Cancel</button>
              <button type="submit" :disabled="isSubmitting" class="btn-primary-action">
                {{ isSubmitting ? 'Saving Changes...' : 'Save Team Updates' }}
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
const teams = ref([]);
const searchQuery = ref('');
const showModal = ref(false);
const showEditModal = ref(false);
const isSubmitting = ref(false);

const form = ref({
  id: null,
  team_name: '',
  quota: 0
});

const editForm = ref({
  id: null,
  team_name: '',
  quota: 0
});

onMounted(async () => {
  await fetchTeams();
});

const fetchTeams = async () => {
  try {
    const res = await axios.get('/api/teams');
    teams.value = res.data.data || res.data;
  } catch (error) {
    console.error('Error fetching teams:', error);
    toast.error('Failed to load teams data.');
  }
};

const totalMembersCount = computed(() => {
  return teams.value.reduce((acc, t) => acc + (Number(t.users_count) || 0), 0);
});

const totalQuotaSum = computed(() => {
  return teams.value.reduce((acc, t) => acc + (Number(t.quota) || 0), 0);
});

const filteredTeams = computed(() => {
  return teams.value.filter(t => {
    const name = t.team_name || '';
    return name.toLowerCase().includes(searchQuery.value.toLowerCase());
  });
});

const openAddModal = () => {
  form.value = {
    id: null,
    team_name: '',
    quota: 0
  };
  showModal.value = true;
};

const openEditModal = (team) => {
  editForm.value = {
    id: team.id,
    team_name: team.team_name,
    quota: team.quota
  };
  showEditModal.value = true;
};

const deleteTeam = async (id) => {
  if (confirm('Are you sure you want to delete this team?')) {
    try {
      await axios.delete(`/api/teams/${id}`);
      teams.value = teams.value.filter(t => t.id !== id);
      toast.success('Team deleted successfully.');
    } catch (error) {
      console.error('Error deleting team:', error);
      toast.error('Failed to delete team.');
    }
  }
};

const saveTeam = async () => {
  isSubmitting.value = true;
  try {
    await axios.post('/api/teams', form.value);
    toast.success('Team created successfully!');
    showModal.value = false;
    await fetchTeams();
  } catch (error) {
    console.error('Error saving team:', error.response?.data || error.message);
    toast.error(error.response?.data?.message || 'Failed to save team.');
  } finally {
    isSubmitting.value = false;
  }
};

const updateTeam = async () => {
  isSubmitting.value = true;
  try {
    await axios.put(`/api/teams/${editForm.value.id}`, editForm.value);
    toast.success('Team updated successfully!');
    showEditModal.value = false;
    await fetchTeams();
  } catch (error) {
    console.error('Error updating team:', error.response?.data || error.message);
    toast.error(error.response?.data?.message || 'Failed to update team.');
  } finally {
    isSubmitting.value = false;
  }
};

const formatCurrency = (val) => Number(val || 0).toLocaleString('en-US', { minimumFractionDigits: 2 });
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
.modern-tabs-container { display: flex; align-items: center; background: #f8fafc; padding: 8px 16px; border-radius: 10px; border: 1px solid #e2e8f0; }
.active-tab-indicator { font-size: 12px; font-weight: 700; color: #0f172a; text-transform: uppercase; letter-spacing: 0.5px; }

.search-input-box { display: flex; align-items: center; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 14px; width: 320px; height: 40px; }
.search-icon { stroke: #64748b; margin-right: 10px; flex-shrink: 0; }
.search-input-box input { border: none; background: transparent; outline: none; font-size: 13px; color: #1e293b; width: 100%; }

.items-table-frame { border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden; }
.enterprise-data-table { width: 100%; border-collapse: collapse; text-align: left; }
.enterprise-data-table th { background: #f8fafc; padding: 14px 18px; font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; letter-spacing: 0.5px; }
.enterprise-data-table td { padding: 16px 18px; border-bottom: 1px solid #e2e8f0; font-size: 13px; color: #334155; vertical-align: middle; }

.font-bold { font-weight: 700; } .font-mono { font-family: monospace; font-size: 12px; }
.text-slate-900 { color: #0f172a; font-size: 13px; } .text-center { text-align: center; }
.empty-data-message { text-align: center; color: #94a3b8; padding: 40px !important; font-size: 13px; }

.status-pill { padding: 4px 10px; border-radius: 6px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
.pill-info { background: #e0f2fe; color: #0284c7; }

.action-buttons-group { display: flex; justify-content: center; gap: 8px; }
.action-btn { width: 32px; height: 32px; border-radius: 6px; border: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s; background: #ffffff; color: #334155; }
.action-btn:hover { background: #f1f5f9; border-color: #94a3b8; color: #0f172a; }
.edit-btn:hover { color: #2563eb; }
.delete-btn:hover { color: #dc2626; }

.modal-backdrop { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.65); display: flex; align-items: center; justify-content: center; z-index: 1000; backdrop-filter: blur(2px); }
.modal-card-container { background: #ffffff; width: 540px; max-height: 90vh; overflow-y: auto; border-radius: 12px; box-shadow: 0 20px 30px rgba(0,0,0,0.15); }
.modal-header-bar { background: #0f172a; padding: 18px 24px; display: flex; justify-content: space-between; align-items: center; color: #ffffff; position: sticky; top: 0; z-index: 10; }
.modal-header-bar h3 { font-size: 14px; font-weight: 700; text-transform: uppercase; margin: 0; letter-spacing: 0.5px; }
.btn-close-modal { background: none; border: none; color: #ffffff; font-size: 24px; cursor: pointer; }
.modal-body-content { padding: 28px; }

.form-grid-columns { display: grid; grid-template-columns: 1fr; gap: 20px; margin-bottom: 24px; }
.form-control-group { display: flex; flex-direction: column; }
.form-control-group.full-width { grid-column: span 1; }
.form-control-group label { display: block; font-size: 11px; font-weight: 700; text-transform: uppercase; color: #64748b; margin-bottom: 6px; letter-spacing: 0.5px; }
.form-control-group input { width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px; color: #1e293b; outline: none; transition: border-color 0.2s; box-sizing: border-box; background-color: #ffffff; }
.form-control-group input:focus { border-color: #0f172a; box-shadow: 0 0 0 3px rgba(15, 23, 42, 0.1); }

.form-submit-row { display: flex; justify-content: flex-end; gap: 12px; border-top: 1px solid #e2e8f0; padding-top: 20px; }
.btn-cancel-modal { background: #f1f5f9; color: #475569; padding: 10px 18px; border-radius: 8px; font-size: 11px; font-weight: 700; border: none; cursor: pointer; text-transform: uppercase; }
.btn-cancel-modal:hover { background: #e2e8f0; }
</style>