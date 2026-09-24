<template>
  <app-layout>
    <div class="dashboard-page-wrapper">
      
      <div class="kpi-grid-container mb-24">
        <div class="kpi-card-item">
          <div class="kpi-info">
            <span class="kpi-label">Total Billing</span>
            <span class="kpi-number">₱{{ formatCurrency(totalInvoiced) }}</span>
          </div>
          <div class="kpi-icon-box">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
          </div>
        </div>
        <div class="kpi-card-item">
          <div class="kpi-info">
            <span class="kpi-label">Total Collected</span>
            <span class="kpi-number">₱{{ formatCurrency(totalCollected) }}</span>
          </div>
          <div class="kpi-icon-box">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
          </div>
        </div>
        <div class="kpi-card-item">
          <div class="kpi-info">
            <span class="kpi-label">Outstanding Balance</span>
            <span class="kpi-number">₱{{ formatCurrency(outstandingBalance) }}</span>
          </div>
          <div class="kpi-icon-box">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
          </div>
        </div>
        <div class="kpi-card-item">
          <div class="kpi-info">
            <span class="kpi-label">Overdue Amount</span>
            <span class="kpi-number">₱{{ formatCurrency(overdueAmount) }}</span>
          </div>
          <div class="kpi-icon-box">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
          </div>
        </div>
      </div>

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
            <span :class="{'text-emerald-600 font-bold': quotaPercentage >= 100, 'text-sky-600 font-bold': quotaPercentage < 100}">
              Achievement: {{ quotaPercentage }}%
            </span>
          </div>
          <div class="progress-bar-track">
            <div class="progress-bar-fill" :style="{ width: Math.min(quotaPercentage, 100) + '%' }"></div>
          </div>
        </div>
      </div>

      <div class="analytics-card-panel">
        <div class="panel-header-box">
          <h3>Invoice Directory &amp; Billing Control</h3>
          <a href="/invoices/create" class="btn-primary-action">+ Create Invoice</a>
        </div>

        <div class="panel-body-content">
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

            <div v-if="isAdminOrSuper" class="team-filter-box">
              <select v-model="selectedTeamFilter" class="form-select-dropdown" style="width: 220px; height: 40px;">
                <option value="">All Teams (Company-wide)</option>
                <option v-for="team in teams" :key="team.id" :value="team.id">{{ team.team_name || team.name }}</option>
              </select>
            </div>

            <div class="search-input-box">
              <svg class="search-icon" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
              <input v-model="searchQuery" type="text" placeholder="Search by Invoice # or Client..." />
            </div>
          </div>

          <div class="items-table-frame">
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
                <tr v-for="inv in filteredInvoices" :key="inv.id" :class="{'deletion-pending-row': inv.deletion_requested && isAdminOrSuper}">
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
                      <span :class="['status-pill', getBadgeClass(inv)]">
                        {{ getDisplayStatus(inv) }}
                      </span>
                      <span v-if="inv.edit_count > 0" class="edit-count-chip" :title="`Edited ${inv.edit_count} time(s)`">
                        Edit #{{ inv.edit_count }}
                      </span>
                    </div>
                  </td>
                  <td class="text-center">
                    <div class="action-buttons-group">
                      <!-- View / Edit / Approve button (Lahat nasa loob na ng modal o redirect pag draft) -->
                      <button @click="openViewModal(inv)" class="action-btn view-btn" title="View / Manage Invoice">
                        <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                      </button>

                      <!-- Delete / Request Deletion button -->
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
                <tr v-if="filteredInvoices.length === 0">
                  <td :colspan="isAdminOrSuper ? 8 : 7" class="empty-data-message">No invoice records found matching your filter criteria.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ── VIEW & MANAGE MODAL (DITO NA NAG-AAAPROVE AT NAG-UUUPDATE NG STATUS) ── -->
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
                  <!-- Admin/Superadmin: pwedeng magbago ng status direkta sa dropdown dito sa loob ng modal -->
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

            <div v-if="modalForm.status === 'Paid'" class="paid-locked-banner">
              <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
              <span>Official Paid Invoice &mdash; Record is finalized and locked from further edits.</span>
            </div>

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

            <div class="vat-selection-row mt-16 flex items-center justify-between gap-4">
              <div class="form-control-group" style="max-width: 250px;">
                <label class="section-title">Tax Type</label>
                <select v-model="modalForm.vat_type" :disabled="!isModalEditable" :class="{'bg-gray-50 cursor-not-allowed': !isModalEditable}" class="form-select-dropdown font-bold">
                  <option value="vat">VAT (12%)</option>
                  <option value="non-vat">Non-VAT (0%)</option>
                  <option value="exempt">Exempt (0%)</option>
                </select>
              </div>
              <div class="form-control-group flex items-center gap-2 mt-4">
                <label class="checkbox-label-inline cursor-pointer flex items-center gap-2">
                  <input type="checkbox" v-model="modalForm.has_withholding_tax" :disabled="!isModalEditable" class="rounded text-sky-600 focus:ring-sky-500 h-3.5 w-3.5" />
                  <span class="font-semibold text-slate-600" style="font-size: 11px;">Apply Withholding Tax (2% EWT)</span>
                </label>
              </div>
            </div>

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
                  <span>{{ (modalForm.vat_type || '').toLowerCase() === 'vat' ? 'VAT (12%):' : 'Tax / Non-VAT (0%):' }}</span>
                  <span class="font-bold">₱{{ formatCurrency(modalTaxAmount) }}</span>
                </div>
                <!-- Withholding Tax Display sa Modal -->
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

            <div v-if="isAdminOrSuper && modalForm.deletion_requested" class="deletion-request-banner mt-20">
              <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
              <div>
                <strong>Deletion Requested</strong>
                <p>{{ modalForm.deletion_reason }}</p>
              </div>
            </div>
          </div>

          <div class="form-submit-row modal-footer-actions">
            <button type="button" @click="showViewModal = false" class="btn-cancel-modal">Close</button>

            <!-- Save Invoice Changes button (hidden if record is Paid or non-editable) -->
            <button
              v-if="isModalEditable"
              type="button"
              @click="saveModalChanges"
              :disabled="isSubmitting"
              class="btn-primary-action bg-emerald-600 border-emerald-600 hover:bg-emerald-700 flex-btn"
            >
              <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
              {{ isSubmitting ? 'Saving...' : 'Save Invoice Changes' }}
            </button>

            <button
              v-if="isAdminOrSuper && modalForm.deletion_requested"
              type="button"
              @click="approveDeletionFromModal"
              class="btn-primary-action bg-red-600 border-red-600 hover:bg-red-700 flex-btn"
            >
              <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
              Approve &amp; Delete
            </button>

            <!-- Dynamic Status Action Button (nagbabago depende sa napiling status) -->
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

      <!-- ── DELETE / REQUEST DELETION MODAL ──────────────────────────────── -->
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

    </div>
  </app-layout>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import axios from 'axios';
import { useToast } from 'vue-toastification';
import AppLayout from '@/Layouts/AppLayout.vue';

const toast = useToast();
const activeTab = ref('All');
const searchQuery = ref('');
const invoices = ref([]);
const clients = ref([]);
const teams = ref([]);
const selectedTeamFilter = ref('');
const isAdmin = ref(false);
const isSuperAdmin = ref(false);
const currentUserId = ref(null);

const showViewModal = ref(false);
const showDeleteModal = ref(false);
const selectedInvoiceForDeletion = ref(null);
const deletionReason = ref('');
const isSubmitting = ref(false);
const originalStatus = ref('');

const isAdminOrSuper = computed(() => isAdmin.value || isSuperAdmin.value);

const modalForm = ref({
  id: null,
  user_id: null,
  invoice_number: '',
  client_id: '',
  issue_date: '',
  due_date: '',
  status: 'Draft',
  vat_type: 'VAT',
  notes: '',
  items: [],
  subtotal: 0,
  tax_amount: 0,
  withholding_tax: 0,
  total_amount: 0,
  edit_count: 0,
  deletion_requested: false,
  deletion_reason: ''
});

const currentDate = new Date();

const availableTabs = computed(() => {
  const base = ['All', 'Paid', 'Pending Approval', 'Current (Not Due)', '1-30 Days Overdue', '31-60 Days Overdue', '60+ Days Overdue', 'Draft'];
  if (isAdminOrSuper.value) base.push('Deletion Requests');
  return base;
});

const pendingDeletionCount = computed(() => invoices.value.filter(i => i.deletion_requested).length);

onMounted(async () => {
  await fetchUserData();
  await fetchInvoices();
  await fetchClients();
  if (isAdminOrSuper.value) {
    await fetchTeams();
  }
});

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
  }
};

watch(selectedTeamFilter, () => fetchInvoices());

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

const getClientName = (clientId) => {
  const found = clients.value.find(c => c.id === clientId);
  return found ? found.client_name : 'N/A';
};

const handleImageError = (e) => { e.target.style.display = 'none'; };

const getOverdueDays = (dueDateStr) => {
  if (!dueDateStr) return 0;
  const due = new Date(dueDateStr);
  return Math.ceil((currentDate - due) / (1000 * 60 * 60 * 24));
};

const totalInvoiced = computed(() => invoices.value.reduce((sum, inv) => sum + Number(inv.total_amount || 0), 0));
const totalCollected = computed(() => invoices.value.filter(inv => inv.status === 'Paid').reduce((sum, inv) => sum + Number(inv.total_amount || 0), 0));
const outstandingBalance = computed(() => invoices.value.filter(inv => inv.status !== 'Paid' && inv.status !== 'Cancelled').reduce((sum, inv) => sum + Number(inv.total_amount || 0), 0));
const overdueAmount = computed(() => invoices.value.filter(inv => {
  const days = getOverdueDays(inv.due_date);
  return days > 0 && inv.status !== 'Paid' && inv.status !== 'Cancelled';
}).reduce((sum, inv) => sum + Number(inv.total_amount || 0), 0));

const activeTeamQuota = computed(() => {
  if (!selectedTeamFilter.value || selectedTeamFilter.value === '') {
    return teams.value.reduce((sum, t) => sum + Number(t.quota || 0), 0) || 1;
  }
  const foundTeam = teams.value.find(t => t.id == selectedTeamFilter.value);
  return foundTeam ? Number(foundTeam.quota || 1) : 1;
});

const quotaPercentage = computed(() => {
  const target = activeTeamQuota.value;
  if (target <= 0) return 0;
  return Math.round((totalCollected.value / target) * 100);
});

const formatCurrency = (val) => Number(val || 0).toLocaleString('en-US', { minimumFractionDigits: 2 });

const filteredInvoices = computed(() => {
  return invoices.value.filter(inv => {
    const daysOverdue = getOverdueDays(inv.due_date);
    const isPaidOrDraft = inv.status === 'Paid' || inv.status === 'Draft' || inv.status === 'Cancelled';

    let matchesTab = true;
    if (activeTab.value === 'Paid')                  matchesTab = inv.status === 'Paid';
    else if (activeTab.value === 'Draft')            matchesTab = inv.status === 'Draft';
    else if (activeTab.value === 'Pending Approval')  matchesTab = inv.status === 'Pending Approval';
    else if (activeTab.value === 'Current (Not Due)') matchesTab = daysOverdue <= 0 && !isPaidOrDraft;
    else if (activeTab.value === '1-30 Days Overdue') matchesTab = daysOverdue >= 1 && daysOverdue <= 30 && !isPaidOrDraft;
    else if (activeTab.value === '31-60 Days Overdue')matchesTab = daysOverdue >= 31 && daysOverdue <= 60 && !isPaidOrDraft;
    else if (activeTab.value === '60+ Days Overdue')  matchesTab = daysOverdue > 60 && !isPaidOrDraft;
    else if (activeTab.value === 'Deletion Requests') matchesTab = inv.deletion_requested === true;

    const clientNameStr = inv.client ? inv.client.client_name : (inv.client_name || '');
    const matchesSearch =
      inv.invoice_number.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      clientNameStr.toLowerCase().includes(searchQuery.value.toLowerCase());

    return matchesTab && matchesSearch;
  });
});

const getDisplayStatus = (inv) => {
  if (['Paid', 'Draft', 'Pending Approval', 'Cancelled', 'Approved'].includes(inv.status)) return inv.status;
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

// Computed totals for View Modal
const modalSubtotal = computed(() => {
  if (!modalForm.value.items || !Array.isArray(modalForm.value.items)) return 0;
  return modalForm.value.items.reduce((sum, i) => sum + (Number(i.quantity || 0) * Number(i.price || 0)), 0);
});

const modalTaxAmount = computed(() => {
  const type = (modalForm.value.vat_type || 'vat').toLowerCase();
  if (type === 'non-vat' || type === 'exempt') return 0;
  return modalSubtotal.value * 0.12;
});

const modalWhtAmount = computed(() => {
  if (!modalForm.value.has_withholding_tax) return 0;
  return modalSubtotal.value * 0.02;
});

const modalTotalAmount = computed(() => {
  return Math.max(0, modalSubtotal.value + modalTaxAmount.value - modalWhtAmount.value - (Number(modalForm.value.discount_amount) || 0));
});

const addModalItem = () => {
  if (!modalForm.value.items) modalForm.value.items = [];
  modalForm.value.items.push({ item_name: '', quantity: 1, price: 0 });
};

const removeModalItem = (index) => {
  if (modalForm.value.items && modalForm.value.items.length > 1) {
    modalForm.value.items.splice(index, 1);
  }
};

// Draft -> redirect sa create/edit page; Non-Draft -> buksan ang View Modal
const openViewModal = async (inv) => {
  if (inv.status === 'Draft') {
    window.location.href = `/invoices/create?id=${inv.id}`;
    return;
  }
  try {
    const res = await axios.get(`/api/invoices/${inv.id}`);
    const data = res.data.data || res.data;
    const sub = data.subtotal || 0;
    const tax = data.tax_amount || 0;
    const withholding = data.withholding_tax || data.withholding_tax_amount || 0;
    const inferredVatType = data.tax_type ? data.tax_type.toLowerCase() : (tax > 0 ? 'vat' : 'non-vat');

    modalForm.value = {
      id: data.id,
      user_id: data.user_id,
      invoice_number: data.invoice_number,
      client_id: data.client_id,
      issue_date: data.issue_date || '',
      due_date: data.due_date || '',
      status: data.status || 'Draft',
      vat_type: inferredVatType,
      has_withholding_tax: data.has_withholding_tax ? true : (withholding > 0),
      notes: data.notes || '',
      items: data.items && data.items.length > 0
        ? data.items.map(i => ({ item_name: i.item_name, quantity: i.quantity, price: i.price }))
        : [{ item_name: '', quantity: 1, price: 0 }],
      subtotal: sub,
      tax_amount: tax,
      withholding_tax: withholding,
      total_amount: data.total_amount || 0,
      edit_count: data.edit_count || 0,
      deletion_requested: data.deletion_requested || false,
      deletion_reason: data.deletion_reason || ''
    };
    originalStatus.value = data.status;
    showViewModal.value = true;
  } catch (error) {
    console.error('Error loading invoice details:', error);
    toast.error('Failed to load invoice details.');
  }
};

const saveModalChanges = async () => {
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
      status: modalForm.value.status
    };

    await axios.put(`/api/invoices/${modalForm.value.id}`, payload);

    if (!isAdminOrSuper.value && originalStatus.value !== 'Draft') {
      toast.success('Invoice updated! Submitted for approval (Edit count updated).');
    } else {
      toast.success('Invoice details saved successfully!');
    }

    showViewModal.value = false;
    await fetchInvoices();
  } catch (error) {
    console.error('Failed to save invoice edits:', error);
    toast.error(error.response?.data?.message || 'Failed to save invoice changes.');
  } finally {
    isSubmitting.value = false;
  }
};

const printInvoice = () => window.open(`/api/invoices/${modalForm.value.id}/pdf`, '_blank');

const canEditModalInvoice = computed(() => {
  if (!modalForm.value.id) return false;
  if (isSuperAdmin.value) return true;
  return currentUserId.value && Number(modalForm.value.user_id) === Number(currentUserId.value);
});

const isModalEditable = computed(() => {
  if (!canEditModalInvoice.value) return false;
  if (modalForm.value.status === 'Paid') return false; // Paid invoices are locked / closed records
  return true;
});

const navigateToEdit = (id) => {
  window.location.href = `/invoices/create?id=${id}`;
};

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

// Save Status Change (nag-uupdate base sa napiling status sa dropdown sa taas)
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
        reason: deletionReason.value
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

const approveDeletionFromModal = async () => {
  isSubmitting.value = true;
  try {
    await axios.delete(`/api/invoices/${modalForm.value.id}`);
    invoices.value = invoices.value.filter(i => i.id !== modalForm.value.id);
    toast.success('Invoice deleted successfully.');
    showViewModal.value = false;
  } catch (error) {
    toast.error('Failed to delete invoice.');
  } finally {
    isSubmitting.value = false;
  }
};
</script>

<style scoped>
.dashboard-page-wrapper { max-width: 1400px; margin: 0 auto; padding: 24px 32px; box-sizing: border-box; }
.mb-24 { margin-bottom: 24px; } .mb-20 { margin-bottom: 20px; } .mt-20 { margin-top: 20px; } .mt-24 { margin-top: 24px; } .mt-16 { margin-top: 16px; } .mt-12 { margin-top: 12px; } .mb-8 { margin-bottom: 8px; } .p-20 { padding: 20px 24px; }

.kpi-grid-container { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; }
.kpi-card-item { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px 24px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 1px 3px rgba(0,0,0,0.02); }
.kpi-info { display: flex; flex-direction: column; gap: 4px; }
.kpi-label { font-size: 11px; font-weight: 700; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px; }
.kpi-number { font-size: 24px; font-weight: 800; color: #0f172a; }
.kpi-icon-box { width: 40px; height: 40px; border-radius: 8px; background: #f8fafc; border: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: center; color: #64748b; }

.section-heading-title { font-size: 13px; font-weight: 700; text-transform: uppercase; color: #0f172a; margin: 0; }
.quota-progress-container { display: flex; flex-direction: column; gap: 8px; }
.quota-info-row { display: flex; justify-content: space-between; font-size: 12px; color: #475569; }
.progress-bar-track { width: 100%; height: 10px; background: #f1f5f9; border-radius: 5px; overflow: hidden; border: 1px solid #cbd5e1; }
.progress-bar-fill { height: 100%; background: linear-gradient(90deg, #3b82f6, #0284c7); border-radius: 5px; transition: width 0.4s ease; }

.analytics-card-panel { background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.02); }
.panel-header-box { background: #0f172a; padding: 20px 28px; display: flex; justify-content: space-between; align-items: center; }
.panel-header-box h3 { color: #ffffff; font-size: 15px; font-weight: 700; text-transform: uppercase; margin: 0; letter-spacing: 0.5px; }
.btn-primary-action { background: #0f172a; color: #ffffff; padding: 10px 20px; border-radius: 8px; font-size: 12px; font-weight: 700; text-decoration: none; text-transform: uppercase; transition: background 0.2s; border: 1px solid #334155; cursor: pointer; }
.btn-primary-action:hover:not(:disabled) { background: #1e293b; }
.panel-body-content { padding: 28px; }

.table-controls-row { display: flex; justify-content: space-between; align-items: center; gap: 20px; flex-wrap: wrap; }
.modern-tabs-container { display: flex; gap: 6px; background: #f8fafc; padding: 6px; border-radius: 10px; border: 1px solid #e2e8f0; flex-wrap: wrap; }
.modern-tab-btn { background: transparent; border: none; padding: 8px 12px; font-size: 11px; font-weight: 700; color: #64748b; border-radius: 6px; cursor: pointer; transition: all 0.2s ease; position: relative; display: flex; align-items: center; gap: 6px; }
.modern-tab-btn:hover { color: #0f172a; background: #f1f5f9; }
.modern-tab-btn.active { background: #0f172a; color: #ffffff; }
.tab-badge { background: #dc2626; color: #fff; font-size: 9px; font-weight: 800; padding: 2px 6px; border-radius: 10px; min-width: 16px; text-align: center; }

.team-filter-box { display: flex; align-items: center; }
.search-input-box { display: flex; align-items: center; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 14px; width: 260px; height: 40px; }
.search-icon { stroke: #64748b; margin-right: 10px; flex-shrink: 0; }
.search-input-box input { border: none; background: transparent; outline: none; font-size: 13px; color: #1e293b; width: 100%; }

.items-table-frame { border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden; }
.enterprise-data-table { width: 100%; border-collapse: collapse; text-align: left; }
.enterprise-data-table th { background: #f8fafc; padding: 14px 18px; font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; letter-spacing: 0.5px; }
.enterprise-data-table td { padding: 16px 18px; border-bottom: 1px solid #e2e8f0; font-size: 13px; color: #334155; vertical-align: middle; }
.deletion-pending-row { background: #fff7f7 !important; }
.deletion-pending-row td { border-left: 3px solid #dc2626; }

.font-bold { font-weight: 700; }
.text-slate-900 { color: #0f172a; font-size: 13px; }
.text-center { text-align: center; }
.text-right { text-align: right; }
.text-sky-400 { color: #38bdf8; }
.text-sky-700 { color: #0369a1; }
.empty-data-message { text-align: center; color: #94a3b8; padding: 40px !important; font-size: 13px; }
.team-badge { background: #f1f5f9; color: #334155; padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; border: 1px solid #cbd5e1; }
.flex-between-box { display: flex; justify-content: space-between; align-items: center; }

.status-cell { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
.edit-count-chip { background: linear-gradient(135deg, #f59e0b, #d97706); color: #fff; font-size: 9px; font-weight: 800; padding: 2px 7px; border-radius: 10px; text-transform: uppercase; letter-spacing: 0.3px; white-space: nowrap; }
.deletion-flag-icon { margin-left: 4px; font-size: 12px; }
.deletion-pending-label { font-size: 10px; color: #d97706; font-weight: 700; white-space: nowrap; }

.status-pill { padding: 4px 10px; border-radius: 6px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; display: inline-block; white-space: nowrap; }
.status-pill-container { display: flex; align-items: center; height: 38px; }
.pill-success  { background: #d1fae5; color: #059669; }
.pill-warning  { background: #fef3c7; color: #d97706; }
.pill-danger   { background: #fee2e2; color: #dc2626; }
.pill-info     { background: #e0f2fe; color: #0284c7; }
.pill-approved { background: #ede9fe; color: #6d28d9; }
.pill-cancelled{ background: #f1f5f9; color: #64748b; }

.bg-gray-50 { background-color: #f8fafc !important; color: #334155 !important; }

.action-buttons-group { display: flex; justify-content: center; gap: 6px; align-items: center; flex-wrap: wrap; }
.action-btn { width: 32px; height: 32px; border-radius: 6px; border: 1px solid #cbd5e1; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s; background: #ffffff; color: #334155; flex-shrink: 0; }
.action-btn:hover { background: #f1f5f9; border-color: #94a3b8; color: #0f172a; }
.view-btn:hover    { color: #2563eb; border-color: #2563eb; }
.delete-btn:hover  { color: #dc2626; border-color: #dc2626; }

/* Modal */
.modal-backdrop { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.65); display: flex; align-items: center; justify-content: center; z-index: 1000; backdrop-filter: blur(2px); }
.invoice-modal-card { background: #ffffff; width: 900px; max-height: 92vh; border-radius: 12px; overflow-y: auto; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); display: flex; flex-direction: column; }
.modal-header-bar { background: #0f172a; padding: 18px 28px; display: flex; justify-content: space-between; align-items: center; color: #ffffff; }
.modal-header-left { display: flex; align-items: center; gap: 12px; }
.modal-header-bar h3 { font-size: 14px; font-weight: 700; text-transform: uppercase; margin: 0; letter-spacing: 0.5px; }
.modal-edit-badge { background: linear-gradient(135deg, #f59e0b, #d97706); color: #fff; font-size: 10px; font-weight: 800; padding: 3px 10px; border-radius: 20px; text-transform: uppercase; }
.header-modal-actions { display: flex; align-items: center; gap: 12px; }
.btn-print-action { background: #0284c7; color: #ffffff; border: none; padding: 6px 14px; border-radius: 6px; font-size: 11px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 6px; text-transform: uppercase; transition: background 0.2s; }
.btn-print-action:hover { background: #0369a1; }
.btn-close-modal { background: none; border: none; color: #ffffff; font-size: 24px; cursor: pointer; }

.invoice-paper-content { padding: 36px; background: #ffffff; }
.invoice-header-section { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #e2e8f0; padding-bottom: 24px; }
.company-brand-group { display: flex; align-items: center; gap: 16px; }
.invoice-company-logo { width: 50px; height: 50px; object-fit: contain; border-radius: 6px; }
.company-brand h2 { font-size: 16px; font-weight: 800; color: #0f172a; margin: 0; letter-spacing: 0.5px; }
.company-brand p { font-size: 11px; color: #64748b; margin: 2px 0 0 0; }
.invoice-meta-grid { display: grid; grid-template-columns: 140px 140px; gap: 12px; }

.invoice-parties-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; background: #f8fafc; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0; align-items: flex-end; }
.dates-inline-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.section-title { font-size: 11px; font-weight: 700; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px; display: block; margin-bottom: 6px; }

.modal-items-table { width: 100%; border-collapse: collapse; }
.modal-items-table th { background: #f1f5f9; padding: 12px 14px; font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; border: 1px solid #cbd5e1; letter-spacing: 0.5px; }
.modal-items-table td { padding: 10px 14px; border: 1px solid #cbd5e1; vertical-align: middle; }
.modal-items-table input { width: 100%; border: none; background: transparent; outline: none; font-size: 13px; color: #1e293b; padding: 4px 0; }

.invoice-summary-box { display: grid; grid-template-columns: 1.4fr 1fr; gap: 28px; align-items: flex-start; border-top: 2px solid #e2e8f0; padding-top: 24px; }
.invoice-summary-box textarea { width: 100%; border: 1px solid #cbd5e1; border-radius: 8px; padding: 12px; font-size: 12px; outline: none; box-sizing: border-box; background: #f8fafc; color: #1e293b; resize: none; }
.totals-calculation-box { display: flex; flex-direction: column; gap: 10px; background: #f8fafc; padding: 18px; border-radius: 8px; border: 1px solid #e2e8f0; }
.total-row { display: flex; justify-content: space-between; align-items: center; font-size: 13px; color: #475569; }
.total-row.grand-total { font-size: 15px; font-weight: 800; color: #0f172a; border-top: 1px solid #cbd5e1; padding-top: 10px; }

.deletion-request-banner { display: flex; align-items: flex-start; gap: 12px; background: #fff7ed; border: 1px solid #fed7aa; border-radius: 8px; padding: 14px 16px; }
.deletion-request-banner svg { flex-shrink: 0; stroke: #d97706; margin-top: 2px; }
.deletion-request-banner strong { font-size: 12px; font-weight: 700; color: #c2410c; text-transform: uppercase; display: block; margin-bottom: 4px; }
.deletion-request-banner p { font-size: 12px; color: #7c3500; margin: 0; }

.modal-footer-actions { background: #f8fafc; padding: 18px 36px; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 12px; margin: 0; }
.btn-cancel-modal { background: #f1f5f9; color: #475569; padding: 10px 18px; border-radius: 8px; font-size: 11px; font-weight: 700; border: none; cursor: pointer; text-transform: uppercase; transition: background 0.2s; }
.btn-cancel-modal:hover { background: #e2e8f0; }

.confirm-card-container { background: #ffffff; width: 440px; border-radius: 12px; padding: 32px; text-align: center; box-shadow: 0 20px 30px rgba(0,0,0,0.2); }
.confirm-icon-box { width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px auto; }
.confirm-card-container h3 { font-size: 16px; font-weight: 800; color: #0f172a; margin: 0 0 8px 0; text-transform: uppercase; }
.confirm-card-container p { font-size: 13px; color: #64748b; margin: 0 0 16px 0; line-height: 1.5; text-align: left; }
.confirm-buttons-row { display: flex; justify-content: center; gap: 12px; margin-top: 20px; }

.form-control-group { display: flex; flex-direction: column; width: 100%; }
.form-control-group label { font-size: 10px; font-weight: 700; text-transform: uppercase; color: #64748b; margin-bottom: 4px; letter-spacing: 0.5px; }

.form-control-input {
  width: 100%;
  padding: 9px 12px;
  border: 1px solid #cbd5e1;
  border-radius: 6px;
  font-size: 12px;
  background: #ffffff;
  outline: none;
  box-sizing: border-box;
  color: #1e293b;
}

.form-select-dropdown {
  width: 100%;
  padding: 9px 36px 9px 12px;
  border: 1px solid #cbd5e1;
  border-radius: 6px;
  font-size: 12px;
  background-color: #ffffff;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%23475569' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 12px center;
  background-size: 12px;
  -webkit-appearance: none;
  -moz-appearance: none;
  appearance: none;
  outline: none;
  box-sizing: border-box;
  color: #1e293b;
  cursor: pointer;
  transition: border-color 0.2s, box-shadow 0.2s;
}

.form-select-dropdown:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.12);
}

.flex-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.deletion-flag-badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  background: #fee2e2;
  color: #dc2626;
  font-size: 10px;
  font-weight: 700;
  padding: 2px 7px;
  border-radius: 6px;
  margin-left: 6px;
  border: 1px solid #fca5a5;
  text-transform: uppercase;
}

.deletion-pending-badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  background: #fef3c7;
  color: #d97706;
  font-size: 11px;
  font-weight: 700;
  padding: 3px 8px;
  border-radius: 6px;
  border: 1px solid #fcd34d;
}

.bg-red-600 { background: #dc2626 !important; border-color: #dc2626 !important; }
.bg-emerald-600 { background: #059669 !important; border-color: #059669 !important; }

.text-xs { font-size: 12px; }
.text-slate-500 { color: #64748b; }
.vat-selection-row { display: flex; }
.text-emerald-600 { color: #059669; }
.text-sky-600 { color: #0284c7; }
.text-red-600 { color: #dc2626; }
.form-submit-row { display: flex; justify-content: flex-end; gap: 12px; }
.mb-12 { margin-bottom: 12px; }

/* Compact paid locked banner */
.paid-locked-banner {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  margin-top: 14px;
  padding: 5px 12px;
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  border-radius: 6px;
  color: #15803d;
  font-size: 11px;
  font-weight: 600;
  letter-spacing: 0.2px;
}
</style>