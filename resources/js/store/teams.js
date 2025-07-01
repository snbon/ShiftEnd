import { defineStore } from 'pinia';
import { ref } from 'vue';
import axios from 'axios';

export const useTeamsStore = defineStore('teams', () => {
  // Cache: { [locationId]: [teamMembers] }
  const teamByLocation = ref({});
  const loading = ref(false);
  const error = ref(null);

  function getTeamMembers(locationId) {
    return teamByLocation.value[locationId] || [];
  }

  async function fetchTeamMembers(locationId, force = false) {
    if (!locationId) return;
    if (!force && teamByLocation.value[locationId]) return; // Already cached
    loading.value = true;
    error.value = null;
    try {
      const response = await axios.get(`/api/locations/${locationId}/team`);
      teamByLocation.value[locationId] = response.data.team || [];
    } catch (err) {
      error.value = err;
    } finally {
      loading.value = false;
    }
  }

  async function refreshTeamMembers(locationId) {
    await fetchTeamMembers(locationId, true);
  }

  return {
    teamByLocation,
    loading,
    error,
    getTeamMembers,
    fetchTeamMembers,
    refreshTeamMembers,
  };
});
