<template>
  <app-layout>
    <div class="dashboard-page-wrapper">

      <!-- ── HERO HEADER ─────────────────────────────────────────────────── -->
      <div class="welcome-hero-banner mb-24">
        <div class="hero-text-content">
          <div class="hero-title-badge">
            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
            <span>CLIENT MANAGEMENT</span>
          </div>
          <h1>Client Accounts &amp; Company Directory</h1>
          <p>Register corporate clients, manage contact details, assign supervisors, and track approval status.</p>
        </div>
        <button @click="openAddModal" class="btn-hero-action">
          <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
          Add New Client
        </button>
      </div>

      <!-- ── KPI CARDS ──────────────────────────────────────────────────── -->
      <div class="kpi-grid-container mb-24">
        <template v-if="isLoading">
          <SkeletonCard v-for="n in 3" :key="n" />
        </template>
        <template v-else>
        <div class="kpi-card-item border-l-4 border-l-amber-500">
          <div class="kpi-info">
            <span class="kpi-label">Active Clients</span>
            <span class="kpi-number text-amber-700">{{ activeClientsCount }}</span>
            <small class="text-xs text-slate-500 mt-1">Approved &amp; operational</small>
          </div>
          <div class="kpi-icon-box bg-amber-50 text-amber-600">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle></svg>
          </div>
        </div>

        <div class="kpi-card-item border-l-4 border-l-slate-700">
          <div class="kpi-info">
            <span class="kpi-label">Pending Approvals</span>
            <span class="kpi-number text-slate-800">{{ pendingClientsCount }}</span>
            <small class="text-xs text-slate-500 mt-1">Awaiting sign-off</small>
          </div>
          <div class="kpi-icon-box bg-slate-100 text-slate-700" style="background: #f1f5f9;">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
          </div>
        </div>

        <div class="kpi-card-item border-l-4 border-l-amber-500">
          <div class="kpi-info">
            <span class="kpi-label">Total Registered</span>
            <span class="kpi-number text-amber-700">{{ clients.length }}</span>
            <small class="text-xs text-slate-500 mt-1">All accounts in system</small>
          </div>
          <div class="kpi-icon-box bg-amber-50 text-amber-600">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>
          </div>
        </div>
        </template>
      </div>

      <!-- ── CLIENT TABLE PANEL ─────────────────────────────────────────── -->
      <div class="analytics-card-panel">
        <div class="panel-header-box flex-between-box">
          <div class="flex items-center gap-2">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" class="text-amber-400"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle></svg>
            <h3>Client Directory</h3>
          </div>
          <span class="text-xs font-bold text-amber-400">{{ filteredClients.length }} / {{ clients.length }} Clients</span>
        </div>

        <div class="panel-body-content">
          <!-- Tabs + Search -->
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
            <div class="controls-right-group">
              <div class="search-input-box">
                <svg class="search-icon" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <input v-model="searchQuery" type="text" placeholder="Search company, contact, TIN..." />
                <button v-if="searchQuery" @click="searchQuery = ''" class="btn-clear-search">&times;</button>
              </div>
            </div>
          </div>

          <!-- Table -->
          <div class="items-table-frame">
              <SkeletonTable v-if="isLoading" :rows="8" :cols="7" />
              <template v-else>
            <table class="enterprise-data-table">
              <thead>
                <tr>
                  <th style="width: 120px;">Client Code</th>
                  <th>Company Name</th>
                  <th>Contact Person &amp; Position</th>
                  <th>Assigned Supervisor</th>
                  <th>TIN Number</th>
                  <th>Status</th>
                  <th style="width: 100px; text-align: center;">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="client in paginatedClients" :key="client.id" class="client-row-item">
                  <td>
                    <span class="client-code-badge">
                      {{ client.client_code || 'CL-' + String(client.id).padStart(4, '0') }}
                    </span>
                  </td>
                  <td class="font-bold text-slate-900">{{ client.client_name }}</td>
                  <td>
                    <div class="contact-meta">
                      <span class="main-contact-email">{{ client.contact_person }}</span>
                      <small class="sub-contact-num">{{ client.position || 'N/A' }}</small>
                    </div>
                  </td>
                  <td>
                    <span class="font-bold" style="color: #334155;">
                      {{ client.user ? client.user.name : (getUserName(client.user_id) || 'Unassigned') }}
                    </span>
                  </td>
                  <td class="font-mono" style="color: #475569;">{{ client.tin_number || 'N/A' }}</td>
                  <td>
                    <span :class="['status-pill', getStatusClass(client.client_status || client.status)]">
                      <span class="pill-dot"></span>
                      {{ client.client_status || client.status }}
                    </span>
                  </td>
                  <td class="text-center">
                    <div class="action-buttons-group">
                      <button @click="openViewModal(client)" class="action-btn edit-btn" title="View / Edit Client">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                      </button>
                      <button @click="promptDeleteClient(client)" class="action-btn delete-btn" title="Delete Client">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                      </button>
                    </div>
                  </td>
                </tr>
                <tr v-if="filteredClients.length === 0 && !isLoading">
                  <td colspan="7">
                    <div class="empty-state-box">
                      <svg viewBox="0 0 24 24" width="36" height="36" fill="none" stroke="currentColor" stroke-width="1.5" class="text-slate-300 mb-2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle></svg>
                      <p class="font-bold" style="color: #475569; margin-bottom: 4px;">No matching client records found</p>
                      <span class="text-xs text-slate-400">Try adjusting your search or status filter.</span>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
              </template>
          </div>

          <!-- Pagination -->
          <div class="pagination-footer-bar" v-if="filteredClients.length > 0">
            <div class="pagination-left-info">
              <span class="text-xs text-slate-500 font-bold">
                Showing {{ paginationStart }} to {{ paginationEnd }} of {{ filteredClients.length }} Clients
              </span>
              <div class="per-page-selector">
                <span class="text-xs text-slate-500" style="font-weight: 500;">Per page:</span>
                <select v-model="perPage" class="select-per-page">
                  <option :value="10">10</option>
                  <option :value="15">15</option>
                  <option :value="25">25</option>
                  <option :value="50">50</option>
                  <option :value="100">100</option>
                  <option :value="-1">All</option>
                </select>
              </div>
            </div>
            <div class="pagination-nav-group" v-if="totalPages > 1">
              <button @click="currentPage = 1" :disabled="currentPage <= 1" class="btn-pagination-nav" title="First Page">&laquo;</button>
              <button @click="currentPage--" :disabled="currentPage <= 1" class="btn-pagination-nav" title="Previous Page">&lsaquo; Prev</button>
              <template v-for="(p, pIdx) in visiblePages" :key="pIdx">
                <span v-if="p === '...'" class="pagination-ellipsis">&hellip;</span>
                <button v-else @click="currentPage = p" :class="['btn-pagination-page', { active: currentPage === p }]">{{ p }}</button>
              </template>
              <button @click="currentPage++" :disabled="currentPage >= totalPages" class="btn-pagination-nav" title="Next Page">Next &rsaquo;</button>
              <button @click="currentPage = totalPages" :disabled="currentPage >= totalPages" class="btn-pagination-nav" title="Last Page">&raquo;</button>
            </div>
          </div>
        </div>
      </div>

      <!-- ── ADD CLIENT MODAL ───────────────────────────────────────────── -->
      <div v-if="showAddModal" class="modal-backdrop">
        <div class="modal-card-container">
          <div class="modal-header-bar">
            <div class="flex items-center gap-2">
              <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" class="text-amber-400"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
              <h3>New Client Registration</h3>
            </div>
            <button @click="showAddModal = false" class="btn-close-modal">&times;</button>
          </div>
          <form @submit.prevent="saveNewClient" class="modal-body-content">
            <div class="form-grid-columns">
              <div class="form-control-group full-width">
                <label>Company / Client Name</label>
                <input v-model="addForm.client_name" type="text" required placeholder="e.g. Acme Corporation Philippines Inc." />
              </div>
              <div class="form-control-group">
                <label>Contact Person</label>
                <input v-model="addForm.contact_person" type="text" required placeholder="e.g. Juan Dela Cruz" />
              </div>
              <div class="form-control-group">
                <label>Position</label>
                <input v-model="addForm.position" type="text" placeholder="e.g. Procurement Manager" />
              </div>
              <div class="form-control-group">
                <label>Assigned Supervisor / POC</label>
                <select v-model="addForm.user_id" required class="form-select-dropdown">
                  <option disabled value="">Select Supervisor / User</option>
                  <option v-for="user in users" :key="user.id" :value="user.id">
                    {{ user.name }} ({{ user.role }})
                  </option>
                </select>
              </div>
              <div class="form-control-group">
                <label>Phone / Mobile</label>
                <input v-model="addForm.contact_number" type="text" placeholder="0917XXXXXXX" />
              </div>
              <div class="form-control-group">
                <label>TIN Number</label>
                <input v-model="addForm.tin_number" type="text" placeholder="000-000-000-000" />
              </div>
              <div class="form-control-group">
                <label>Initial Status</label>
                <select v-model="addForm.client_status" class="form-select-dropdown">
                  <option value="Approved">Approved</option>
                  <option value="Pending Approval">Pending Approval</option>
                  <option value="Suspended">Suspended</option>
                </select>
              </div>
              <div class="form-control-group full-width">
                <label>Complete Company Address</label>
                <input v-model="addForm.client_address" type="text" required placeholder="Building, Street, City, Province" />
              </div>
            </div>
            <div class="form-submit-row">
              <button type="button" @click="showAddModal = false" class="btn-cancel-modal">Cancel</button>
              <button type="submit" :disabled="isSubmitting" class="btn-primary-action">
                {{ isSubmitting ? 'Registering...' : 'Register Client' }}
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- ── VIEW / EDIT CLIENT MODAL ──────────────────────────────────── -->
      <div v-if="showViewModal" class="modal-backdrop">
        <div class="modal-card-container">
          <div class="modal-header-bar">
            <div class="flex items-center gap-2">
              <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" class="text-amber-400"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
              <h3>Client Profile #{{ selectedClient?.id }}</h3>
            </div>
            <button @click="showViewModal = false" class="btn-close-modal">&times;</button>
          </div>
          <form @submit.prevent="promptUpdateClient" class="modal-body-content" v-if="selectedClient">
            <div class="form-grid-columns">
              <div class="form-control-group">
                <label>Client Code</label>
                <input :value="selectedClient.client_code || 'CL-' + String(selectedClient.id).padStart(4, '0')" disabled class="bg-gray-50" />
              </div>
              <div class="form-control-group">
                <label>Approval Status</label>
                <select v-model="selectedClient.client_status" class="form-select-dropdown">
                  <option value="Approved">Approved</option>
                  <option value="Pending Approval">Pending Approval</option>
                  <option value="Suspended">Suspended</option>
                </select>
              </div>
              <div class="form-control-group full-width">
                <label>Company / Client Name</label>
                <input v-model="selectedClient.client_name" type="text" required />
              </div>
              <div class="form-control-group">
                <label>Contact Person</label>
                <input v-model="selectedClient.contact_person" type="text" required />
              </div>
              <div class="form-control-group">
                <label>Position</label>
                <input v-model="selectedClient.position" type="text" />
              </div>
              <div class="form-control-group">
                <label>Assigned Supervisor / POC</label>
                <select v-model="selectedClient.user_id" required class="form-select-dropdown">
                  <option disabled value="">Select Supervisor</option>
                  <option v-for="user in users" :key="user.id" :value="user.id">
                    {{ user.name }} ({{ user.role }})
                  </option>
                </select>
              </div>
              <div class="form-control-group">
                <label>Phone Number</label>
                <input v-model="selectedClient.contact_number" type="text" />
              </div>
              <div class="form-control-group full-width">
                <label>TIN Number</label>
                <input v-model="selectedClient.tin_number" type="text" />
              </div>
              <div class="form-control-group full-width">
                <label>Company Address</label>
                <input v-model="selectedClient.client_address" type="text" required />
              </div>
            </div>
            <div class="form-submit-row">
              <button type="button" @click="showViewModal = false" class="btn-cancel-modal">Cancel</button>
              <button type="submit" :disabled="isSubmitting" class="btn-primary-action">
                {{ isSubmitting ? 'Saving...' : 'Save Profile Updates' }}
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- ── DELETE CONFIRMATION MODAL ─────────────────────────────────── -->
      <div v-if="showDeleteModal" class="modal-backdrop" style="z-index: 1100;">
        <div class="confirm-card-container">
          <div class="confirm-icon-box" style="background: #fee2e2;">
            <svg viewBox="0 0 24 24" width="30" height="30" fill="none" stroke="#dc2626" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
          </div>
          <h3>Confirm Delete Client</h3>
          <p>Are you sure you want to permanently delete client <strong>{{ selectedClientForDeletion?.client_name }}</strong>? This action cannot be undone.</p>
          <div class="confirm-buttons-row">
            <button type="button" @click="showDeleteModal = false" class="btn-cancel-modal">Cancel</button>
            <button type="button" @click="executeDeleteClient" :disabled="isSubmitting" class="btn-primary-action bg-red-600 border-red-600 hover:bg-red-700">
              {{ isSubmitting ? 'Deleting...' : 'Yes, Delete Client' }}
            </button>
          </div>
        </div>
      </div>

      <!-- ── EDIT CONFIRMATION MODAL ────────────────────────────────────── -->
      <div v-if="showEditConfirmModal" class="modal-backdrop" style="z-index: 1100;">
        <div class="confirm-card-container">
          <div class="confirm-icon-box" style="background: #fef3c7;">
            <svg viewBox="0 0 24 24" width="30" height="30" fill="none" stroke="#d97706" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
          </div>
          <h3>Confirm Profile Updates</h3>
          <p>Are you sure you want to save your changes to client <strong>{{ selectedClient?.client_name }}</strong>?</p>
          <div class="confirm-buttons-row">
            <button type="button" @click="showEditConfirmModal = false" class="btn-cancel-modal">Cancel</button>
            <button type="button" @click="executeUpdateClient" :disabled="isSubmitting" class="btn-primary-action bg-emerald-600 border-emerald-600 hover:bg-emerald-700">
              {{ isSubmitting ? 'Saving...' : 'Yes, Save Changes' }}
            </button>
          </div>
        </div>
      </div>

    </div>
  </app-layout>
</template>

<script setup>
import { onMounted } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useClients } from '@/composables/useClients';
import SkeletonCard from '@/Components/Skeleton/SkeletonCard.vue';
import SkeletonTable from '@/Components/Skeleton/SkeletonTable.vue';
import './Index.css';

const {
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
  initClients,
  openAddModal, openViewModal,
  promptDeleteClient, executeDeleteClient,
  saveNewClient, promptUpdateClient, executeUpdateClient,
} = useClients();

onMounted(() => initClients());
</script>