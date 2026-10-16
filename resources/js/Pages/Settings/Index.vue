<template>
  <app-layout>
    <div class="dashboard-page-wrapper">
      
      <!-- HERO HEADER BAR -->
      <div class="welcome-hero-banner mb-24">
        <div class="hero-text-content">
          <h1>System &amp; Enterprise Settings</h1>
          <p>Configure official corporate details, default billing rules, signatory credentials, and system governance.</p>
        </div>
      </div>

      <div v-if="!isAdmin" class="analytics-card-panel p-24 text-center">
        <svg class="mx-auto mb-12 text-amber-500" viewBox="0 0 24 24" width="48" height="48" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
        <h3 class="text-lg font-bold text-slate-800 mb-4">Access Restricted &mdash; Admin Only</h3>
        <p class="text-sm text-slate-600 mb-16">System &amp; Enterprise Settings are restricted to Administrator accounts. Regular team members cannot modify corporate configurations.</p>
        <a href="/dashboard" class="btn-primary-action inline-block">Return to Dashboard</a>
      </div>

      <!-- 2-COLUMN SETTINGS LAYOUT -->
      <div v-else class="settings-grid-layout">
        
        <!-- LEFT SIDEBAR TAB NAVIGATION -->
        <div class="settings-sidebar-nav">
          <div class="nav-card-container">
            <div class="nav-card-header">Configuration Modules</div>
            <nav class="nav-links-list">
              <button 
                @click="activeTab = 'Company Profile & Signatory'"
                :class="['settings-nav-item', { active: activeTab === 'Company Profile & Signatory' }]"
              >
                <div class="nav-icon">
                  <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18"></path><path d="M9 8h1"></path><path d="M9 12h1"></path><path d="M9 16h1"></path><path d="M14 8h1"></path><path d="M14 12h1"></path><path d="M14 16h1"></path><path d="M5 21V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16"></path></svg>
                </div>
                <div class="nav-text">
                  <span class="nav-title">Company &amp; Signatory</span>
                  <small class="nav-subtitle">Identity, TIN, address &amp; PDF signature</small>
                </div>
              </button>

              <button 
                @click="activeTab = 'Billing & Tax Defaults'"
                :class="['settings-nav-item', { active: activeTab === 'Billing & Tax Defaults' }]"
              >
                <div class="nav-icon">
                  <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
                </div>
                <div class="nav-text">
                  <span class="nav-title">Billing &amp; Tax Defaults</span>
                  <span class="nav-subtitle">VAT, EWT %, payment terms &amp; currency</span>
                </div>
              </button>

              <button 
                @click="activeTab = 'System Governance'"
                :class="['settings-nav-item', { active: activeTab === 'System Governance' }]"
              >
                <div class="nav-icon">
                  <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                </div>
                <div class="nav-text">
                  <span class="nav-title">System Governance</span>
                  <span class="nav-subtitle">Deletion approvals &amp; alerts</span>
                </div>
              </button>
            </nav>
          </div>

          <!-- SYSTEM INFO WIDGET -->
          <div class="system-info-widget mt-20">
            <div class="info-widget-title">System Health &amp; Version</div>
            <div class="info-row mt-8">
              <span>Platform:</span>
              <strong class="text-slate-800">Billing Pro v2.4</strong>
            </div>
            <div class="info-row mt-4">
              <span>Database:</span>
              <strong class="text-emerald-600">Connected (MySQL)</strong>
            </div>
          </div>
        </div>

        <!-- RIGHT MAIN FORM PANEL -->
        <div class="settings-main-panel">
          <div class="panel-card-wrapper">
            
            <form @submit.prevent="saveSettings">
              
              <!-- TAB 1: COMPANY PROFILE & SIGNATORY -->
              <div v-if="activeTab === 'Company Profile & Signatory'" class="tab-pane-content">
                <div class="pane-header-box mb-24">
                  <h3>Corporate Identity &amp; Invoice Header Profile</h3>
                  <p>These details are automatically printed on official commercial invoices, delivery receipts, and PDF statements.</p>
                </div>

                <div class="form-section-card mb-24">
                  <h4 class="card-section-label">Official Registered Business Info</h4>
                  <div class="form-grid-2col mb-20">
                    <div class="form-control-group">
                      <label>Registered Company Name</label>
                      <input v-model="form.company_name" type="text" class="form-control-input" required />
                    </div>
                    <div class="form-control-group">
                      <label>VAT Registered TIN Number</label>
                      <input v-model="form.vat_tin" type="text" class="form-control-input" placeholder="000-000-000-00000" />
                    </div>
                  </div>

                  <div class="form-control-group mb-20">
                    <label>Complete Office / Billing Address</label>
                    <textarea v-model="form.company_address" rows="2" class="form-control-input" placeholder="Complete address..."></textarea>
                  </div>

                  <div class="form-grid-2col">
                    <div class="form-control-group">
                      <label>Corporate Contact Email</label>
                      <input v-model="form.contact_email" type="email" class="form-control-input" placeholder="billing@company.com" />
                    </div>
                    <div class="form-control-group">
                      <label>Corporate Telephone / Landline</label>
                      <input v-model="form.contact_phone" type="text" class="form-control-input" placeholder="(02) 8000-0000" />
                    </div>
                  </div>
                </div>

                <div class="form-section-card">
                  <h4 class="card-section-label">Official PDF Signatory Profile</h4>
                  <div class="form-grid-2col">
                    <div class="form-control-group">
                      <label>Authorized Signatory Name</label>
                      <input v-model="form.signatory_name" type="text" class="form-control-input" placeholder="Full name..." />
                    </div>
                    <div class="form-control-group">
                      <label>Official Position / Title</label>
                      <input v-model="form.signatory_title" type="text" class="form-control-input" placeholder="Finance Manager..." />
                    </div>
                  </div>
                </div>
              </div>

              <!-- TAB 2: BILLING & TAX DEFAULTS -->
              <div v-if="activeTab === 'Billing & Tax Defaults'" class="tab-pane-content">
                <div class="pane-header-box mb-24">
                  <h3>Default Billing Presets &amp; Tax Formulas</h3>
                  <p>Configure default values pre-filled when users create new invoices across the organization.</p>
                </div>

                <div class="form-section-card mb-24">
                  <h4 class="card-section-label">Tax &amp; Payment Term Presets</h4>
                  <div class="form-grid-2col mb-20">
                    <div class="form-control-group">
                      <label>Default Tax Type</label>
                      <select v-model="form.default_tax_type" class="form-select-dropdown">
                        <option value="vat">VAT (Value Added Tax 12%)</option>
                        <option value="non-vat">Non-VAT (0%)</option>
                        <option value="exempt">Exempt (0%)</option>
                      </select>
                    </div>
                    <div class="form-control-group">
                      <label>Default Payment Terms (Days)</label>
                      <select v-model="form.default_payment_terms" class="form-select-dropdown">
                        <option value="15">15 Days</option>
                        <option value="30">30 Days</option>
                        <option value="45">45 Days</option>
                        <option value="60">60 Days</option>
                      </select>
                    </div>
                  </div>

                  <div class="form-grid-3col">
                    <div class="form-control-group">
                      <label>VAT Rate (%)</label>
                      <input v-model.number="form.default_tax_rate" type="number" step="any" class="form-control-input" />
                    </div>
                    <div class="form-control-group">
                      <label>Withholding Tax Rate (%)</label>
                      <input v-model.number="form.default_withholding_tax" type="number" step="any" class="form-control-input" />
                    </div>
                    <div class="form-control-group">
                      <label>Currency Symbol</label>
                      <input v-model="form.currency_symbol" type="text" class="form-control-input" />
                    </div>
                  </div>
                </div>
              </div>

              <!-- TAB 3: SYSTEM GOVERNANCE -->
              <div v-if="activeTab === 'System Governance'" class="tab-pane-content">
                <div class="pane-header-box mb-24">
                  <h3>Security &amp; Workflow Governance</h3>
                  <p>Control admin approval requirements and automated alert notifications.</p>
                </div>

                <div class="form-section-card space-y-4">
                  <div class="setting-toggle-row">
                    <div class="toggle-text-info">
                      <strong>Require Admin Approval for Invoice Deletions</strong>
                      <p>Non-admin users must submit deletion requests with justifications for admin review.</p>
                    </div>
                    <input type="checkbox" v-model="form.require_deletion_approval" class="toggle-input" />
                  </div>

                  <div class="setting-toggle-row">
                    <div class="toggle-text-info">
                      <strong>Automated In-App Alerts</strong>
                      <p>Receive notifications when invoices are edited or submitted for approval.</p>
                    </div>
                    <input type="checkbox" v-model="form.enable_email_notifications" class="toggle-input" />
                  </div>
                </div>
              </div>

              <!-- STICKY ACTION FOOTER -->
              <div class="panel-action-footer mt-28">
                <div class="status-saved-text" v-if="lastSavedTime">
                  Last saved: {{ lastSavedTime }}
                </div>
                <div v-else></div>
                
                <button type="submit" :disabled="isSaving" class="btn-primary-save">
                  <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                  <span>{{ isSaving ? 'Saving Configurations...' : 'Save Configuration Changes' }}</span>
                </button>
              </div>

            </form>

          </div>
        </div>

      </div>

    </div>
  </app-layout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { useToast } from 'vue-toastification';
import AppLayout from '@/Layouts/AppLayout.vue';

const toast = useToast();
const activeTab = ref('Company Profile & Signatory');
const isSaving = ref(false);
const lastSavedTime = ref('');
const isAdmin = ref(true);

const fetchUserData = async () => {
  try {
    const res = await axios.get('/api/user');
    const user = res.data.user || res.data;
    const role = user?.role?.toLowerCase().trim() ?? '';
    isAdmin.value = role === 'admin' || role === 'superadmin';
  } catch (e) {
    console.error('Failed to load user info:', e);
  }
};

const form = ref({
  company_name: 'LENDELL OUTSOURCING SOLUTIONS, INC.',
  company_address: 'UNIT GF 10, CITYNET 1, 183 EDSA WACK-WACK GREENHILLS, MANDALUYONG CITY',
  vat_tin: '008-797-768-00000',
  contact_email: 'billing@lendell.ph',
  contact_phone: '(02) 8123-4567',
  signatory_name: 'LOIDA CARINO',
  signatory_title: 'Finance & Accounting Manager',
  default_payment_terms: '30',
  default_tax_type: 'vat',
  default_tax_rate: 12,
  default_withholding_tax: 2,
  currency_symbol: '₱',
  enable_email_notifications: true,
  require_deletion_approval: true,
});

const fetchSettings = async () => {
  try {
    const res = await axios.get('/api/settings');
    const data = res.data.data || {};
    form.value = {
      company_name: data.company_name || form.value.company_name,
      company_address: data.company_address || form.value.company_address,
      vat_tin: data.vat_tin || form.value.vat_tin,
      contact_email: data.contact_email || form.value.contact_email,
      contact_phone: data.contact_phone || form.value.contact_phone,
      signatory_name: data.signatory_name || form.value.signatory_name,
      signatory_title: data.signatory_title || form.value.signatory_title,
      default_payment_terms: data.default_payment_terms || '30',
      default_tax_type: data.default_tax_type || 'vat',
      default_tax_rate: Number(data.default_tax_rate || 12),
      default_withholding_tax: Number(data.default_withholding_tax || 2),
      currency_symbol: data.currency_symbol || '₱',
      enable_email_notifications: data.enable_email_notifications == '1' || data.enable_email_notifications === true,
      require_deletion_approval: data.require_deletion_approval == '1' || data.require_deletion_approval === true,
    };
  } catch (error) {
    console.error('Failed to load settings:', error);
  }
};

const saveSettings = async () => {
  isSaving.value = true;
  try {
    await axios.put('/api/settings', form.value);
    lastSavedTime.value = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    toast.success('Enterprise system configurations saved successfully!');
  } catch (error) {
    console.error('Failed to save settings:', error);
    toast.error('Failed to save settings.');
  } finally {
    isSaving.value = false;
  }
};

onMounted(async () => {
  await fetchUserData();
  if (isAdmin.value) {
    await fetchSettings();
  }
});
</script>

<style scoped>
.dashboard-page-wrapper { max-width: 1400px; margin: 0 auto; padding: 24px 32px; box-sizing: border-box; }
.mb-24 { margin-bottom: 24px; }
.mb-20 { margin-bottom: 20px; }
.mt-20 { margin-top: 20px; }
.mt-28 { margin-top: 28px; }
.mt-8 { margin-top: 8px; }
.mt-4 { margin-top: 4px; }

.welcome-hero-banner { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px 28px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }
.hero-text-content h1 { font-size: 20px; font-weight: 800; color: #0f172a; margin: 0 0 6px 0; letter-spacing: -0.5px; }
.hero-text-content p { font-size: 13px; color: #64748b; margin: 0; }

.settings-grid-layout { display: grid; grid-template-columns: 300px 1fr; gap: 24px; align-items: start; }

.nav-card-container { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
.nav-card-header { background: #f8fafc; padding: 14px 18px; font-size: 11px; font-weight: 700; text-transform: uppercase; color: #64748b; border-bottom: 1px solid #e2e8f0; letter-spacing: 0.5px; }
.nav-links-list { display: flex; flex-direction: column; padding: 8px; }

.settings-nav-item { display: flex; align-items: center; gap: 12px; width: 100%; padding: 12px 14px; border-radius: 8px; border: none; background: transparent; text-align: left; cursor: pointer; transition: all 0.2s ease; }
.settings-nav-item:hover { background: #f1f5f9; }
.settings-nav-item.active { background: #0f172a; color: #ffffff; }
.settings-nav-item.active .nav-subtitle { color: #94a3b8; }
.settings-nav-item.active .nav-icon { color: #38bdf8; }

.nav-icon { color: #64748b; display: flex; align-items: center; }
.nav-text { display: flex; flex-direction: column; }
.nav-title { font-size: 13px; font-weight: 700; }
.nav-subtitle { font-size: 11px; color: #64748b; margin-top: 2px; }

.system-info-widget { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; font-size: 12px; }
.info-widget-title { font-weight: 700; color: #0f172a; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px; }
.info-row { display: flex; justify-content: space-between; font-size: 12px; color: #64748b; }

.panel-card-wrapper { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 28px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }

.pane-header-box h3 { font-size: 16px; font-weight: 800; color: #0f172a; margin: 0 0 4px 0; }
.pane-header-box p { font-size: 13px; color: #64748b; margin: 0; }

.form-section-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 20px; }
.card-section-label { font-size: 11px; font-weight: 800; text-transform: uppercase; color: #0f172a; letter-spacing: 0.5px; margin: 0 0 16px 0; }

.form-grid-2col { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 20px; }
.form-grid-3col { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 20px; }

.form-control-group { display: flex; flex-direction: column; width: 100%; }
.form-control-group label { font-size: 11px; font-weight: 700; text-transform: uppercase; color: #64748b; margin-bottom: 6px; letter-spacing: 0.5px; }
.form-control-input, .form-select-dropdown { width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px; outline: none; background: #ffffff; color: #1e293b; transition: border-color 0.2s; }
.form-control-input:focus, .form-select-dropdown:focus { border-color: #0284c7; box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.1); }

.setting-toggle-row { display: flex; justify-content: space-between; align-items: center; background: #ffffff; border: 1px solid #cbd5e1; padding: 16px; border-radius: 8px; }
.toggle-text-info strong { font-size: 13px; color: #0f172a; display: block; }
.toggle-text-info p { font-size: 12px; color: #64748b; margin: 2px 0 0 0; }
.toggle-input { width: 20px; height: 20px; cursor: pointer; accent-color: #0284c7; }

.panel-action-footer { border-top: 1px solid #e2e8f0; padding-top: 20px; display: flex; justify-content: space-between; align-items: center; }
.status-saved-text { font-size: 12px; color: #64748b; font-weight: 600; }

.btn-primary-save { display: inline-flex; align-items: center; gap: 8px; background: #0f172a; color: #ffffff; padding: 12px 24px; border-radius: 8px; font-size: 12px; font-weight: 700; text-transform: uppercase; border: none; cursor: pointer; transition: background 0.2s; letter-spacing: 0.5px; }
.btn-primary-save:hover { background: #1e293b; }

@media (max-width: 900px) {
  .settings-grid-layout { grid-template-columns: 1fr; }
}
</style>