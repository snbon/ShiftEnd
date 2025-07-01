import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useLocationStore = defineStore('location', () => {
  // Initialize from localStorage if available
  const storedLocationId = localStorage.getItem('currentLocationId');
  const currentLocationId = ref(storedLocationId ? parseInt(storedLocationId) : null);

  function setLocation(id) {
    currentLocationId.value = id;
    // Persist to localStorage
    if (id) {
      localStorage.setItem('currentLocationId', id.toString());
    } else {
      localStorage.removeItem('currentLocationId');
    }
  }

  function clearLocation() {
    currentLocationId.value = null;
    localStorage.removeItem('currentLocationId');
  }

  return { currentLocationId, setLocation, clearLocation };
});
