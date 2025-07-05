import { defineStore } from 'pinia';
import axios from 'axios';
import { useUserStore } from './users';
import { useLocationStore } from './location';

export const usePermissionStore = defineStore('permissions', {
  state: () => ({
    permissions: {}, // { permissionName: true/false }
    loading: false,
  }),
  actions: {
    async fetchPermissions() {
      this.loading = true;
      try {
        const userStore = useUserStore();
        const locationStore = useLocationStore();
        const user = userStore.user;
        const locationId = locationStore.currentLocationId;
        if (!user || !locationId) return;
        // Find the user's role for this location
        const loc = user.locations.find(l => l.id === locationId);
        const role = loc?.pivot?.role;
        if (!role) return;
        const { data } = await axios.get(`/api/user-management/locations/${locationId}/roles/${role}/permissions`);
        // Flatten permissions
        const flat = {};
        Object.values(data.data).flat().forEach(p => {
          flat[p.name] = !!p.granted;
        });
        this.permissions = flat;
      } finally {
        this.loading = false;
      }
    },
    hasPermission(permissionName) {
      return !!this.permissions[permissionName];
    },
  },
});
