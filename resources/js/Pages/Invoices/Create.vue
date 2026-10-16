<template>
  <app-layout>
    <div class="dashboard-page-wrapper">
      
      <!-- HERO HEADER -->
      <div class="welcome-hero-banner mb-24">
        <div class="hero-text-content">
          <div class="hero-title-badge">
            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
            <span>{{ isEditing ? 'INVOICE REVISION' : 'INVOICE CREATION' }}</span>
          </div>
          <h1>{{ isEditing ? 'Edit / Continue Draft Invoice' : 'Create Service Invoice' }}</h1>
          <p>Generate compliant service invoices, calculate VAT &amp; withholding taxes, and dispatch client billing records.</p>
        </div>
        <a href="/invoices" class="btn-hero-action secondary">
          <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
          Back to Invoices
        </a>
      </div>

      <div class="analytics-card-panel">
        <div class="panel-header-box flex-between-box">
          <div class="header-title-group">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" class="text-amber-400"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
            <h3>{{ isEditing ? 'Edit Invoice Record' : 'Invoice Billing Details & Line Items' }}</h3>
            <span v-if="isEditing && invoiceEditCount > 0" class="edit-count-badge">
              Edit #{{ invoiceEditCount }}
            </span>
          </div>
          <span class="text-xs font-bold text-amber-400">
            {{ form.invoice_number ? '#' + form.invoice_number : 'New Draft' }}
          </span>
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
            
            <div class="form-control-group" ref="clientDropdownRef">
              <label>Client / Company</label>
              <div class="client-search-wrapper">
                <div class="client-search-input-box" :class="{'is-disabled': isEditing && !isSuperAdmin, 'has-selected': form.client_id}">
                  <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" class="search-field-icon">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                  </svg>
                  <input
                    type="text"
                    v-model="clientSearchQuery"
                    @focus="handleClientInputFocus"
                    @input="showClientDropdown = true"
                    :placeholder="form.client_id ? 'Search or change client...' : 'Search client name, TIN, or contact...'"
                    :readonly="isEditing && !isSuperAdmin"
                    :disabled="isEditing && !isSuperAdmin"
                    class="client-search-input"
                  />
                  <button
                    v-if="form.client_id && (!isEditing || isSuperAdmin)"
                    type="button"
                    @click="clearClientSelection"
                    class="btn-clear-client"
                    title="Clear selected client"
                  >
                    &times;
                  </button>
                  <span v-else-if="!isEditing || isSuperAdmin" class="dropdown-chevron" @click="showClientDropdown = !showClientDropdown">
                    <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"></polyline></svg>
                  </span>
                </div>

                <!-- Suggestions Dropdown -->
                <div v-if="showClientDropdown && (!isEditing || isSuperAdmin)" class="client-suggestions-menu">
                  <div class="suggestions-header" v-if="clients.length > 0">
                    <span>SUGGESTED CLIENTS ({{ filteredSuggestedClients.length }})</span>
                  </div>
                  <div class="suggestions-list-scroll">
                    <div
                      v-for="c in filteredSuggestedClients"
                      :key="c.id"
                      @mousedown.prevent="selectClient(c)"
                      class="suggestion-client-item"
                      :class="{'is-selected': c.id === form.client_id}"
                    >
                      <div class="suggestion-item-main">
                        <span class="suggestion-name">{{ c.client_name }}</span>
                        <span v-if="c.tin_number" class="suggestion-tin">TIN: {{ c.tin_number }}</span>
                      </div>
                      <div class="suggestion-item-sub" v-if="c.contact_person || c.client_address">
                        <span v-if="c.contact_person">{{ c.contact_person }}</span>
                        <span v-if="c.contact_person && c.client_address"> &bull; </span>
                        <span v-if="c.client_address" class="truncate-address">{{ c.client_address }}</span>
                      </div>
                    </div>
                    <div v-if="filteredSuggestedClients.length === 0" class="no-suggestions-state">
                      No clients found matching "{{ clientSearchQuery }}".
                    </div>
                  </div>
                </div>
              </div>

              <!-- Hidden input for required validation -->
              <input type="hidden" :value="form.client_id" required />

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
                  <option value="vat_exclusive">VAT Exclusive (+12% VAT)</option>
                  <option value="vat_inclusive">VAT Inclusive (Incl. 12% VAT)</option>
                  <option value="vat_exempt">VAT Exempt (0% Tax)</option>
                </select>
              </div>

              <!-- Pinapakita natin ang Vatable Sales para malinaw pag inclusive -->
              <div class="calc-row-item" v-if="form.tax_type === 'vat_inclusive'">
                <span class="text-xs text-slate-400">Vatable Sales (Net of VAT):</span>
                <span class="text-xs text-slate-400">₱{{ formatCurrency(vatableSales) }}</span>
              </div>

              <div class="calc-row-item" v-if="form.tax_type !== 'vat_exempt'">
                <span>{{ form.tax_type === 'vat_inclusive' ? 'VAT Amount (Included)' : 'Add: VAT (12%)' }}:</span>
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
import AppLayout from '@/Layouts/AppLayout.vue';
import { useInvoiceCreate } from '@/composables/useInvoiceCreate';
import './Create.css';

const {
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
  cancel, submitInvoice
} = useInvoiceCreate();
</script>