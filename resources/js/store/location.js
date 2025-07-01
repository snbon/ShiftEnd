import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useLocationStore = defineStore('location', () => {
  const currentLocationId = ref(null);
  function setLocation(id) {
    currentLocationId.value = id;
  }
  return { currentLocationId, setLocation };
});
