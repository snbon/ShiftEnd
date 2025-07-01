<template>
  <v-app>
    <template v-if="user && !isOnboardingPage && !isInvitePage">
      <v-navigation-drawer app permanent>
        <v-list>
          <v-list-item>
            <v-list-item-content>
              <div class="d-flex align-center py-4 px-2">
                <!-- Logo placeholder -->
                <!-- <img src="/logo.png" alt="ShiftEnd Logo" height="32" class="mr-2" /> -->
                <span class="font-weight-bold text-h5">ShiftEnd</span>
              </div>
            </v-list-item-content>
          </v-list-item>
          <v-divider></v-divider>
          <v-list-item v-for="item in menu" :key="item.to" :to="item.to" router exact>
            <template #prepend>
              <v-icon>{{ item.icon }}</v-icon>
            </template>
            <v-list-item-title>{{ item.title }}</v-list-item-title>
          </v-list-item>
          <v-divider></v-divider>
          <v-list-item @click="logout">
            <template #prepend>
              <v-icon>mdi-logout</v-icon>
            </template>
            <v-list-item-title>Logout</v-list-item-title>
          </v-list-item>
        </v-list>
      </v-navigation-drawer>
    </template>
    <v-main>
      <v-alert
        v-if="verificationMessage"
        :type="verificationMessage.includes('successfully') ? 'success' : 'error'"
        class="ma-4"
        closable
        @click:close="verificationMessage = ''"
      >
        {{ verificationMessage }}
      </v-alert>
      <template v-if="isInvitePage">
        <router-view />
      </template>
      <template v-else>
        <router-view />
        <div v-if="loading" class="loading-overlay">
          <v-progress-circular indeterminate color="primary" size="64" />
        </div>
      </template>
    </v-main>
  </v-app>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import axios from 'axios';

const router = useRouter();
const route = useRoute();
const user = ref(null);
const loading = ref(false);
const verificationMessage = ref('');

const isOnboardingPage = computed(() => route.path === '/onboarding');

const isPublicRoute = computed(() => {
  const path = location.pathname;
  const search = location.search;
  return (
    /^\/login(\?|$)/.test(path + search) ||
    /^\/register(\?|$)/.test(path + search) ||
    /^\/invite(\/|$)/.test(path)
  );
});

const isInvitePage = computed(() => route.path.startsWith('/invite/'));

const menu = computed(() => {
  if (user.value?.status === 'pending') {
    return [
      { title: 'Dashboard', to: '/', icon: 'mdi-view-dashboard' },
      { title: 'Settings', to: '/settings', icon: 'mdi-cog' }
    ];
  }
  const baseMenu = [
    { title: 'Dashboard', to: '/', icon: 'mdi-view-dashboard' },
    { title: 'Add Report', to: '/add-report', icon: 'mdi-plus' },
    { title: 'History', to: '/history', icon: 'mdi-history' },
  ];
  if (user.value?.role === 'owner') {
    baseMenu.push(
      { title: 'Locations', to: '/locations', icon: 'mdi-store' },
      { title: 'Team', to: '/team', icon: 'mdi-account-group' },
      { title: 'Pending Users', to: '/pending-users', icon: 'mdi-account-clock' },
      { title: 'Settings', to: '/settings', icon: 'mdi-cog' }
    );
  } else if (user.value?.role === 'manager') {
    baseMenu.push(
      { title: 'Team', to: '/team', icon: 'mdi-account-group' },
      { title: 'Settings', to: '/settings', icon: 'mdi-cog' }
    );
  } else {
    baseMenu.push(
      { title: 'Settings', to: '/settings', icon: 'mdi-cog' }
    );
  }
  return baseMenu;
});

const logout = async () => {
  try {
    await axios.post('/api/logout');
    localStorage.removeItem('user');
    user.value = null;
    window.location.href = '/login';
  } catch (error) {
    localStorage.removeItem('user');
    user.value = null;
    window.location.href = '/login';
  }
};

const checkAuth = async () => {
  loading.value = true;
  try {
    const storedUser = localStorage.getItem('user');
    if (storedUser) {
      try {
        const userData = JSON.parse(storedUser);
        user.value = userData;
      } catch (e) {
        localStorage.removeItem('user');
      }
    }
    const response = await axios.get('/api/auth/check');
    if (response.data.authenticated) {
      const userResponse = await axios.get('/api/user');
      user.value = userResponse.data.user;
      localStorage.setItem('user', JSON.stringify(userResponse.data.user));
      if (user.value.email_verified_at && !user.value.location_id && router.currentRoute.value.path !== '/onboarding') {
        router.push('/onboarding');
      }
    } else {
      user.value = null;
      localStorage.removeItem('user');
      router.push('/login');
    }
  } catch (error) {
    user.value = null;
    localStorage.removeItem('user');
    router.push('/login');
  } finally {
    loading.value = false;
  }
};

const handleVerificationParams = () => {
  const urlParams = new URLSearchParams(window.location.search);
  if (urlParams.get('verified') === '1') {
    verificationMessage.value = 'Email verified successfully! You can now log in.';
    router.replace({ path: route.path, query: {} });
  } else if (urlParams.get('verification_error') === '1') {
    const message = urlParams.get('message') || 'Email verification failed. Please try again or contact support.';
    verificationMessage.value = message;
    router.replace({ path: route.path, query: {} });
  }
};

onMounted(() => {
  if (!isPublicRoute.value) {
    checkAuth();
  }
  handleVerificationParams();
});

// Also watch for route changes (SPA navigation)
watch(() => route.path + location.search, () => {
  if (!isPublicRoute.value) {
    checkAuth();
  }
});
</script>

<style scoped>
.v-navigation-drawer {
  width: 256px;
}
.loading-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background: rgba(255,255,255,0.7);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
}
</style>
