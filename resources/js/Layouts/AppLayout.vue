<template>
  <div class="app-root" :class="{ dark: isDark }" @click="closeAllDropdowns">

    <!-- Mobile Sidebar Backdrop Overlay -->
    <div v-if="mobileSidebarOpen" class="sidebar-backdrop" @click="mobileSidebarOpen = false"></div>

    <!-- ══════════════════ SIDEBAR ══════════════════ -->
    <aside class="sidebar" :class="{ collapsed: sidebarCollapsed, 'mobile-open': mobileSidebarOpen }">

      <!-- Logo -->
      <div class="sidebar-logo-area">
        <img src="/assets/images/LOBISv2.png" alt="LOBIS" class="sidebar-logo" />
        <button class="mobile-close-btn" @click="mobileSidebarOpen = false" aria-label="Close menu">&times;</button>
      </div>

      <!-- Nav -->
      <nav class="sidebar-nav">

        <p class="nav-label" v-show="!sidebarCollapsed">Main Overview</p>
        <a href="/dashboard" class="nav-link" :class="{ active: currentPath.includes('/dashboard') }" :title="sidebarCollapsed ? 'Dashboard' : undefined" @click="onNavClick">
          <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg>
          <span>Dashboard</span>
        </a>

        <p class="nav-label" v-show="!sidebarCollapsed">Sales &amp; Billing</p>
        <a href="/invoices" class="nav-link" :class="{ active: currentPath === '/invoices' }" :title="sidebarCollapsed ? 'Service Invoices' : undefined" @click="onNavClick">
          <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
          <span>Service Invoices</span>
        </a>

        <p class="nav-label" v-show="!sidebarCollapsed">Directory &amp; Catalog</p>
        <a href="/clients" class="nav-link" :class="{ active: currentPath.includes('/clients') }" :title="sidebarCollapsed ? 'Clients / Companies' : undefined" @click="onNavClick">
          <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
          <span>Clients / Companies</span>
        </a>

        <template v-if="isAdmin">
          <p class="nav-label" v-show="!sidebarCollapsed">Team &amp; Administration</p>
          <a href="/teams" class="nav-link" :class="{ active: currentPath.includes('/teams') }" :title="sidebarCollapsed ? 'Teams Management' : undefined" @click="onNavClick">
            <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            <span>Teams Management</span>
          </a>
          <a href="/users" class="nav-link" :class="{ active: currentPath.includes('/users') }" :title="sidebarCollapsed ? 'Users & Roles' : undefined" @click="onNavClick">
            <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            <span>Users &amp; Roles</span>
          </a>
          <a href="/activity-logs" class="nav-link" :class="{ active: currentPath.includes('/activity-logs') }" :title="sidebarCollapsed ? 'Activity & User Logs' : undefined" @click="onNavClick">
            <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            <span>Activity &amp; User Logs</span>
          </a>
        </template>

        <p class="nav-label" v-show="!sidebarCollapsed">Reports</p>
        <a href="/reports" class="nav-link" :class="{ active: currentPath.includes('/reports') }" :title="sidebarCollapsed ? 'Financial Reports' : undefined" @click="onNavClick">
          <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
          <span>Financial Reports</span>
        </a>

      </nav>
    </aside>

    <!-- ══════════════════ MAIN ══════════════════ -->
    <div class="main-wrapper">

      <!-- Header -->
      <header class="app-header">

        <!-- Left: burger + search -->
        <div class="header-left">
          <button class="burger" @click.stop="toggleSidebar" :title="sidebarCollapsed ? 'Expand' : 'Collapse'">
            <span></span><span></span><span></span>
          </button>

          <div class="search-wrap" @click.stop>
            <div class="search-box">
              <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
              <input v-model="searchQuery" @input="onSearchInput" @keyup.enter="performSearchRedirect" type="text" placeholder="Search invoices, clients..." />
              <button v-if="searchQuery" @click="clearSearch" class="search-clear">&times;</button>
            </div>
            <div v-if="showSearchDropdown && searchQuery.trim().length > 0" class="search-dropdown">
              <div class="sd-header">Results ({{ searchResults.length }})</div>
              <template v-if="searchResults.length > 0">
                <a v-for="item in searchResults" :key="item.id" :href="item.link" class="sd-item">
                  <span class="sd-tag">{{ item.type }}</span>
                  <span v-html="highlightMatch(item.title, searchQuery)"></span>
                </a>
              </template>
              <div v-else class="sd-empty">No records found</div>
            </div>
          </div>
        </div>

        <!-- Right: utils -->
        <div class="header-right">

          <!-- Dark mode -->
          <button class="hdr-btn" @click.stop="toggleDarkMode" :title="isDark ? 'Light mode' : 'Dark mode'">
            <svg v-if="isDark" viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="5"/>
              <line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/>
              <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
              <line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/>
              <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
            </svg>
            <svg v-else viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
            </svg>
          </button>

          <!-- Notifications -->
          <div class="dd-wrap" @click.stop>
            <button class="hdr-btn" @click="toggleNotifications" title="Notifications">
              <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
              <span v-if="unreadCount > 0" class="badge">{{ unreadCount > 9 ? '9+' : unreadCount }}</span>
            </button>
            <div v-if="showNotifications" class="dd-panel notif-panel">
              <div class="dd-head">
                <span>Notifications</span>
                <div class="dd-head-actions">
                  <button @click.stop="markAllAsRead" class="dd-action">Mark all read</button>
                </div>
              </div>
              <div class="dd-body">
                <div v-if="notifications.length === 0" class="dd-empty">
                  <svg viewBox="0 0 24 24" width="30" height="30" fill="none" stroke="currentColor" stroke-width="1.5" opacity="0.3"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                  <p>No notifications</p>
                </div>
                <div v-for="n in notifications" :key="n.id" class="notif-item" :class="{ unread: !n.read }" @click="handleNotificationClick(n)" :title="n.invoice_number ? `Open Invoice #${n.invoice_number}` : undefined">
                  <span v-if="!n.read" class="notif-dot"></span>
                  <div class="notif-content">
                    <p class="notif-msg">{{ n.title }}</p>
                    <small class="notif-time">{{ n.time }}</small>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="hdr-sep"></div>

          <!-- Profile -->
          <div class="dd-wrap" @click.stop>
            <div class="profile-trigger" @click="toggleProfileMenu">
              <div class="profile-text">
                <span class="profile-name-sm">{{ user.name || 'Loading...' }}</span>
                <span class="profile-role-sm">{{ user.role || 'Member' }}</span>
              </div>
              <img :src="`https://ui-avatars.com/api/?name=${encodeURIComponent(user.name || 'U')}&background=f59e0b&color=1e293b&bold=true`" alt="avatar" class="avatar" />
            </div>
            <div v-if="showProfileMenu" class="dd-panel profile-panel">
              <div class="profile-panel-head">
                <img :src="`https://ui-avatars.com/api/?name=${encodeURIComponent(user.name || 'U')}&background=f59e0b&color=1e293b&bold=true&size=64`" alt="avatar" class="avatar-lg" />
                <div>
                  <p class="pp-name">{{ user.name || 'User' }}</p>
                  <p class="pp-email">{{ user.email || '' }}</p>
                </div>
              </div>
              <div class="dd-divider"></div>
              <button @click="handleLogout" class="dd-item logout">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                Logout
              </button>
            </div>
          </div>

        </div>
      </header>

      <!-- Scrollable Main Container -->
      <main ref="appMainRef" class="app-main" @scroll="onMainScroll">
        <slot />
      </main>

      <!-- Back to Top Button -->
      <transition name="fade-slide">
        <button
          v-show="showBackToTop"
          @click="scrollToTop"
          class="back-to-top"
          title="Back to top"
          aria-label="Back to top"
        >
          <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="18 15 12 9 6 15"/></svg>
          <span class="back-to-top-label">Top</span>
        </button>
      </transition>

      <!-- Live Reverb Notification Toast -->
      <transition name="toast-slide">
        <div v-if="activeToast" class="notif-toast" @click="handleToastClick">
          <div class="toast-indicator"></div>
          <div class="toast-icon">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>
            </svg>
          </div>
          <div class="toast-content">
            <p class="toast-title">{{ activeToast.title }}</p>
            <p class="toast-msg">{{ activeToast.message }}</p>
          </div>
          <button class="toast-close" @click.stop="activeToast = null">&times;</button>
        </div>
      </transition>

    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import axios from 'axios';

const currentPath = window.location.pathname;

/* Sidebar & Mobile Drawer */
const sidebarCollapsed = ref(false);
const mobileSidebarOpen = ref(false);

const toggleSidebar = () => {
  if (typeof window !== 'undefined' && window.innerWidth <= 1024) {
    mobileSidebarOpen.value = !mobileSidebarOpen.value;
  } else {
    sidebarCollapsed.value = !sidebarCollapsed.value;
  }
};

const onNavClick = () => {
  if (typeof window !== 'undefined' && window.innerWidth <= 1024) {
    mobileSidebarOpen.value = false;
  }
};

/* Back to Top */
const appMainRef = ref(null);
const showBackToTop = ref(false);

const onMainScroll = (e) => {
  const st = e?.target?.scrollTop ?? 0;
  showBackToTop.value = st > 220;
};

const handleWindowScroll = () => {
  const st = window.scrollY || document.documentElement.scrollTop || 0;
  if (st > 220) showBackToTop.value = true;
};

const scrollToTop = () => {
  if (appMainRef.value) {
    appMainRef.value.scrollTo({ top: 0, behavior: 'smooth' });
  }
  if (typeof window !== 'undefined') {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }
};

/* Real-time Toast Notifications */
const activeToast = ref(null);
let toastTimer = null;

const showToast = (title, message, invoiceNumber = null) => {
  if (toastTimer) clearTimeout(toastTimer);
  activeToast.value = { title, message, invoiceNumber };
  toastTimer = setTimeout(() => {
    activeToast.value = null;
  }, 5500);
};

const handleToastClick = () => {
  if (activeToast.value?.invoiceNumber) {
    window.location.href = `/invoices?search=${encodeURIComponent(activeToast.value.invoiceNumber)}`;
  } else {
    showNotifications.value = true;
  }
  activeToast.value = null;
};

/* Dark mode */
const isDark = ref(localStorage.getItem('lobis-theme') === 'dark');
const toggleDarkMode = () => {
  isDark.value = !isDark.value;
  localStorage.setItem('lobis-theme', isDark.value ? 'dark' : 'light');
};

/* Auth user */
const user = ref({ name: '', role: '', email: '' });
const isAdmin = computed(() => {
  const role = (user.value?.role || '').toLowerCase().trim();
  return role === 'admin' || role === 'superadmin';
});
const fetchAuthUser = async () => {
  try {
    const res = await axios.get('/api/user');
    user.value = res.data.user || res.data;
  } catch (e) {
    console.error('Failed to fetch user:', e);
  }
};

/* Notifications */
const notifications = ref([]);
const unreadCount = ref(0);
const showNotifications = ref(false);

const formatRelativeTime = (iso) => {
  if (!iso) return '';
  const mins = Math.floor((Date.now() - new Date(iso)) / 60000);
  if (mins < 1) return 'just now';
  if (mins < 60) return `${mins}m ago`;
  const hrs = Math.floor(mins / 60);
  if (hrs < 24) return `${hrs}h ago`;
  return new Date(iso).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
};

const fetchNotifications = async () => {
  try {
    const { data } = await axios.get('/api/notifications');
    const newItems = (data.notifications || []).map(n => ({
      id: n.id,
      title: n.title || n.message,
      message: n.message,
      time: formatRelativeTime(n.created_at),
      read: !!n.is_read,
      invoice_id: n.invoice_id,
      invoice_number: n.invoice_number,
    }));

    // If new unread notification arrived while user is active on the page, trigger popup!
    const newUnreadCount = data.unread_count || 0;
    if (notifications.value.length > 0 && newUnreadCount > unreadCount.value) {
      const latest = newItems[0];
      if (latest && !latest.read) {
        showToast(latest.title, latest.message || 'You received a new update.', latest.invoice_number);
      }
    }

    notifications.value = newItems;
    unreadCount.value = newUnreadCount;
  } catch (e) {}
};

const markAllAsRead = async () => {
  try { await axios.post('/api/notifications/read-all'); } catch (e) {}
  notifications.value.forEach(n => n.read = true);
  unreadCount.value = 0;
};

const handleNotificationClick = async (n) => {
  if (!n.read) {
    try { await axios.post(`/api/notifications/${n.id}/read`); } catch (e) {}
    n.read = true;
    unreadCount.value = Math.max(0, unreadCount.value - 1);
  }
  showNotifications.value = false;
  if (n.invoice_number) {
    window.location.href = `/invoices?search=${encodeURIComponent(n.invoice_number)}`;
  } else if (n.invoice_id) {
    window.location.href = `/invoices`;
  }
};

/* Echo / Reverb Real-time Subscriptions */
const handleIncomingNotification = (event) => {
  const n = event.notification || event;
  if (!n) return;

  // Avoid duplicate notifications in UI
  if (notifications.value.some(existing => existing.id === n.id)) return;

  notifications.value.unshift({
    id: n.id,
    title: n.title || n.message,
    message: n.message,
    time: 'just now',
    read: false,
    invoice_id: n.invoice_id,
    invoice_number: n.invoice_number,
  });
  unreadCount.value++;

  showToast(
    n.title || 'New Notification',
    n.message || 'You received a new update.',
    n.invoice_number
  );

  // Trigger real-time content refresh across any open views
  window.dispatchEvent(new CustomEvent('lobis:data-updated', { detail: n }));
};

const subscribeToChannels = () => {
  if (!window.Echo || !user.value?.id) return;
  const uid = user.value.id;

  try {
    // 1. Private User Model Channel (App.Models.User.{id})
    window.Echo.private(`App.Models.User.${uid}`)
      .listen('.NotificationCreated', handleIncomingNotification)
      .listen('NotificationCreated', handleIncomingNotification);

    // 2. Short User Channel (user.{id})
    window.Echo.private(`user.${uid}`)
      .listen('.NotificationCreated', handleIncomingNotification)
      .listen('NotificationCreated', handleIncomingNotification);

    // 3. Admin Channel (if admin or superadmin)
    if (isAdmin.value) {
      window.Echo.private('admin-channel')
        .listen('.NotificationCreated', handleIncomingNotification)
        .listen('NotificationCreated', handleIncomingNotification);
    }

    // 4. Invoices Channel - Event-driven data update
    window.Echo.private('invoices-channel')
      .listen('.InvoiceCreated', (e) => window.dispatchEvent(new CustomEvent('lobis:data-updated', { detail: e })))
      .listen('InvoiceCreated', (e) => window.dispatchEvent(new CustomEvent('lobis:data-updated', { detail: e })))
      .listen('.InvoiceStatusChanged', (e) => window.dispatchEvent(new CustomEvent('lobis:data-updated', { detail: e })))
      .listen('InvoiceStatusChanged', (e) => window.dispatchEvent(new CustomEvent('lobis:data-updated', { detail: e })))
      .listen('.InvoiceDeleted', (e) => window.dispatchEvent(new CustomEvent('lobis:data-updated', { detail: e })))
      .listen('InvoiceDeleted', (e) => window.dispatchEvent(new CustomEvent('lobis:data-updated', { detail: e })));
  } catch (err) {
    console.warn('Echo Reverb subscription failed:', err);
  }
};

const onResize = () => {
  if (window.innerWidth > 1024) {
    mobileSidebarOpen.value = false;
  }
};

/* Search */
const searchQuery = ref('');
const searchResults = ref([]);
const showSearchDropdown = ref(false);
const onSearchInput = async () => {
  showNotifications.value = false; showProfileMenu.value = false;
  if (!searchQuery.value.trim()) { searchResults.value = []; showSearchDropdown.value = false; return; }
  try {
    const { data } = await axios.get(`/api/search?q=${encodeURIComponent(searchQuery.value)}`);
    searchResults.value = data; showSearchDropdown.value = true;
  } catch (e) {}
};
const highlightMatch = (text, query) => {
  if (!query) return text;
  return text.replace(new RegExp(`(${query})`, 'gi'), '<mark class="hl">$1</mark>');
};
const performSearchRedirect = () => {
  if (searchQuery.value.trim()) window.location.href = `/invoices?search=${encodeURIComponent(searchQuery.value.trim())}`;
};
const clearSearch = () => { searchQuery.value = ''; searchResults.value = []; showSearchDropdown.value = false; };

/* Dropdowns */
const showProfileMenu = ref(false);
const toggleNotifications = () => { showNotifications.value = !showNotifications.value; showProfileMenu.value = false; showSearchDropdown.value = false; };
const toggleProfileMenu  = () => { showProfileMenu.value = !showProfileMenu.value; showNotifications.value = false; showSearchDropdown.value = false; };
const closeAllDropdowns  = () => { showNotifications.value = false; showProfileMenu.value = false; showSearchDropdown.value = false; };

/* Logout */
const handleLogout = async () => {
  if (!confirm('Are you sure you want to log out?')) return;
  try { await axios.post('/api/logout'); } catch (e) {}
  window.location.href = '/login';
};

onMounted(async () => {
  window.addEventListener('resize', onResize);
  window.addEventListener('scroll', handleWindowScroll, { passive: true });
  await fetchAuthUser();
  await fetchNotifications();
  subscribeToChannels();
});

onUnmounted(() => {
  window.removeEventListener('resize', onResize);
  window.removeEventListener('scroll', handleWindowScroll);
  if (toastTimer) clearTimeout(toastTimer);
  if (window.Echo && user.value?.id) {
    window.Echo.leave(`App.Models.User.${user.value.id}`);
    window.Echo.leave(`user.${user.value.id}`);
    window.Echo.leave('admin-channel');
    window.Echo.leave('invoices-channel');
  }
});
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

/* ══════════════════════════════════════════
   TOKENS
══════════════════════════════════════════ */
.app-root {
  /* Light */
  --c-bg: #f4f6f9;
  --c-sidebar-bg: #ffffff;
  --c-sidebar-border: #e8edf2;
  --c-header-bg: #ffffff;
  --c-header-border: #e8edf2;

  --c-text: #1a2332;
  --c-muted: #7a8898;
  --c-label: #b0bac6;

  --c-nav-hover-bg: #f4f6f9;
  --c-nav-hover-text: #1a2332;

  /* Active = amber brand accent */
  --c-active-bg: #fff8eb;
  --c-active-text: #b45309;
  --c-active-icon: #d97706;
  --c-active-bar: #f59e0b;

  --c-dd-bg: #ffffff;
  --c-dd-border: #e8edf2;
  --c-dd-hover: #f4f6f9;
  --c-input-bg: #f4f6f9;
  --c-input-border: #dde3ec;

  display: flex;
  height: 100vh;
  font-family: 'Inter', sans-serif;
  background: var(--c-bg);
  transition: background 0.25s, color 0.25s;
}

/* Dark overrides */
.app-root.dark {
  --c-bg: #0d1117;
  --c-sidebar-bg: #161b22;
  --c-sidebar-border: #21262d;
  --c-header-bg: #161b22;
  --c-header-border: #21262d;

  --c-text: #e6edf3;
  --c-muted: #7d8590;
  --c-label: #3d444d;

  --c-nav-hover-bg: #1f272e;
  --c-nav-hover-text: #e6edf3;

  --c-active-bg: #2d1f00;
  --c-active-text: #f59e0b;
  --c-active-icon: #f59e0b;
  --c-active-bar: #f59e0b;

  --c-dd-bg: #161b22;
  --c-dd-border: #21262d;
  --c-dd-hover: #1f272e;
  --c-input-bg: #0d1117;
  --c-input-border: #21262d;
}

/* ══════════════════════════════════════════
   SIDEBAR
══════════════════════════════════════════ */
.sidebar {
  width: 244px;
  background: var(--c-sidebar-bg);
  border-right: 1px solid var(--c-sidebar-border);
  display: flex;
  flex-direction: column;
  flex-shrink: 0;
  transition: width 0.28s cubic-bezier(0.4, 0, 0.2, 1);
  overflow: hidden;
  box-shadow: 1px 0 0 0 var(--c-sidebar-border);
}
.sidebar.collapsed { width: 62px; }

/* Logo area — same height as header */
.sidebar-logo-area {
  height: 68px;
  display: flex;
  align-items: center;
  justify-content: center;          /* center logo */
  padding: 0 12px;
  border-bottom: 1px solid var(--c-sidebar-border);
  overflow: hidden;
  flex-shrink: 0;
}
.sidebar-logo {
  height: 46px;
  width: auto;
  max-width: 200px;
  object-fit: contain;
  transition: opacity 0.2s;
}

/* Nav */
.sidebar-nav {
  flex: 1;
  padding: 8px 10px 16px;
  display: flex;
  flex-direction: column;
  gap: 2px;
  overflow-y: auto;
  overflow-x: hidden;
  scrollbar-width: thin;
  scrollbar-color: var(--c-sidebar-border) transparent;
}
.sidebar-nav::-webkit-scrollbar { width: 3px; }
.sidebar-nav::-webkit-scrollbar-thumb { background: var(--c-sidebar-border); border-radius: 4px; }

.nav-label {
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.8px;
  color: var(--c-label);
  padding: 16px 10px 5px;
  margin: 0;
  white-space: nowrap;
  user-select: none;
}

.nav-link {
  display: flex;
  align-items: center;
  gap: 11px;
  height: 42px;               /* uniform height for even lines */
  padding: 0 12px;
  border-radius: 9px;
  font-size: 13px;
  font-weight: 500;
  color: var(--c-muted);
  text-decoration: none;
  white-space: nowrap;
  overflow: hidden;
  transition: background 0.16s, color 0.16s, box-shadow 0.16s;
  position: relative;
  letter-spacing: 0.1px;
}
.nav-link svg { stroke: var(--c-muted); flex-shrink: 0; transition: stroke 0.16s; }
.nav-link span { overflow: hidden; text-overflow: ellipsis; }

.nav-link:hover {
  background: var(--c-nav-hover-bg);
  color: var(--c-nav-hover-text);
  box-shadow: inset 0 0 0 1px var(--c-sidebar-border);
}
.nav-link:hover svg { stroke: var(--c-nav-hover-text); }

/* Active — amber pill */
.nav-link.active {
  background: var(--c-active-bg);
  color: var(--c-active-text);
  font-weight: 600;
  box-shadow: inset 0 0 0 1px rgba(245,158,11,0.25);
}
.nav-link.active svg { stroke: var(--c-active-icon); }
.nav-link.active::before {
  content: '';
  position: absolute;
  left: 0; top: 8px; bottom: 8px;
  width: 3px;
  border-radius: 0 4px 4px 0;
  background: var(--c-active-bar);
}

/* Collapsed: icon-only mode */
.sidebar.collapsed .nav-link {
  justify-content: center;
  gap: 0;
  padding: 0;
  border-radius: 10px;
}
.sidebar.collapsed .nav-link span { display: none; }
.sidebar.collapsed .nav-link.active::before { display: none; }
.sidebar.collapsed .nav-label { display: none; }
.sidebar.collapsed .sidebar-logo { max-width: 38px; height: 38px; }

/* ══════════════════════════════════════════
   HEADER
══════════════════════════════════════════ */
.main-wrapper { flex: 1; display: flex; flex-direction: column; overflow: hidden; }

.app-header {
  height: 68px;               /* same as sidebar logo area */
  background: var(--c-header-bg);
  border-bottom: 1px solid var(--c-header-border);
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 24px;
  flex-shrink: 0;
  position: relative;
  z-index: 100;
  transition: background 0.25s;
}

.header-left { display: flex; align-items: center; gap: 12px; }
.header-right { display: flex; align-items: center; gap: 8px; }

/* Burger */
.burger {
  width: 36px; height: 36px;
  display: flex; flex-direction: column;
  align-items: center; justify-content: center;
  gap: 4.5px;
  border-radius: 8px;
  border: 1px solid var(--c-input-border);
  background: var(--c-input-bg);
  cursor: pointer;
  flex-shrink: 0;
  transition: border-color 0.2s, background 0.2s;
}
.burger:hover { border-color: var(--c-active-bar); background: var(--c-active-bg); }
.burger span { display: block; width: 15px; height: 1.8px; border-radius: 2px; background: var(--c-muted); transition: background 0.2s; }
.burger:hover span { background: var(--c-active-icon); }

/* Search */
.search-wrap { position: relative; }
.search-box {
  display: flex; align-items: center; gap: 8px;
  width: 300px; height: 36px;
  padding: 0 12px;
  background: var(--c-input-bg);
  border: 1px solid var(--c-input-border);
  border-radius: 8px;
  transition: all 0.2s;
}
.search-box svg { stroke: var(--c-muted); flex-shrink: 0; }
.search-box:focus-within { border-color: var(--c-active-bar); box-shadow: 0 0 0 3px rgba(245,158,11,0.12); }
.search-box input { flex: 1; border: none; background: transparent; outline: none; font-size: 12.5px; color: var(--c-text); font-family: 'Inter', sans-serif; }
.search-box input::placeholder { color: var(--c-muted); }
.search-clear { background: none; border: none; font-size: 16px; color: var(--c-muted); cursor: pointer; padding: 0; line-height: 1; }
.search-clear:hover { color: var(--c-text); }

.search-dropdown {
  position: absolute; top: 42px; left: 0; width: 100%;
  background: var(--c-dd-bg); border: 1px solid var(--c-dd-border);
  border-radius: 10px; box-shadow: 0 12px 28px -4px rgba(0,0,0,0.12);
  max-height: 280px; overflow-y: auto; z-index: 120;
}
.sd-header { padding: 9px 14px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--c-muted); border-bottom: 1px solid var(--c-dd-border); background: var(--c-input-bg); }
.sd-item { display: flex; align-items: center; gap: 10px; padding: 9px 14px; text-decoration: none; border-bottom: 1px solid var(--c-dd-border); color: var(--c-text); font-size: 12px; transition: background 0.12s; }
.sd-item:hover { background: var(--c-dd-hover); }
.sd-tag { font-size: 9px; font-weight: 700; text-transform: uppercase; background: var(--c-input-bg); border: 1px solid var(--c-dd-border); color: var(--c-muted); padding: 1px 5px; border-radius: 4px; }
.sd-empty { padding: 14px; text-align: center; font-size: 12px; color: var(--c-muted); }
:deep(.hl) { background: #fef08a; color: #713f12; font-weight: 700; border-radius: 2px; padding: 0 1px; }

/* Header buttons */
.hdr-btn {
  width: 36px; height: 36px;
  display: flex; align-items: center; justify-content: center;
  border-radius: 8px; border: 1px solid var(--c-input-border);
  background: var(--c-input-bg); color: var(--c-muted);
  cursor: pointer; position: relative; transition: all 0.18s;
}
.hdr-btn:hover { border-color: var(--c-active-bar); color: var(--c-active-icon); background: var(--c-active-bg); }
.hdr-btn svg { stroke: currentColor; }

.badge {
  position: absolute; top: -4px; right: -4px;
  min-width: 16px; height: 16px;
  background: #ef4444; color: #fff;
  font-size: 9px; font-weight: 800;
  border-radius: 20px; padding: 0 4px;
  display: flex; align-items: center; justify-content: center;
  border: 2px solid var(--c-header-bg);
}

.hdr-sep { width: 1px; height: 26px; background: var(--c-input-border); margin: 0 4px; }

/* Dropdown panels */
.dd-wrap { position: relative; }
.dd-panel {
  position: absolute; right: 0; top: 44px;
  background: var(--c-dd-bg); border: 1px solid var(--c-dd-border);
  border-radius: 12px; box-shadow: 0 16px 40px -8px rgba(0,0,0,0.16);
  z-index: 120; overflow: hidden;
}
.dd-head {
  display: flex; justify-content: space-between; align-items: center;
  padding: 11px 14px; background: var(--c-input-bg);
  border-bottom: 1px solid var(--c-dd-border);
  font-size: 11px; font-weight: 700; color: var(--c-text);
  text-transform: uppercase; letter-spacing: 0.5px;
}
.dd-action { background: none; border: none; font-size: 10px; color: var(--c-active-text); cursor: pointer; font-weight: 600; padding: 2px 6px; border-radius: 4px; transition: background 0.15s; }
.dd-action:hover { background: var(--c-active-bg); }
.dd-body { max-height: 300px; overflow-y: auto; }
.dd-empty { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 28px 16px; gap: 8px; color: var(--c-muted); font-size: 12px; }
.dd-empty p { margin: 0; }
.dd-divider { height: 1px; background: var(--c-dd-border); }
.dd-item { display: flex; align-items: center; gap: 9px; width: 100%; padding: 10px 14px; background: none; border: none; font-size: 12.5px; font-weight: 500; color: var(--c-text); cursor: pointer; text-align: left; text-decoration: none; transition: background 0.12s; font-family: 'Inter', sans-serif; }
.dd-item:hover { background: var(--c-dd-hover); }
.dd-item svg { stroke: var(--c-muted); }
.logout { color: #dc2626; }
.logout:hover { background: #fef2f2; }
.dark .logout:hover { background: #3b000022; }

/* Notification panel */
.notif-panel { width: 340px; }
.notif-item { display: flex; align-items: flex-start; gap: 10px; padding: 10px 14px; border-bottom: 1px solid var(--c-dd-border); transition: background 0.12s; }
.notif-item:hover { background: var(--c-dd-hover); }
.notif-item.unread { border-left: 3px solid var(--c-active-bar); background: var(--c-active-bg); }
.notif-dot { width: 6px; height: 6px; border-radius: 50%; background: var(--c-active-bar); flex-shrink: 0; margin-top: 5px; }
.notif-msg { font-size: 12px; color: var(--c-text); margin: 0 0 3px; line-height: 1.4; }
.notif-time { font-size: 10px; color: var(--c-muted); }

/* Profile trigger */
.profile-trigger { display: flex; align-items: center; gap: 10px; padding: 4px 6px; border-radius: 8px; cursor: pointer; transition: background 0.15s; }
.profile-trigger:hover { background: var(--c-input-bg); }
.profile-text { display: flex; flex-direction: column; align-items: flex-end; }
.profile-name-sm { font-size: 12px; font-weight: 600; color: var(--c-text); white-space: nowrap; }
.profile-role-sm { font-size: 10px; color: var(--c-muted); white-space: nowrap; }
.avatar { width: 32px; height: 32px; border-radius: 8px; border: 2px solid var(--c-active-bar); }

/* Profile panel */
.profile-panel { width: 240px; }
.profile-panel-head { display: flex; align-items: center; gap: 12px; padding: 14px; background: var(--c-input-bg); border-bottom: 1px solid var(--c-dd-border); }
.avatar-lg { width: 40px; height: 40px; border-radius: 10px; border: 2px solid var(--c-active-bar); flex-shrink: 0; }
.pp-name { font-size: 13px; font-weight: 700; color: var(--c-text); margin: 0 0 2px; }
.pp-email { font-size: 10px; color: var(--c-muted); margin: 0; word-break: break-all; }

/* Main body */
.app-main { flex: 1; overflow-y: auto; background: var(--c-bg); transition: background 0.25s; position: relative; }

/* Mobile Close Button */
.mobile-close-btn {
  display: none;
  background: none;
  border: none;
  font-size: 26px;
  line-height: 1;
  color: var(--c-muted);
  cursor: pointer;
  padding: 4px 8px;
  border-radius: 6px;
  margin-left: auto;
  transition: color 0.15s, background 0.15s;
}
.mobile-close-btn:hover {
  color: var(--c-text);
  background: var(--c-nav-hover-bg);
}

/* Sidebar Backdrop */
.sidebar-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.45);
  backdrop-filter: blur(2px);
  z-index: 998;
  animation: fadeInBackdrop 0.2s ease;
}
@keyframes fadeInBackdrop {
  from { opacity: 0; }
  to { opacity: 1; }
}

/* Notifications head actions */
.dd-head-actions {
  display: flex;
  align-items: center;
  gap: 8px;
}
.notif-item {
  cursor: pointer;
}
.notif-content {
  flex: 1;
  min-width: 0;
}

/* Back to Top Floating Button */
.back-to-top {
  position: fixed;
  bottom: 24px;
  right: 24px;
  z-index: 95;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  height: 42px;
  padding: 0 14px;
  border-radius: 9999px;
  background: var(--c-header-bg);
  border: 1px solid var(--c-header-border);
  color: var(--c-text);
  box-shadow: 0 8px 24px -4px rgba(0, 0, 0, 0.15), 0 2px 6px rgba(0, 0, 0, 0.08);
  cursor: pointer;
  font-size: 12px;
  font-weight: 600;
  letter-spacing: 0.3px;
  font-family: 'Inter', sans-serif;
  transition: all 0.24s cubic-bezier(0.4, 0, 0.2, 1);
  backdrop-filter: blur(8px);
}
.back-to-top svg {
  stroke: var(--c-active-bar);
  transition: transform 0.2s ease;
}
.back-to-top:hover {
  transform: translateY(-3px);
  box-shadow: 0 12px 28px -4px rgba(245, 158, 11, 0.28), 0 4px 10px rgba(0, 0, 0, 0.12);
  border-color: var(--c-active-bar);
  color: var(--c-active-text);
}
.back-to-top:hover svg {
  transform: translateY(-2px);
}
.back-to-top:active {
  transform: translateY(0);
}

/* Back to top transition */
.fade-slide-enter-active,
.fade-slide-leave-active {
  transition: opacity 0.25s ease, transform 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}
.fade-slide-enter-from,
.fade-slide-leave-to {
  opacity: 0;
  transform: translateY(16px) scale(0.9);
}

/* Real-time Toast Notifications */
.notif-toast {
  position: fixed;
  bottom: 24px;
  left: 24px;
  z-index: 1050;
  display: flex;
  align-items: center;
  gap: 12px;
  max-width: 380px;
  width: calc(100vw - 48px);
  padding: 12px 14px;
  background: var(--c-dd-bg);
  border: 1px solid var(--c-dd-border);
  border-radius: 12px;
  box-shadow: 0 16px 36px -6px rgba(0, 0, 0, 0.2), 0 4px 12px rgba(0, 0, 0, 0.08);
  cursor: pointer;
  overflow: hidden;
}
.dark .notif-toast {
  box-shadow: 0 16px 36px -6px rgba(0, 0, 0, 0.6), 0 0 0 1px var(--c-sidebar-border);
}
.toast-indicator {
  position: absolute;
  top: 0;
  bottom: 0;
  left: 0;
  width: 4px;
  background: var(--c-active-bar);
}
.toast-icon {
  width: 34px;
  height: 34px;
  border-radius: 8px;
  background: var(--c-active-bg);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.toast-icon svg {
  stroke: var(--c-active-icon);
}
.toast-content {
  flex: 1;
  min-width: 0;
}
.toast-title {
  font-size: 12px;
  font-weight: 700;
  color: var(--c-text);
  margin: 0 0 2px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.toast-msg {
  font-size: 11px;
  color: var(--c-muted);
  margin: 0;
  line-height: 1.35;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.toast-close {
  background: none;
  border: none;
  font-size: 18px;
  color: var(--c-muted);
  cursor: pointer;
  padding: 0 4px;
  line-height: 1;
}
.toast-close:hover {
  color: var(--c-text);
}
.toast-slide-enter-active,
.toast-slide-leave-active {
  transition: opacity 0.3s ease, transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.toast-slide-enter-from,
.toast-slide-leave-to {
  opacity: 0;
  transform: translateY(24px) scale(0.95);
}

/* ══════════════════════════════════════════
   RESPONSIVE DESIGN (All Devices)
══════════════════════════════════════════ */
@media (max-width: 1024px) {
  .sidebar {
    position: fixed;
    top: 0;
    bottom: 0;
    left: 0;
    height: 100vh;
    z-index: 1000;
    transform: translateX(-100%);
    box-shadow: 4px 0 28px rgba(0, 0, 0, 0.28);
    transition: transform 0.28s cubic-bezier(0.4, 0, 0.2, 1);
  }
  .sidebar.mobile-open {
    transform: translateX(0);
  }
  .mobile-close-btn {
    display: block;
  }
  .sidebar.collapsed {
    width: 244px; /* On mobile drawer, maintain full width */
  }
  .sidebar.collapsed .nav-link {
    justify-content: flex-start;
    padding: 0 12px;
  }
  .sidebar.collapsed .nav-link span {
    display: inline;
  }
  .sidebar.collapsed .nav-label {
    display: block;
  }
  .sidebar.collapsed .sidebar-logo {
    max-width: 160px;
    height: 44px;
  }
}

@media (max-width: 768px) {
  .app-header {
    padding: 0 14px;
    height: 62px;
  }
  .sidebar-logo-area {
    height: 62px;
  }
  .search-box {
    width: 170px;
    height: 34px;
  }
  .profile-text {
    display: none;
  }
  .notif-panel {
    width: min(340px, calc(100vw - 20px));
    right: -60px;
  }
  .profile-panel {
    width: min(240px, calc(100vw - 20px));
  }
  .back-to-top {
    bottom: 18px;
    right: 18px;
    height: 38px;
    padding: 0 12px;
    font-size: 11px;
  }
  .notif-toast {
    bottom: 18px;
    left: 18px;
    width: calc(100vw - 36px);
  }
}

@media (max-width: 480px) {
  .app-header {
    padding: 0 10px;
  }
  .header-left {
    gap: 8px;
  }
  .header-right {
    gap: 6px;
  }
  .search-box {
    width: 125px;
    padding: 0 8px;
  }
  .search-box input {
    font-size: 11.5px;
  }
  .hdr-sep {
    display: none;
  }
  .notif-panel {
    right: -85px;
  }
  .back-to-top-label {
    display: none; /* Icon only on extra small mobile */
  }
  .back-to-top {
    width: 38px;
    height: 38px;
    padding: 0;
    justify-content: center;
  }
}
</style>