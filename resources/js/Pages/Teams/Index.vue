<template>
  <app-layout>
    <div class="dashboard-page-wrapper">
      
      <!-- HERO HEADER -->
      <div class="welcome-hero-banner mb-24">
        <div class="hero-text-content">
          <div class="hero-title-badge">
            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>
            <span>TEAM MANAGEMENT</span>
          </div>
          <h1>Team Directory &amp; Monthly Quotas</h1>
          <p>Manage sales teams, track assigned team members, and configure monthly revenue targets.</p>
        </div>
        <button @click="openAddModal" class="btn-hero-action">
          <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
          Add New Team
        </button>
      </div>

      <!-- KPI METRIC CARDS -->
      <div class="kpi-grid-container mb-24">
        <template v-if="isLoading">
          <SkeletonCard v-for="n in 3" :key="n" />
        </template>
        <template v-else>
        <div class="kpi-card-item border-l-4 border-l-slate-700">
          <div class="kpi-info">
            <span class="kpi-label">Active Teams</span>
            <span class="kpi-number">{{ teams.length }}</span>
            <small class="text-xs text-slate-500 mt-1">Configured teams</small>
          </div>
          <div class="kpi-icon-box bg-slate-100 text-slate-700">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
          </div>
        </div>

        <div class="kpi-card-item border-l-4 border-l-amber-500">
          <div class="kpi-info">
            <span class="kpi-label">Assigned Members</span>
            <span class="kpi-number text-amber-700">{{ totalMembersCount }}</span>
            <small class="text-xs text-slate-500 mt-1">Total team staff</small>
          </div>
          <div class="kpi-icon-box bg-amber-50 text-amber-600">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
          </div>
        </div>

        <div class="kpi-card-item border-l-4 border-l-slate-700">
          <div class="kpi-info">
            <span class="kpi-label">Total Monthly Quota</span>
            <span class="kpi-number text-slate-800">₱{{ formatCurrency(totalQuotaSum) }}</span>
            <small class="text-xs text-slate-500 mt-1">Combined target revenue</small>
          </div>
          <div class="kpi-icon-box bg-slate-100 text-slate-700">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>
          </div>
        </div>
        </template>
      </div>

      <!-- MAIN TABLE PANEL -->
      <div class="analytics-card-panel">
        <div class="panel-header-box flex-between-box">
          <div class="flex items-center gap-2">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" class="text-amber-400"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>
            <h3>Teams Directory</h3>
          </div>
          <span class="text-xs font-bold text-amber-400">{{ filteredTeams.length }} / {{ teams.length }} Teams</span>
        </div>

        <div class="panel-body-content">
          <div class="table-controls-row mb-20">
            <div class="modern-tabs-container">
              <span class="active-tab-indicator">All Teams Directory</span>
            </div>

            <div class="search-input-box" style="width: 320px; max-width: 100%;">
              <svg class="search-icon" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
              <input v-model="searchQuery" type="text" placeholder="Search team name..." />
            </div>
          </div>

          <div class="items-table-frame">
            <SkeletonTable v-if="isLoading" :rows="6" :cols="6" />
            <template v-else>
            <table class="enterprise-data-table">
              <thead>
                <tr>
                  <th style="width: 120px;">Team ID</th>
                  <th>Team Name</th>
                  <th>Monthly Target Quota</th>
                  <th>Total Members</th>
                  <th>Created Date</th>
                  <th style="width: 100px; text-align: center;">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="team in filteredTeams" :key="team.id" class="team-row-item">
                  <td>
                    <span class="team-id-badge">#TEAM-{{ String(team.id).padStart(4, '0') }}</span>
                  </td>
                  <td class="font-bold text-slate-900">{{ team.team_name }}</td>
                  <td class="font-bold text-amber-700">₱{{ formatCurrency(team.quota) }}</td>
                  <td>
                    <span class="status-pill pill-amber">{{ team.users_count || 0 }} Members</span>
                  </td>
                  <td class="text-xs text-slate-500">{{ formatDate(team.created_at) }}</td>
                  <td class="text-center">
                    <div class="action-buttons-group">
                      <button @click="openEditModal(team)" class="action-btn edit-btn" title="Edit Team">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                      </button>
                      <button @click="promptDeleteTeam(team)" class="action-btn delete-btn" title="Delete Team">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                      </button>
                    </div>
                  </td>
                </tr>
                <tr v-if="filteredTeams.length === 0 && !isLoading">
                  <td colspan="6" class="empty-data-message">
                    <div class="empty-state-box">
                      <svg viewBox="0 0 24 24" width="36" height="36" fill="none" stroke="currentColor" stroke-width="1.5" class="text-slate-300 mb-2"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>
                      <p class="font-semibold text-slate-600 mb-1">No matching teams found</p>
                      <span class="text-xs text-slate-400">Try adjusting your search query.</span>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
            </template>
          </div>
        </div>
      </div>

      <!-- CREATE TEAM MODAL -->
      <div v-if="showModal" class="modal-backdrop">
        <div class="modal-card-container">
          <div class="modal-header-bar">
            <div class="flex items-center gap-2">
              <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" class="text-amber-400"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
              <h3>Create New Team</h3>
            </div>
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

      <!-- EDIT TEAM MODAL -->
      <div v-if="showEditModal" class="modal-backdrop">
        <div class="modal-card-container">
          <div class="modal-header-bar">
            <div class="flex items-center gap-2">
              <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" class="text-amber-400"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
              <h3>Update Team: <span style="color: #fbbf24;">{{ editForm.team_name }}</span></h3>
            </div>
            <button @click="showEditModal = false" class="btn-close-modal">&times;</button>
          </div>

          <form @submit.prevent="promptUpdateTeam" class="modal-body-content">
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

      <!-- DELETE TEAM CONFIRMATION MODAL -->
      <div v-if="showDeleteModal" class="modal-backdrop" style="z-index: 1100;">
        <div class="confirm-card-container">
          <div class="confirm-icon-box" style="background: #fee2e2;">
            <svg viewBox="0 0 24 24" width="30" height="30" fill="none" stroke="#dc2626" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
          </div>
          <h3>Confirm Delete Team</h3>
          <p>Are you sure you want to permanently delete team <strong>{{ selectedTeamForDeletion?.team_name }}</strong>? This action cannot be undone.</p>
          <div class="confirm-buttons-row">
            <button type="button" @click="showDeleteModal = false" class="btn-cancel-modal">Cancel</button>
            <button type="button" @click="executeDeleteTeam" :disabled="isSubmitting" class="btn-primary-action bg-red-600 border-red-600 hover:bg-red-700">
              {{ isSubmitting ? 'Deleting...' : 'Yes, Delete Team' }}
            </button>
          </div>
        </div>
      </div>

      <!-- EDIT TEAM CONFIRMATION MODAL -->
      <div v-if="showEditConfirmModal" class="modal-backdrop" style="z-index: 1100;">
        <div class="confirm-card-container">
          <div class="confirm-icon-box" style="background: #fef3c7;">
            <svg viewBox="0 0 24 24" width="30" height="30" fill="none" stroke="#d97706" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
          </div>
          <h3>Confirm Team Updates</h3>
          <p>Are you sure you want to save the updates to team <strong>{{ editForm.team_name }}</strong>?</p>
          <div class="confirm-buttons-row">
            <button type="button" @click="showEditConfirmModal = false" class="btn-cancel-modal">Cancel</button>
            <button type="button" @click="executeUpdateTeam" :disabled="isSubmitting" class="btn-primary-action bg-emerald-600 border-emerald-600 hover:bg-emerald-700">
              {{ isSubmitting ? 'Saving...' : 'Yes, Save Changes' }}
            </button>
          </div>
        </div>
      </div>

    </div>
  </app-layout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useTeams } from '@/composables/useTeams';
import SkeletonCard from '@/Components/Skeleton/SkeletonCard.vue';
import SkeletonTable from '@/Components/Skeleton/SkeletonTable.vue';
import './Index.css';

const {
  // State
  teams, searchQuery, showModal, showEditModal,
  showDeleteModal, selectedTeamForDeletion, showEditConfirmModal,
  isSubmitting, isLoading, form, editForm,

  // Computeds
  totalMembersCount, totalQuotaSum, filteredTeams,

  // Helpers & Actions
  formatCurrency, formatDate, fetchTeams, openAddModal,
  openEditModal, promptDeleteTeam, executeDeleteTeam, saveTeam,
  promptUpdateTeam, executeUpdateTeam
} = useTeams();
</script>