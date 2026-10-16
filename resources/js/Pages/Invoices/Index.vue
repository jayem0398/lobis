<template>
  <app-layout>
    <div class="dashboard-page-wrapper">

      <!-- ── HERO HEADER ─────────────────────────────────────────────────── -->
      <div class="welcome-hero-banner mb-24">
        <div class="hero-text-content">
          <div class="hero-title-badge">
            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
            <span>SERVICE INVOICES</span>
          </div>
          <h1>Service Invoices &amp; Billing Control</h1>
          <p>Create, manage, track payment status, and process approvals for all client billing records.</p>
        </div>
        <a href="/invoices/create" class="btn-hero-action">
          <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
          Create New Invoice
        </a>
      </div>

      <!-- ── KPI SUMMARY CARDS ───────────────────────────────────────────── -->
      <div class="kpi-grid-container mb-24">
        <template v-if="isLoading">
          <SkeletonCard v-for="n in 4" :key="n" />
        </template>
        <template v-else>
        <div class="kpi-card-item border-l-4 border-l-slate-700">
          <div class="kpi-info">
            <span class="kpi-label">Total Billing</span>
            <span class="kpi-number text-slate-800">₱{{ formatCurrency(totalInvoiced) }}</span>
            <small class="text-xs text-slate-500 mt-1">Total invoiced amount</small>
          </div>
          <div class="kpi-icon-box bg-slate-100 text-slate-700">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
          </div>
        </div>

        <div class="kpi-card-item border-l-4 border-l-amber-500">
          <div class="kpi-info">
            <span class="kpi-label">Total Collected</span>
            <span class="kpi-number text-amber-700">₱{{ formatCurrency(totalCollected) }}</span>
            <small class="text-xs text-slate-500 mt-1">Paid &amp; confirmed revenue</small>
          </div>
          <div class="kpi-icon-box bg-amber-50 text-amber-600">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
          </div>
        </div>

        <div class="kpi-card-item border-l-4 border-l-slate-700">
          <div class="kpi-info">
            <span class="kpi-label">Outstanding Balance</span>
            <span class="kpi-number text-slate-800">₱{{ formatCurrency(outstandingBalance) }}</span>
            <small class="text-xs text-slate-500 mt-1">Pending payments</small>
          </div>
          <div class="kpi-icon-box bg-slate-100 text-slate-700">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
          </div>
        </div>

        <div class="kpi-card-item border-l-4 border-l-amber-500">
          <div class="kpi-info">
            <span class="kpi-label">Overdue Amount</span>
            <span class="kpi-number text-amber-700">₱{{ formatCurrency(overdueAmount) }}</span>
            <small class="text-xs text-slate-500 mt-1">Past due invoices</small>
          </div>
          <div class="kpi-icon-box bg-amber-50 text-amber-600">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
          </div>
        </div>
        </template>
      </div>

      <!-- ── QUOTA TRACKER (Admin only) ─────────────────────────────────── -->
      <div v-if="isAdminOrSuper" class="analytics-card-panel mb-24 p-20">
        <div class="flex-between-box mb-12">
          <h4 class="section-heading-title">Team Billing Quota &amp; Performance Tracker</h4>
          <span class="font-bold text-xs text-slate-500">
            {{ selectedTeamFilter ? 'Selected Team Progress' : 'Company-Wide Combined Quota' }}
          </span>
        </div>
        <div class="quota-progress-container">
          <div class="quota-info-row">
            <span>Collected / Total Billing: <strong>₱{{ formatCurrency(totalCollected) }}</strong></span>
            <span>Target Quota: <strong>₱{{ formatCurrency(activeTeamQuota) }}</strong></span>
            <span :class="{'text-amber-700 font-bold': quotaPercentage >= 100, 'text-amber-600 font-bold': quotaPercentage < 100}">
              Achievement: {{ quotaPercentage }}%
            </span>
          </div>
          <div class="progress-bar-track">
            <div class="progress-bar-fill" :style="{ width: Math.min(quotaPercentage, 100) + '%' }"></div>
          </div>
        </div>
      </div>

      <!-- ── INVOICE TABLE PANEL ────────────────────────────────────────── -->
      <div class="analytics-card-panel">
        <div class="panel-header-box flex-between-box">
          <div class="flex items-center gap-2">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" class="text-amber-400"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
            <h3>Invoice Directory &amp; Billing Control</h3>
          </div>
          <span class="text-xs font-bold text-amber-400">{{ filteredInvoices.length }} / {{ invoices.length }} Invoices</span>
        </div>

        <div class="panel-body-content">
          <!-- Tabs + Search -->
          <div class="table-controls-row mb-20">
            <div class="modern-tabs-container">
              <button
                v-for="tab in availableTabs"
                :key="tab"
                @click="activeTab = tab"
                :class="['modern-tab-btn', { active: activeTab === tab }]"
              >
                {{ tab }}
                <span v-if="tab === 'Deletion Requests' && pendingDeletionCount > 0" class="tab-badge">
                  {{ pendingDeletionCount }}
                </span>
              </button>
            </div>

            <div class="controls-right-group">
              <div v-if="isAdminOrSuper" class="team-filter-box">
                <select v-model="selectedTeamFilter" class="form-select-dropdown" style="width: 220px; height: 38px;">
                  <option value="">All Teams (Company-wide)</option>
                  <option v-for="team in teams" :key="team.id" :value="team.id">{{ team.team_name || team.name }}</option>
                </select>
              </div>
              <div class="search-input-box">
                <svg class="search-icon" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <input v-model="searchQuery" type="text" placeholder="Search invoice #, client..." />
                <button v-if="searchQuery" @click="searchQuery = ''" class="btn-clear-search">&times;</button>
              </div>
            </div>
          </div>

          <!-- Table -->
          <div class="items-table-frame">
              <SkeletonTable v-if="isLoading" :rows="8" :cols="6" />
              <template v-else>
            <table class="enterprise-data-table">
              <thead>
                <tr>
                  <th>Invoice #</th>
                  <th>Client Name</th>
                  <th v-if="isAdminOrSuper">Team</th>
                  <th>Issue Date</th>
                  <th>Due Date</th>
                  <th>Total Amount</th>
                  <th>Status / Aging</th>
                  <th style="width: 120px; text-align: center;">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="inv in paginatedInvoices" :key="inv.id" :class="{'deletion-pending-row': inv.deletion_requested && isAdminOrSuper}">
                  <td class="font-bold text-slate-900">
                    {{ inv.invoice_number }}
                    <span v-if="isAdminOrSuper && inv.deletion_requested" class="deletion-flag-badge" title="Pending Deletion Request">
                      <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                      Deletion Requested
                    </span>
                  </td>
                  <td>{{ inv.client ? inv.client.client_name : (inv.client_name || 'N/A') }}</td>
                  <td v-if="isAdminOrSuper">
                    <span class="team-badge">{{ inv.team ? (inv.team.team_name || inv.team.name) : 'No Team' }}</span>
                  </td>
                  <td>{{ inv.issue_date }}</td>
                  <td>{{ inv.due_date }}</td>
                  <td class="font-bold">₱{{ formatCurrency(inv.total_amount) }}</td>
                  <td>
                    <div class="status-cell">
                      <span :class="['status-pill', getBadgeClass(inv)]">{{ getDisplayStatus(inv) }}</span>
                      <span v-if="inv.edit_count > 0" class="edit-count-chip" :title="`Edited ${inv.edit_count} time(s)`">
                        Edit #{{ inv.edit_count }}
                      </span>
                    </div>
                  </td>
                  <td class="text-center">
                    <div class="action-buttons-group">
                      <button @click="openViewModal(inv)" class="action-btn view-btn" title="View / Manage Invoice">
                        <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                      </button>
                      <button
                        v-if="!inv.deletion_requested"
                        @click="promptDeleteInvoice(inv)"
                        class="action-btn delete-btn"
                        :title="isAdminOrSuper ? 'Delete Invoice' : 'Request Deletion'"
                      >
                        <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                      </button>
                      <span v-if="!isAdminOrSuper && inv.deletion_requested" class="deletion-pending-badge" title="Deletion request pending admin approval">
                        <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                        Pending Deletion
                      </span>
                    </div>
                  </td>
                </tr>
                <tr v-if="filteredInvoices.length === 0 && !isLoading">
                  <td :colspan="isAdminOrSuper ? 8 : 7" class="empty-data-message">No invoice records found matching your filter criteria.</td>
                </tr>
              </tbody>
            </table>
            </template>
          </div>

          <!-- Pagination -->
          <div class="pagination-footer-bar" v-if="filteredInvoices.length > 0">
            <div class="pagination-left-info">
              <span class="text-xs text-slate-500 font-semibold">
                Showing {{ paginationStart }} to {{ paginationEnd }} of {{ filteredInvoices.length }} Invoices
              </span>
              <div class="per-page-selector">
                <span class="text-xs text-slate-500 font-medium">Per page:</span>
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

      <!-- ── VIEW & MANAGE MODAL ────────────────────────────────────────── -->
      <div v-if="showViewModal" class="modal-backdrop">
        <div class="invoice-modal-card">
          <div class="modal-header-bar">
            <div class="modal-header-left">
              <h3>COMMERCIAL INVOICE — <span class="text-sky-400">{{ modalForm.invoice_number }}</span></h3>
              <span v-if="modalForm.edit_count > 0" class="modal-edit-badge">Edit #{{ modalForm.edit_count }}</span>
            </div>
            <div class="header-modal-actions">
              <button @click="printInvoice" class="btn-print-action" title="Print Invoice">
                <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                Print / PDF
              </button>
              <button @click="showViewModal = false" class="btn-close-modal">&times;</button>
            </div>
          </div>

          <div class="invoice-paper-content" id="printable-invoice-area">
            <!-- Company + Invoice meta -->
            <div class="invoice-header-section">
              <div class="company-brand-group">
                <img src="/images/logo.png" alt="Company Logo" class="invoice-company-logo" @error="handleImageError" />
                <div class="company-brand">
                  <h2>LENDELL OUTSOURCING SOLUTIONS, INC.</h2>
                  <p>Official Commercial Invoice Document</p>
                </div>
              </div>
              <div class="invoice-meta-grid">
                <div class="form-control-group">
                  <label>Invoice Number</label>
                  <input v-model="modalForm.invoice_number" type="text" readonly class="bg-gray-50 font-bold text-slate-900 form-control-input" />
                </div>
                <div class="form-control-group">
                  <label>Status</label>
                  <select v-if="isAdminOrSuper" v-model="modalForm.status" class="form-select-dropdown font-bold">
                    <option value="Pending Approval">Pending Approval</option>
                    <option value="Approved">Approved</option>
                    <option value="Paid">Paid</option>
                    <option value="Cancelled">Cancelled</option>
                  </select>
                  <div v-else class="status-pill-container">
                    <span :class="['status-pill', getBadgeClass({due_date: modalForm.due_date, status: modalForm.status})]">
                      {{ modalForm.status }}
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Paid lock banner -->
            <div v-if="modalForm.status === 'Paid'" class="paid-locked-banner">
              <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
              <span>Official Paid Invoice &mdash; Record is finalized and locked from further edits.</span>
            </div>

            <!-- Billed To + Dates -->
            <div class="invoice-parties-grid mt-20">
              <div class="form-control-group">
                <label class="section-title">Billed To Client</label>
                <select v-if="isSuperAdmin && isModalEditable" v-model="modalForm.client_id" class="form-select-dropdown font-bold">
                  <option v-for="client in clients" :key="client.id" :value="client.id">{{ client.client_name }}</option>
                </select>
                <input v-else type="text" :value="getClientName(modalForm.client_id)" readonly class="bg-gray-50 form-control-input font-bold" />
              </div>
              <div class="dates-inline-grid">
                <div class="form-control-group">
                  <label class="section-title">Issue Date</label>
                  <input v-model="modalForm.issue_date" :disabled="!isModalEditable" :class="{'bg-gray-50 cursor-not-allowed': !isModalEditable}" class="form-control-input" type="date" />
                </div>
                <div class="form-control-group">
                  <label class="section-title">Due Date</label>
                  <input v-model="modalForm.due_date" :disabled="!isModalEditable" :class="{'bg-gray-50 cursor-not-allowed': !isModalEditable}" class="form-control-input" type="date" />
                </div>
              </div>
            </div>

            <!-- Tax Type + WHT -->
            <div class="vat-selection-row mt-16 flex items-center justify-between gap-4 w-full">
              <div class="form-control-group" style="max-width: 280px;">
                <label class="section-title">Tax Type</label>
                <select v-model="modalForm.vat_type" :disabled="!isModalEditable" :class="{'bg-gray-50 cursor-not-allowed': !isModalEditable}" class="form-select-dropdown font-bold">
                  <option value="vat_inclusive">VAT Inclusive</option>
                  <option value="vat_exclusive">VAT Exclusive</option>
                  <option value="vat_exempt">VAT Exempt</option>
                </select>
              </div>
              <div class="flex items-center justify-end pb-1" style="margin-left: auto;">
                <label class="checkbox-label-inline cursor-pointer flex items-center gap-2 m-0">
                  <input type="checkbox" v-model="modalForm.has_withholding_tax" :disabled="!isModalEditable" class="rounded text-sky-600 focus:ring-sky-500 h-4 w-4" />
                  <span class="font-bold text-slate-700" style="font-size: 11px;">Apply Withholding Tax (2% EWT)</span>
                </label>
              </div>
            </div>

            <!-- Line Items -->
            <div class="mt-20">
              <div class="flex justify-between items-center mb-8">
                <label class="section-title">Line Items &amp; Descriptions</label>
                <button v-if="isModalEditable" type="button" @click="addModalItem" class="btn-add-line text-xs font-semibold px-2 py-1 bg-sky-50 text-sky-600 rounded border border-sky-200 hover:bg-sky-100">+ Add Line Item</button>
              </div>
              <table class="modal-items-table">
                <thead>
                  <tr>
                    <th style="text-align: left;">Description</th>
                    <th style="width: 100px; text-align: center;">Qty</th>
                    <th style="width: 140px; text-align: right;">Price (₱)</th>
                    <th style="width: 140px; text-align: right;">Total (₱)</th>
                    <th v-if="isModalEditable" style="width: 40px; text-align: center;"></th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(item, index) in modalForm.items" :key="index">
                    <td><input v-model="item.item_name" :disabled="!isModalEditable" :class="{'bg-gray-50 cursor-not-allowed': !isModalEditable}" class="form-control-input" type="text" placeholder="Service description..." /></td>
                    <td class="text-center"><input v-model.number="item.quantity" :disabled="!isModalEditable" :class="{'bg-gray-50 cursor-not-allowed': !isModalEditable}" class="form-control-input text-center" type="number" min="0.1" step="any" /></td>
                    <td class="text-right"><input v-model.number="item.price" :disabled="!isModalEditable" :class="{'bg-gray-50 cursor-not-allowed': !isModalEditable}" class="form-control-input text-right" type="number" min="0" step="any" /></td>
                    <td class="text-right font-bold text-slate-900">₱{{ formatCurrency((item.quantity || 0) * (item.price || 0)) }}</td>
                    <td v-if="isModalEditable" class="text-center">
                      <button v-if="modalForm.items.length > 1" type="button" @click="removeModalItem(index)" class="text-red-500 hover:text-red-700 font-bold text-lg">&times;</button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Notes + Totals -->
            <div class="invoice-summary-box mt-24">
              <div class="form-control-group">
                <label class="section-title">Notes / Payment Instructions</label>
                <textarea v-model="modalForm.notes" :disabled="!isModalEditable" :class="{'bg-gray-50 cursor-not-allowed': !isModalEditable}" class="form-control-input" rows="3" placeholder="Enter notes or bank info..."></textarea>
              </div>
              <div class="totals-calculation-box">
                <div class="total-row">
                  <span>Subtotal:</span>
                  <span class="font-bold">₱{{ formatCurrency(modalSubtotal) }}</span>
                </div>
                <div class="total-row">
                  <span>{{ modalForm.vat_type === 'vat_inclusive' ? 'VAT (Inclusive 12%):' : (modalForm.vat_type === 'vat_exclusive' ? 'VAT (12%):' : 'Tax / Non-VAT (0%):') }}</span>
                  <span class="font-bold">₱{{ formatCurrency(modalTaxAmount) }}</span>
                </div>
                <div class="total-row" v-if="modalForm.has_withholding_tax || modalWhtAmount > 0">
                  <span>Withholding Tax (2%):</span>
                  <span class="font-bold text-red-600">-₱{{ formatCurrency(modalWhtAmount) }}</span>
                </div>
                <div class="total-row grand-total">
                  <span>Total Amount Due:</span>
                  <span class="font-bold text-sky-700">₱{{ formatCurrency(modalTotalAmount) }}</span>
                </div>
              </div>
            </div>

            <!-- Deletion notice (Admin) -->
            <div v-if="isAdminOrSuper && modalForm.deletion_requested" class="deletion-request-banner mt-20">
              <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
              <div>
                <strong>Deletion Requested</strong>
                <p>{{ modalForm.deletion_reason }}</p>
              </div>
            </div>
          </div>

          <!-- Footer Actions -->
          <div class="form-submit-row modal-footer-actions">
            <button type="button" @click="showViewModal = false" class="btn-cancel-modal">Close</button>

            <button
              v-if="isModalEditable"
              type="button"
              @click="promptSaveModalChanges"
              :disabled="isSubmitting"
              class="btn-primary-action bg-emerald-600 border-emerald-600 hover:bg-emerald-700 flex-btn"
            >
              <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
              {{ isSubmitting ? 'Saving...' : 'Save Invoice Changes' }}
            </button>

            <button
              v-if="isAdminOrSuper && modalForm.deletion_requested"
              type="button"
              @click="promptApproveDeletion"
              class="btn-primary-action bg-red-600 border-red-600 hover:bg-red-700 flex-btn"
            >
              <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
              Approve &amp; Delete
            </button>

            <button
              v-if="isAdminOrSuper"
              type="button"
              @click="saveStatusChange"
              :class="['btn-primary-action flex-btn', dynamicStatusBtnClass]"
            >
              <svg v-if="modalForm.status === 'Approved'" viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
              <svg v-else-if="modalForm.status === 'Paid'" viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"></rect><line x1="2" y1="10" x2="22" y2="10"></line></svg>
              <svg v-else-if="modalForm.status === 'Cancelled'" viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
              <svg v-else-if="modalForm.status === 'Pending Approval'" viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
              {{ dynamicStatusBtnLabel }}
            </button>
          </div>
        </div>
      </div>

      <!-- ── DELETE / REQUEST DELETION MODAL ───────────────────────────── -->
      <div v-if="showDeleteModal" class="modal-backdrop">
        <div class="confirm-card-container">
          <div class="confirm-icon-box" style="background: #fee2e2;">
            <svg viewBox="0 0 24 24" width="32" height="32" fill="none" stroke="#dc2626" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
          </div>
          <h3>{{ isAdminOrSuper ? 'Delete Invoice' : 'Request Deletion Approval' }}</h3>
          <p v-if="isAdminOrSuper">Are you sure you want to permanently delete invoice <strong>{{ selectedInvoiceForDeletion?.invoice_number }}</strong>?</p>
          <p v-else>As a user, you need to provide a reason for deletion. An admin must approve before the invoice is removed.</p>
          <div v-if="!isAdminOrSuper" class="form-control-group mb-20 text-left">
            <label>Reason for Deletion</label>
            <textarea v-model="deletionReason" rows="3" placeholder="State your reason for requesting deletion..." class="form-control-input"></textarea>
          </div>
          <div class="confirm-buttons-row">
            <button @click="showDeleteModal = false" class="btn-cancel-modal">Cancel</button>
            <button @click="executeDeletion" :disabled="isSubmitting || (!isAdminOrSuper && !deletionReason.trim())" class="btn-primary-action bg-red-600 border-red-600 hover:bg-red-700">
              {{ isSubmitting ? 'Processing...' : (isAdminOrSuper ? 'Yes, Delete' : 'Submit Request') }}
            </button>
          </div>
        </div>
      </div>

      <!-- ── EDIT CONFIRM MODAL ─────────────────────────────────────────── -->
      <div v-if="showEditConfirmModal" class="modal-backdrop" style="z-index: 1150;">
        <div class="confirm-card-container">
          <div class="confirm-icon-box" style="background: #fef3c7;">
            <svg viewBox="0 0 24 24" width="30" height="30" fill="none" stroke="#d97706" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
          </div>
          <h3>Confirm Save Changes</h3>
          <p>Are you sure you want to save all revisions made to invoice <strong>{{ modalForm.invoice_number }}</strong>?</p>
          <div class="confirm-buttons-row">
            <button type="button" @click="showEditConfirmModal = false" class="btn-cancel-modal">Cancel</button>
            <button type="button" @click="executeSaveModalChanges" :disabled="isSubmitting" class="btn-primary-action bg-emerald-600 border-emerald-600 hover:bg-emerald-700">
              {{ isSubmitting ? 'Saving...' : 'Yes, Save Changes' }}
            </button>
          </div>
        </div>
      </div>

      <!-- ── APPROVE DELETION MODAL ─────────────────────────────────────── -->
      <div v-if="showApproveDeleteModal" class="modal-backdrop" style="z-index: 1150;">
        <div class="confirm-card-container">
          <div class="confirm-icon-box" style="background: #fee2e2;">
            <svg viewBox="0 0 24 24" width="30" height="30" fill="none" stroke="#dc2626" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
          </div>
          <h3>Confirm Approve &amp; Delete</h3>
          <p>Are you sure you want to approve this deletion request and permanently delete invoice <strong>{{ modalForm.invoice_number }}</strong>?</p>
          <div class="confirm-buttons-row">
            <button type="button" @click="showApproveDeleteModal = false" class="btn-cancel-modal">Cancel</button>
            <button type="button" @click="executeApproveDeletion" :disabled="isSubmitting" class="btn-primary-action bg-red-600 border-red-600 hover:bg-red-700">
              {{ isSubmitting ? 'Deleting...' : 'Yes, Approve & Delete' }}
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
import { useInvoices } from '@/composables/useInvoices';
import SkeletonCard from '@/Components/Skeleton/SkeletonCard.vue';
import SkeletonTable from '@/Components/Skeleton/SkeletonTable.vue';
import './Index.css';

// Destructure everything from the composable
const {
  // State
  invoices, clients, teams,
  activeTab, searchQuery, selectedTeamFilter,
  currentPage, perPage,
  isAdmin, isSuperAdmin, isLoading,
  showViewModal, showDeleteModal, showEditConfirmModal, showApproveDeleteModal,
  selectedInvoiceForDeletion, deletionReason, isSubmitting,
  modalForm,

  // Computeds
  isAdminOrSuper, availableTabs, pendingDeletionCount,
  totalInvoiced, totalCollected, outstandingBalance, overdueAmount,
  activeTeamQuota, quotaPercentage,
  filteredInvoices, totalPages, paginationStart, paginationEnd,
  paginatedInvoices, visiblePages,
  modalSubtotal, modalTaxAmount, modalWhtAmount, modalTotalAmount,
  isModalEditable,
  dynamicStatusBtnLabel, dynamicStatusBtnClass,

  // Helpers
  formatCurrency, getDisplayStatus, getBadgeClass,
  getClientName, handleImageError,

  // Actions
  initInvoices,
  openViewModal, addModalItem, removeModalItem, printInvoice,
  promptSaveModalChanges, executeSaveModalChanges, saveStatusChange,
  promptDeleteInvoice, executeDeletion,
  promptApproveDeletion, executeApproveDeletion,
} = useInvoices();

onMounted(() => initInvoices());
</script>
