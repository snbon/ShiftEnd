import { defineStore } from 'pinia';
import { ref } from 'vue';
import axios from 'axios';

export const useUserStore = defineStore('user', () => {
  const user = ref(null);
  const loading = ref(false);
  const error = ref(null);

  async function fetchUser(force = false) {
    if (user.value && !force) return;
    loading.value = true;
    error.value = null;
    try {
      const response = await axios.get('/api/user');
      user.value = response.data.user;
    } catch (err) {
      error.value = err;
    } finally {
      loading.value = false;
    }
  }

  async function refreshUser() {
    await fetchUser(true);
  }

  // Initialize from localStorage if available
  function initializeFromStorage() {
    try {
      const storedUser = localStorage.getItem('user');
      if (storedUser && !user.value) {
        user.value = JSON.parse(storedUser);
      }
    } catch (e) {
      console.error('Error loading user from storage:', e);
    }
  }

  // Initialize on store creation
  initializeFromStorage();

  return {
    user,
    loading,
    error,
    fetchUser,
    refreshUser,
    initializeFromStorage,
  };
});
