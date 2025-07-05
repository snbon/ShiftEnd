<template>
  <v-container>
    <v-row>
      <v-col cols="12">
        <v-card>
          <v-card-title class="d-flex justify-space-between align-center">
            <span>Enhanced Team Management</span>
            <v-btn
              color="primary"
              prepend-icon="mdi-plus"
              @click="showInviteDialog = true"
            >
              Invite Team Member
            </v-btn>
            <v-btn
              v-if="myRole === 'owner'"
              color="secondary"
              prepend-icon="mdi-shield-key"
              class="ml-2"
              @click="openPermissionDialog"
            >
              Manage Permissions
            </v-btn>
          </v-card-title>
          <v-card-text>
            <!-- Location Selector for Owners -->
            <v-row v-if="locations.length > 1">
              <v-col cols="12" md="6">
                <v-select
                  v-model="selectedLocation"
                  :items="locations"
                  item-title="name"
                  item-value="id"
                  label="Select Location"
                  @update:model-value="loadTeamMembers"
                />
              </v-col>
            </v-row>

            <!-- Team Members Table -->
            <v-data-table
              :headers="headers"
              :items="teamMembers"
              :loading="loading"
              class="mt-4"
            >
              <template #item.role="{ item }">
                <v-chip
                  :color="getRoleColor(item.role)"
                  size="small"
                >
                  {{ item.role }}
                </v-chip>
              </template>

              <template #item.status="{ item }">
                <v-chip
                  :color="item.status === 'active' ? 'green' : (item.status === 'invitation_sent' ? 'blue' : 'orange')"
                  :text="item.status === 'invitation_sent' ? 'Invitation Sent' : item.status"
                  size="small"
                />
              </template>

              <template #item.actions="{ item }">
                <v-btn
                  v-if="item.status === 'invitation_sent'"
                  icon="mdi-email"
                  size="small"
                  variant="text"
                  @click="resendInvitation(item)"
                  :title="'Resend Invitation'"
                />
                <v-btn
                  icon="mdi-dots-vertical"
                  variant="text"
                  size="small"
                  @click="openUserMenu(item)"
                />
              </template>
            </v-data-table>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Invite Dialog with Multi-Location Support -->
    <v-dialog v-model="showInviteDialog" max-width="600px">
      <v-card>
        <v-card-title>Invite Team Member</v-card-title>
        <v-card-text>
          <v-form @submit.prevent="sendInvitation" ref="inviteForm">
            <v-text-field
              v-model="inviteEmail"
              label="Email Address"
              type="email"
              required
              :rules="[v => !!v || 'Email is required', v => /.+@.+\..+/.test(v) || 'Email must be valid']"
            />
            <v-select
              v-model="inviteRole"
              :items="roleOptions"
              label="Role"
              required
              :rules="[v => !!v || 'Role is required']"
            />

            <!-- Multi-Location Selection -->
            <v-expansion-panels v-if="locations.length > 1">
              <v-expansion-panel>
                <v-expansion-panel-title>
                  Assign to Multiple Locations
                </v-expansion-panel-title>
                <v-expansion-panel-text>
                  <v-checkbox
                    v-model="assignToAllLocations"
                    label="Assign to all locations"
                    @update:model-value="handleAssignToAll"
                  />
                  <v-select
                    v-model="selectedLocations"
                    :items="locations"
                    item-title="name"
                    item-value="id"
                    label="Select Locations"
                    multiple
                    :disabled="assignToAllLocations"
                    :rules="[v => v.length > 0 || 'Please select at least one location']"
                  />
                </v-expansion-panel-text>
              </v-expansion-panel>
            </v-expansion-panels>
          </v-form>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn
            variant="outlined"
            @click="showInviteDialog = false"
          >
            Cancel
          </v-btn>
          <v-btn
            color="primary"
            :loading="inviteLoading"
            @click="sendInvitation"
          >
            Send Invitation
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- User Management Menu -->
    <v-menu v-model="showUserMenu" :activator="userMenuActivator">
      <v-list>
        <v-list-item
          v-if="selectedUser && canChangeRole(selectedUser)"
          @click="showRoleDialog = true"
        >
          <v-list-item-title>Change Role</v-list-item-title>
        </v-list-item>
        <v-list-item
          v-if="selectedUser"
          @click="showAssignLocationsDialog = true"
        >
          <v-list-item-title>Assign to Locations</v-list-item-title>
        </v-list-item>
        <v-list-item
          v-if="selectedUser && canRemoveUser(selectedUser)"
          @click="showRemoveUserDialog = true"
        >
          <v-list-item-title class="text-error">Remove from Team</v-list-item-title>
        </v-list-item>
      </v-list>
    </v-menu>

    <!-- Role Change Dialog -->
    <v-dialog v-model="showRoleDialog" max-width="400px">
      <v-card>
        <v-card-title>Change Role</v-card-title>
        <v-card-text>
          <v-select
            v-model="newRole"
            :items="availableRoles"
            label="New Role"
            required
          />
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn
            variant="outlined"
            @click="showRoleDialog = false"
          >
            Cancel
          </v-btn>
          <v-btn
            color="primary"
            :loading="roleChangeLoading"
            @click="changeUserRole"
          >
            Update Role
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Assign Locations Dialog -->
    <v-dialog v-model="showAssignLocationsDialog" max-width="600px">
      <v-card>
        <v-card-title>Assign {{ selectedUser?.name }} to Locations</v-card-title>
        <v-card-text>
          <v-alert type="info" variant="tonal" class="mb-4">
            Select the locations and roles for this user. They can be assigned to multiple locations with different roles.
          </v-alert>
          <v-list>
            <v-list-item
              v-for="location in locations"
              :key="location.id"
            >
              <div class="d-flex align-center w-100">
                <v-checkbox
                  v-model="locationAssignments[location.id].assigned"
                  @update:model-value="updateLocationAssignment(location.id)"
                  class="mr-2"
                />
                <div class="flex-grow-1">
                  <v-list-item-title>{{ location.name }}</v-list-item-title>
                  <v-list-item-subtitle>{{ location.address }}</v-list-item-subtitle>
                </div>
                <v-select
                  v-model="locationAssignments[location.id].role"
                  :items="roleOptions"
                  label="Role"
                  density="compact"
                  :disabled="!locationAssignments[location.id].assigned"
                  style="min-width: 120px"
                  class="ml-2"
                />
              </div>
            </v-list-item>
          </v-list>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn
            variant="outlined"
            @click="showAssignLocationsDialog = false"
          >
            Cancel
          </v-btn>
          <v-btn
            color="primary"
            :loading="assignLoading"
            @click="assignUserToLocations"
          >
            Assign to Locations
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Remove User Dialog with Warning -->
    <v-dialog v-model="showRemoveUserDialog" max-width="500px">
      <v-card>
        <v-card-title class="text-error">
          <v-icon color="error" class="mr-2">mdi-alert</v-icon>
          Remove User from Team
        </v-card-title>
        <v-card-text>
          <v-alert type="warning" variant="tonal" class="mb-4">
            <strong>Warning:</strong> This action cannot be undone. The user will lose access to this location and all associated data.
          </v-alert>
          <p><strong>User:</strong> {{ selectedUser?.name }} ({{ selectedUser?.email }})</p>
          <p><strong>Location:</strong> {{ getLocationName(selectedLocation) }}</p>
          <p><strong>Role:</strong> {{ selectedUser?.role }}</p>

          <v-text-field
            v-model="removalConfirmation"
            label="Type 'CONFIRM_REMOVE_USER' to confirm"
            :rules="[v => v === 'CONFIRM_REMOVE_USER' || 'Please type the confirmation text']"
            required
          />
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn
            variant="outlined"
            @click="showRemoveUserDialog = false"
          >
            Cancel
          </v-btn>
          <v-btn
            color="error"
            :loading="removeLoading"
            :disabled="removalConfirmation !== 'CONFIRM_REMOVE_USER'"
            @click="removeUser"
          >
            Remove User
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Permission Management Dialog -->
    <v-dialog v-model="showPermissionDialog" max-width="800px">
      <v-card>
        <v-card-title>Manage Permissions</v-card-title>
        <v-card-text>
          <v-select
            v-model="selectedPermissionRole"
            :items="roleOptions"
            label="Select Role"
            required
            class="mb-4"
            @update:model-value="fetchRolePermissions"
          />
          <v-tabs v-model="permissionTab">
            <v-tab
              v-for="(permissions, category) in rolePermissions"
              :key="category"
              :value="category"
            >
              {{ category.charAt(0).toUpperCase() + category.slice(1) }}
            </v-tab>
          </v-tabs>
          <v-window v-model="permissionTab">
            <v-window-item
              v-for="(permissions, category) in rolePermissions"
              :key="category"
              :value="category"
            >
              <v-list>
                <v-list-item
                  v-for="permission in permissions"
                  :key="permission.id"
                >
                  <div class="d-flex align-center w-100">
                    <v-checkbox
                      v-model="permission.granted"
                      class="mr-2"
                    />
                    <div>
                      <v-list-item-title>{{ permission.display_name }}</v-list-item-title>
                      <v-list-item-subtitle>{{ permission.description }}</v-list-item-subtitle>
                    </div>
                  </div>
                </v-list-item>
              </v-list>
            </v-window-item>
          </v-window>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn
            variant="outlined"
            @click="showPermissionDialog = false"
          >
            Close
          </v-btn>
          <v-btn
            color="primary"
            :loading="permissionLoading"
            @click="savePermissions"
          >
            Save Permissions
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Success/Error Messages -->
    <v-snackbar
      v-model="showMessage"
      :color="messageType"
      :timeout="3000"
    >
      {{ message }}
    </v-snackbar>
  </v-container>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useUserStore } from '../store/users';
import { useLocationsStore } from '../store/locations';
import { useTeamsStore } from '../store/teams';
import { usePermissionStore } from '../store/permissions';
import axios from 'axios';

const userStore = useUserStore();
const locationsStore = useLocationsStore();
const teamsStore = useTeamsStore();
const permissionStore = usePermissionStore();

const user = computed(() => userStore.user);
const locations = computed(() => locationsStore.locations);
const selectedLocation = ref(null);
const teamMembers = computed(() => teamsStore.getTeamMembers(selectedLocation.value));
const loading = computed(() => teamsStore.loading);

// Dialog states
const showInviteDialog = ref(false);
const showUserMenu = ref(false);
const showRoleDialog = ref(false);
const showAssignLocationsDialog = ref(false);
const showRemoveUserDialog = ref(false);
const showPermissionDialog = ref(false);

// Loading states
const inviteLoading = ref(false);
const roleChangeLoading = ref(false);
const assignLoading = ref(false);
const removeLoading = ref(false);
const permissionLoading = ref(false);

// Form data
const inviteEmail = ref('');
const inviteRole = ref('employee');
const selectedLocations = ref([]);
const assignToAllLocations = ref(false);
const newRole = ref('');
const removalConfirmation = ref('');
const selectedPermissionRole = ref('manager');
const rolePermissions = ref({});
const permissionTab = ref('');

// User management
const userMenuActivator = ref(null);
const selectedUser = ref(null);
const locationAssignments = ref({});

// Messages
const showMessage = ref(false);
const message = ref('');
const messageType = ref('success');

const headers = [
  { title: 'Name', key: 'name' },
  { title: 'Email', key: 'email' },
  { title: 'Role', key: 'role' },
  { title: 'Status', key: 'status' },
  { title: 'Actions', key: 'actions', sortable: false },
];

const roleOptions = [
  { title: 'Employee', value: 'employee' },
  { title: 'Manager', value: 'manager' },
];

const availableRoles = [
  { title: 'Employee', value: 'employee' },
  { title: 'Manager', value: 'manager' },
];

const myRole = computed(() => {
  if (!user.value || !user.value.locations) return null;
  const loc = user.value.locations.find(l => l.id === selectedLocation.value);
  return loc?.pivot?.role || null;
});

const canInvite = computed(() => permissionStore.hasPermission('users.invite'));
const canRemoveUser = (member) => permissionStore.hasPermission('users.remove');
const canChangeRole = (member) => permissionStore.hasPermission('users.edit');

const getRoleColor = (role) => {
  const colors = {
    owner: 'purple',
    manager: 'blue',
    employee: 'green',
  };
  return colors[role] || 'grey';
};

const getLocationName = (locationId) => {
  const location = locations.value.find(l => l.id === locationId);
  return location ? location.name : 'Unknown Location';
};

const showSnackbarMessage = (msg, type) => {
  message.value = msg;
  messageType.value = type;
  showMessage.value = true;
};

const loadTeamMembers = async () => {
  if (selectedLocation.value) {
    await teamsStore.fetchTeamMembers(selectedLocation.value, true);
  }
};

const handleAssignToAll = (value) => {
  if (value) {
    selectedLocations.value = locations.value.map(l => l.id);
  } else {
    selectedLocations.value = [];
  }
};

const sendInvitation = async () => {
  inviteLoading.value = true;
  try {
    const locationsToInvite = assignToAllLocations.value
      ? locations.value.map(l => l.id)
      : selectedLocations.value;

    // Send invitation to each location
    for (const locationId of locationsToInvite) {
      await axios.post('/api/invitations', {
        email: inviteEmail.value,
        role: inviteRole.value,
        location_id: locationId
      });
    }

    showInviteDialog.value = false;
    inviteEmail.value = '';
    inviteRole.value = 'employee';
    selectedLocations.value = [];
    assignToAllLocations.value = false;
    showSnackbarMessage('Invitations sent!', 'success');
    loadTeamMembers();
  } catch (error) {
    showSnackbarMessage(error.response?.data?.message || 'Failed to send invitation.', 'error');
  } finally {
    inviteLoading.value = false;
  }
};

const openUserMenu = (user) => {
  selectedUser.value = user;
  userMenuActivator.value = event.target;
  showUserMenu.value = true;
};

const changeUserRole = async () => {
  if (!selectedUser.value || !newRole.value) return;
  roleChangeLoading.value = true;
  try {
    await axios.put(`/api/locations/${selectedLocation.value}/users/${selectedUser.value.id}/role`, {
      role: newRole.value,
    });
    showSnackbarMessage('Role updated successfully!', 'success');
    showRoleDialog.value = false;
    loadTeamMembers();
  } catch (error) {
    showSnackbarMessage('Failed to update role', 'error');
  } finally {
    roleChangeLoading.value = false;
  }
};

const assignUserToLocations = async () => {
  if (!selectedUser.value) return;
  assignLoading.value = true;
  try {
    const assignments = Object.entries(locationAssignments.value)
      .filter(([_, data]) => data.assigned)
      .map(([locationId, data]) => ({
        location_id: parseInt(locationId),
        role: data.role
      }));

    await axios.post(`/api/user-management/users/${selectedUser.value.id}/assign-locations`, {
      location_assignments: assignments
    });

    showSnackbarMessage('User assigned to locations successfully!', 'success');
    showAssignLocationsDialog.value = false;
    loadTeamMembers();
  } catch (error) {
    showSnackbarMessage('Failed to assign user to locations', 'error');
  } finally {
    assignLoading.value = false;
  }
};

const removeUser = async () => {
  if (!selectedUser.value || removalConfirmation.value !== 'CONFIRM_REMOVE_USER') return;
  removeLoading.value = true;
  try {
    await axios.post(`/api/user-management/users/${selectedUser.value.id}/remove-location`, {
      location_id: selectedLocation.value,
      confirmation: removalConfirmation.value
    });
    showSnackbarMessage('User removed from location successfully!', 'success');
    showRemoveUserDialog.value = false;
    removalConfirmation.value = '';
    loadTeamMembers();
  } catch (error) {
    showSnackbarMessage('Failed to remove user', 'error');
  } finally {
    removeLoading.value = false;
  }
};

const resendInvitation = async (item) => {
  try {
    const id = item.id.toString().replace('invitation-', '');
    await axios.post(`/api/invitations/${id}/resend`);
    showSnackbarMessage('Invitation resent!', 'success');
  } catch (error) {
    showSnackbarMessage('Failed to resend invitation.', 'error');
  }
};

const updateLocationAssignment = (locationId) => {
  if (!locationAssignments.value[locationId]) {
    locationAssignments.value[locationId] = { assigned: false, role: 'employee' };
  }
};

const openPermissionDialog = () => {
  selectedPermissionRole.value = 'manager';
  fetchRolePermissions();
  showPermissionDialog.value = true;
};

const fetchRolePermissions = async () => {
  if (!selectedLocation.value || !selectedPermissionRole.value) return;
  permissionLoading.value = true;
  try {
    const { data } = await axios.get(`/api/user-management/locations/${selectedLocation.value}/roles/${selectedPermissionRole.value}/permissions`);
    rolePermissions.value = data.data;
    // Set the first tab as active
    permissionTab.value = Object.keys(data.data)[0] || '';
  } finally {
    permissionLoading.value = false;
  }
};

const savePermissions = async () => {
  permissionLoading.value = true;
  try {
    const permissions = [];
    Object.values(rolePermissions.value).flat().forEach(permission => {
      permissions.push({
        permission_id: permission.id,
        granted: permission.granted
      });
    });
    await axios.put(`/api/user-management/locations/${selectedLocation.value}/roles/${selectedPermissionRole.value}/permissions`, {
      permissions
    });
    showSnackbarMessage('Permissions updated successfully!', 'success');
    showPermissionDialog.value = false;
    permissionStore.fetchPermissions(); // Refresh current user's permissions
  } catch (error) {
    showSnackbarMessage('Failed to update permissions', 'error');
  } finally {
    permissionLoading.value = false;
  }
};

onMounted(async () => {
  // Ensure locations are loaded
  if (!locations.value.length) {
    await locationsStore.fetchLocations();
  }
  // Ensure user is loaded
  if (!user.value) {
    await userStore.fetchUser();
  }
  // Set default location and load team members
  if (locations.value.length > 0) {
    selectedLocation.value = locations.value[0].id;
    await loadTeamMembers();
  }
  // Initialize location assignments
  locations.value.forEach(location => {
    locationAssignments.value[location.id] = { assigned: false, role: 'employee' };
  });
  // Fetch permissions for the selected location
  await permissionStore.fetchPermissions();
});

watch(selectedLocation, (newVal, oldVal) => {
  if (newVal && newVal !== oldVal) {
    loadTeamMembers();
  }
});
</script>
