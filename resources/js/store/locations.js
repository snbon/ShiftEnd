import { defineStore } from 'pinia';
import { ref } from 'vue';
import axios from 'axios';

export const useLocationsStore = defineStore('locations', () => {
  const locations = ref([]);
  const loading = ref(false);
  const error = ref(null);

  async function fetchLocations(force = false) {
    if (locations.value.length && !force) return;
    loading.value = true;
    error.value = null;
    try {
      const response = await axios.get('/api/locations');
      locations.value = response.data.data || [];
    } catch (err) {
      error.value = err;
    } finally {
      loading.value = false;
    }
  }

  async function refreshLocations() {
    await fetchLocations(true);
  }

  // Initialize from user data if available
  function initializeFromUser(userData) {
    if (userData && userData.locations && !locations.value.length) {
      locations.value = userData.locations;
    }
  }

  return {
    locations,
    loading,
    error,
    fetchLocations,
    refreshLocations,
    initializeFromUser,
  };
});
