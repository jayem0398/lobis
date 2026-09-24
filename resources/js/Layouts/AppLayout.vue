<template>
  <div class="enterprise-layout-wrapper" @click="closeAllDropdowns">
    
    <aside class="enterprise-sidebar">
      <div class="sidebar-brand-container">
        <div class="brand-icon-box">
          <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
        </div>
        <span class="brand-title-text">BILLING PRO</span>
      </div>

      <nav class="sidebar-navigation-links">
        <div class="nav-category-header">Main Overview</div>
        <a href="/dashboard" class="nav-item-link" :class="{ active: currentPath.includes('/dashboard') }">
          <svg class="nav-item-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
          <span>Dashboard</span>
        </a>

        <div class="nav-category-header mt-4">Sales & Billing</div>
        <a href="/invoices" class="nav-item-link" :class="{ active: currentPath === '/invoices' }">
          <svg class="nav-item-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
          <span>Service Invoices</span>
        </a>
        <a href="/invoices/create" class="nav-item-link" :class="{ active: currentPath.includes('/invoices/create') }">
          <svg class="nav-item-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
          <span>Create New Invoice</span>
        </a>

        <div class="nav-category-header mt-4">Directory & Catalog</div>
        <a href="/clients" class="nav-item-link" :class="{ active: currentPath.includes('/clients') }">
          <svg class="nav-item-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
          <span>Clients / Companies</span>
        </a>

        <template v-if="isAdmin">
          <div class="nav-category-header mt-4">Team & Administration</div>
          <a href="/teams" class="nav-item-link" :class="{ active: currentPath.includes('/teams') }">
            <svg class="nav-item-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
            <span>Teams Management</span>
          </a>
          <a href="/users" class="nav-item-link" :class="{ active: currentPath.includes('/users') }">
            <svg class="nav-item-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
            <span>Users &amp; Roles</span>
          </a>
          <a href="/activity-logs" class="nav-item-link" :class="{ active: currentPath.includes('/activity-logs') }">
            <svg class="nav-item-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
            <span>Activity &amp; User Logs</span>
          </a>
        </template>

        <div class="nav-category-header mt-4">Reports & System</div>
        <a href="/reports" class="nav-item-link" :class="{ active: currentPath.includes('/reports') }">
          <svg class="nav-item-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
          <span>Financial Reports</span>
        </a>
        <a href="/settings" class="nav-item-link" :class="{ active: currentPath.includes('/settings') }">
          <svg class="nav-item-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1 z"></path></svg>
          <span>System Settings</span>
        </a>
      </nav>
    </aside>

    <div class="enterprise-content-container">
      <header class="enterprise-header-bar">
        
        <div class="header-search-container" @click.stop>
          <div class="header-search-box">
            <svg class="search-icon" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            <input 
              v-model="searchQuery" 
              @input="onSearchInput"
              @keyup.enter="performSearchRedirect"
              type="text" 
              placeholder="Search invoices, clients from database..." 
            />
            <button v-if="searchQuery" @click="clearSearch" class="clear-search-btn">&times;</button>
          </div>

          <div v-if="showSearchDropdown && searchQuery.trim().length > 0" class="search-results-dropdown">
            <div class="search-dropdown-header">Database Search Results ({{ searchResults.length }})</div>
            <div v-if="searchResults.length > 0">
              <a 
                v-for="item in searchResults" 
                :key="item.id" 
                :href="item.link" 
                class="search-result-item"
              >
                <span class="result-type-tag">{{ item.type }}</span>
                <span class="result-text" v-html="highlightMatch(item.title, searchQuery)"></span>
              </a>
            </div>
            <div v-else class="search-no-result">No matching records found in database</div>
          </div>
        </div>

        <div class="header-utilities-box">
          <div class="dropdown-wrapper" @click.stop>
            <button @click="toggleNotifications" class="header-icon-btn" title="Notifications">
              <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
              <span class="notification-badge" v-if="unreadCount > 0"></span>
            </button>

            <div v-if="showNotifications" class="dropdown-menu-box notification-box">
              <div class="dropdown-menu-header">
                <span>Notifications <span v-if="unreadCount > 0" class="notif-count-badge">{{ unreadCount }}</span></span>
                <button @click="markAllAsRead" class="mark-read-btn">Mark all read</button>
              </div>
              <div class="dropdown-items-list">
                <div v-if="notifications.length === 0" class="notif-empty-state">
                  <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="#cbd5e1" stroke-width="1.5"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                  <p>No notifications yet</p>
                </div>
                <div v-for="notif in notifications" :key="notif.id" class="dropdown-item-notification" :class="{ unread: !notif.read }">
                  <div class="notif-dot" v-if="!notif.read"></div>
                  <div class="notif-content">
                    <p class="notif-title">{{ notif.title }}</p>
                    <small class="notif-time">{{ notif.time }}</small>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="header-divider"></div>

          <div class="dropdown-wrapper" @click.stop>
            <div class="user-profile-trigger" @click="toggleProfileMenu">
              <div class="user-info-text">
                <span class="user-fullname-text">{{ user.name || 'Loading...' }}</span>
                <span class="user-role-text">{{ user.role || 'Member' }}</span>
              </div>
              <img :src="`https://ui-avatars.com/api/?name=${encodeURIComponent(user.name || 'User')}&background=1e1e2d&color=fff`" alt="User Avatar" class="user-avatar-image" />
            </div>

            <div v-if="showProfileMenu" class="dropdown-menu-box profile-box">
              <div class="profile-menu-header">
                <span class="profile-name">{{ user.name || 'User' }}</span>
                <span class="profile-email">{{ user.email || '' }}</span>
              </div>
              <div class="dropdown-divider"></div>
              
              <a href="/settings" class="dropdown-menu-link">
                <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1 z"></path></svg>
                System Settings
              </a>

              <div class="dropdown-divider"></div>
              
              <button @click="handleLogout" class="dropdown-menu-link logout-link">
                <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                Logout
              </button>
            </div>
          </div>

        </div>
      </header>

      <main class="enterprise-main-body">
        <slot />
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';

const currentPath = window.location.pathname;
const searchQuery = ref('');
const searchResults = ref([]);
const showSearchDropdown = ref(false);
const showNotifications = ref(false);
const showProfileMenu = ref(false);
const user = ref({ name: '', role: '', email: '' });

// Sisiguruhin nitong reaktibong babasahin ang exact role mula sa database user object
const isAdmin = computed(() => {
  if (!user.value || !user.value.role) return false;
  const role = user.value.role.toLowerCase().trim();
  return role === 'admin' || role === 'superadmin';
});

const notifications = ref([]);
const unreadCount = ref(0);

const fetchNotifications = async () => {
  try {
    const res = await axios.get('/api/notifications');
    const data = res.data;
    notifications.value = (data.notifications || []).map(n => ({
      id: n.id,
      title: n.message || n.title,
      time: formatRelativeTime(n.created_at),
      read: n.is_read
    }));
    unreadCount.value = data.unread_count || 0;
  } catch (e) {
    // silently fail — notifications are non-critical
  }
};

const formatRelativeTime = (isoStr) => {
  if (!isoStr) return '';
  const diff = Date.now() - new Date(isoStr).getTime();
  const mins = Math.floor(diff / 60000);
  if (mins < 1) return 'just now';
  if (mins < 60) return `${mins}m ago`;
  const hrs = Math.floor(mins / 60);
  if (hrs < 24) return `${hrs}h ago`;
  return new Date(isoStr).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
};

const markAllAsRead = async () => {
  try {
    await axios.post('/api/notifications/read-all');
    notifications.value.forEach(n => n.read = true);
    unreadCount.value = 0;
  } catch (e) {
    notifications.value.forEach(n => n.read = true);
    unreadCount.value = 0;
  }
};

const fetchAuthUser = async () => {
  try {
    const res = await axios.get('/api/user');
    // Sinisigurong sasalo anuman ang structure ng response galing sa AuthController
    const userData = res.data.user || res.data;
    if (userData) {
      user.value = userData;
    }
  } catch (e) {
    console.error('Failed to fetch auth user:', e);
  }
};

onMounted(() => {
  fetchAuthUser();
});

const onSearchInput = async () => {
  showNotifications.value = false;
  showProfileMenu.value = false;

  if (!searchQuery.value.trim()) {
    searchResults.value = [];
    showSearchDropdown.value = false;
    return;
  }

  try {
    const res = await axios.get(`/api/search?q=${encodeURIComponent(searchQuery.value)}`);
    searchResults.value = res.data;
    showSearchDropdown.value = true;
  } catch (e) {
    console.error('Search error:', e);
  }
};

const highlightMatch = (text, query) => {
  if (!query) return text;
  const regex = new RegExp(`(${query})`, 'gi');
  return text.replace(regex, '<mark class="search-highlight">$1</mark>');
};

const performSearchRedirect = () => {
  if (!searchQuery.value.trim()) return;
  window.location.href = `/invoices?search=${encodeURIComponent(searchQuery.value.trim())}`;
};

const clearSearch = () => {
  searchQuery.value = '';
  searchResults.value = [];
  showSearchDropdown.value = false;
};

const toggleNotifications = () => {
  showNotifications.value = !showNotifications.value;
  showProfileMenu.value = false;
  showSearchDropdown.value = false;
};

const toggleProfileMenu = () => {
  showProfileMenu.value = !showProfileMenu.value;
  showNotifications.value = false;
  showSearchDropdown.value = false;
};

// markAllAsRead is defined above with async API call

const handleLogout = async () => {
  if (confirm('Are you sure you want to log out?')) {
    try {
      await axios.post('/api/logout');
    } catch (e) {
      console.error(e);
    }
    window.location.href = '/login';
  }
};

const closeAllDropdowns = () => {
  showNotifications.value = false;
  showProfileMenu.value = false;
  showSearchDropdown.value = false;
};

onMounted(async () => {
  await fetchAuthUser();
  await fetchNotifications();
});
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

.enterprise-layout-wrapper { display: flex; height: 100vh; background-color: #f8fafc; font-family: 'Inter', sans-serif; }
.enterprise-sidebar { width: 280px; background-color: #1e1e2d; color: #a1a5b7; display: flex; flex-direction: column; flex-shrink: 0; }
.sidebar-brand-container { height: 70px; display: flex; align-items: center; gap: 12px; padding: 0 24px; color: #ffffff; font-weight: 800; font-size: 16px; border-bottom: 1px solid rgba(255,255,255,0.05); }
.brand-icon-box { width: 34px; height: 34px; background: #3699ff; color: #ffffff; display: flex; align-items: center; justify-content: center; border-radius: 8px; }
.sidebar-navigation-links { padding: 20px 16px; display: flex; flex-direction: column; gap: 4px; overflow-y: auto; }
.nav-category-header { font-size: 11px; font-weight: 700; text-transform: uppercase; color: #565674; padding: 0 12px 6px 12px; letter-spacing: 0.5px; }
.mt-4 { margin-top: 16px; }
.nav-item-link { display: flex; align-items: center; gap: 12px; padding: 12px 14px; color: #a1a5b7; text-decoration: none; border-radius: 8px; font-size: 13px; font-weight: 500; transition: all 0.2s; }
.nav-item-link .nav-item-icon { stroke: #565674; transition: stroke 0.2s; }
.nav-item-link:hover, .nav-item-link.active { background-color: #2b2b40; color: #ffffff; }
.nav-item-link:hover .nav-item-icon, .nav-item-link.active .nav-item-icon { stroke: #3699ff; }

.enterprise-content-container { flex: 1; display: flex; flex-direction: column; overflow: hidden; }
.enterprise-header-bar { height: 70px; background-color: #ffffff; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; padding: 0 32px; box-shadow: 0 1px 3px rgba(0,0,0,0.02); position: relative; z-index: 100; }

.header-search-container { position: relative; }
.header-search-box { display: flex; align-items: center; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 14px; width: 380px; height: 40px; transition: all 0.2s; }
.header-search-box:focus-within { border-color: #0f172a; background: #ffffff; box-shadow: 0 0 0 3px rgba(15, 23, 42, 0.05); }
.search-icon { stroke: #64748b; margin-right: 10px; flex-shrink: 0; }
.header-search-box input { flex: 1; border: none; background: transparent; outline: none; font-size: 13px; color: #1e293b; }
.clear-search-btn { background: none; border: none; font-size: 16px; color: #94a3b8; cursor: pointer; padding: 0 4px; }
.clear-search-btn:hover { color: #0f172a; }

.search-results-dropdown { position: absolute; top: 46px; left: 0; width: 100%; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 8px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); max-height: 280px; overflow-y: auto; z-index: 110; }
.search-dropdown-header { font-size: 10px; font-weight: 700; text-transform: uppercase; color: #64748b; padding: 10px 14px; border-bottom: 1px solid #e2e8f0; background: #f8fafc; letter-spacing: 0.5px; }
.search-result-item { display: flex; align-items: center; gap: 10px; padding: 10px 14px; text-decoration: none; border-bottom: 1px solid #f1f5f9; transition: background 0.15s; }
.search-result-item:hover { background: #f1f5f9; }
.result-type-tag { font-size: 9px; font-weight: 700; text-transform: uppercase; background: #e2e8f0; color: #334155; padding: 2px 6px; border-radius: 4px; }
.result-text { font-size: 12px; color: #1e293b; }
.search-no-result { padding: 16px; text-align: center; font-size: 12px; color: #94a3b8; }

:deep(.search-highlight) { background-color: #fef08a; color: #713f12; font-weight: 700; padding: 0 2px; border-radius: 2px; }

.header-utilities-box { display: flex; align-items: center; gap: 14px; }
.dropdown-wrapper { position: relative; }
.header-icon-btn { position: relative; width: 38px; height: 38px; border-radius: 8px; border: 1px solid #e2e8f0; background: #ffffff; display: flex; align-items: center; justify-content: center; color: #64748b; cursor: pointer; transition: all 0.2s; }
.header-icon-btn:hover { background: #f8fafc; color: #0f172a; border-color: #cbd5e1; }
.notification-badge { position: absolute; top: 8px; right: 8px; width: 7px; height: 7px; background: #ef4444; border-radius: 50%; }
.header-divider { width: 1px; height: 28px; background-color: #e2e8f0; margin: 0 2px; }
.dropdown-menu-box { position: absolute; right: 0; top: 48px; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 10px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); z-index: 110; overflow: hidden; }
.notification-box { width: 340px; }
.dropdown-menu-header { display: flex; justify-content: space-between; align-items: center; padding: 12px 16px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; font-size: 11px; font-weight: 700; color: #1e293b; text-transform: uppercase; letter-spacing: 0.5px; }
.notif-count-badge { display: inline-block; background: #ef4444; color: #fff; font-size: 9px; font-weight: 800; padding: 1px 5px; border-radius: 10px; margin-left: 4px; vertical-align: middle; }
.mark-read-btn { background: none; border: none; font-size: 10px; color: #2563eb; cursor: pointer; font-weight: 600; text-transform: none; padding: 2px 6px; border-radius: 4px; transition: background 0.15s; }
.mark-read-btn:hover { background: #eff6ff; }
.dropdown-items-list { max-height: 280px; overflow-y: auto; }
.notif-empty-state { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 28px 16px; gap: 8px; }
.notif-empty-state p { font-size: 12px; color: #94a3b8; margin: 0; }
.dropdown-item-notification { display: flex; align-items: flex-start; gap: 10px; padding: 11px 14px; border-bottom: 1px solid #f1f5f9; background: #ffffff; transition: background 0.15s; }
.dropdown-item-notification:hover { background: #f8fafc; }
.dropdown-item-notification.unread { background: #f0f9ff; border-left: 3px solid #3b82f6; }
.notif-dot { width: 7px; height: 7px; border-radius: 50%; background: #3b82f6; flex-shrink: 0; margin-top: 5px; }
.notif-content { flex: 1; }
.notif-title { font-size: 12px; color: #1e293b; margin: 0 0 3px 0; line-height: 1.4; }
.notif-time { font-size: 10px; color: #94a3b8; }
.profile-box { width: 230px; }
.profile-menu-header { padding: 14px 16px; background: #f8fafc; display: flex; flex-direction: column; gap: 2px; }
.profile-name { font-size: 13px; font-weight: 700; color: #1e293b; }
.profile-email { font-size: 11px; color: #64748b; }
.dropdown-divider { height: 1px; background-color: #e2e8f0; }
.dropdown-menu-link { display: flex; align-items: center; gap: 10px; width: 100%; padding: 10px 16px; background: none; border: none; text-decoration: none; font-size: 13px; font-weight: 500; color: #334155; cursor: pointer; text-align: left; box-sizing: border-box; }
.dropdown-menu-link:hover { background: #f1f5f9; color: #0f172a; }
.logout-link { color: #dc2626; }
.logout-link:hover { background: #fef2f2; color: #dc2626; }
.user-profile-trigger { display: flex; align-items: center; gap: 12px; padding: 4px 8px; border-radius: 8px; cursor: pointer; }
.user-profile-trigger:hover { background: #f8fafc; }
.user-info-text { display: flex; flex-direction: column; text-align: right; }
.user-fullname-text { font-size: 13px; font-weight: 600; color: #1e293b; line-height: 1.2; }
.user-role-text { font-size: 11px; font-weight: 500; color: #64748b; }
.user-avatar-image { width: 36px; height: 36px; border-radius: 8px; object-fit: cover; border: 1px solid #cbd5e1; }
.enterprise-main-body { flex: 1; overflow-y: auto; background-color: #f8fafc; }
</style>