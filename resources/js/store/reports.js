import { defineStore } from 'pinia';
import { ref } from 'vue';
import axios from 'axios';
import { useLocationStore } from './location';

export const useReportsStore = defineStore('reports', () => {
  // Cache: { [locationId]: [reports] }
  const reportsByLocation = ref({});
  const loading = ref(false);
  const error = ref(null);

  const locationStore = useLocationStore();

  // Get reports for the current location
  function getReports(locationId = null) {
    const id = locationId || locationStore.currentLocationId;
    return reportsByLocation.value[id] || [];
  }

  // Fetch reports for a location (from API, update cache)
  async function fetchReports(locationId = null, force = false) {
    const id = locationId || locationStore.currentLocationId;
    if (!id) return;
    if (!force && reportsByLocation.value[id]) return; // Already cached
    loading.value = true;
    error.value = null;
    try {
      const response = await axios.get('/api/reports', { params: { location_id: id } });
      reportsByLocation.value[id] = response.data.data || [];
    } catch (err) {
      error.value = err;
    } finally {
      loading.value = false;
    }
  }

  // Manual refresh (force reload)
  async function refreshReports(locationId = null) {
    await fetchReports(locationId, true);
  }

  return {
    reportsByLocation,
    loading,
    error,
    getReports,
    fetchReports,
    refreshReports,
  };
});
