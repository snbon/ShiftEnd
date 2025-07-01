<template>
  <v-container>
    <v-row>
      <v-col cols="12">
        <v-card>
          <v-card-title class="d-flex justify-space-between align-center">
            <span>Team Management</span>
            <v-btn
              v-if="canInvite"
              color="primary"
              prepend-icon="mdi-plus"
              @click="showInviteDialog = true"
            >
              Invite Team Member
            </v-btn>
          </v-card-title>
          <v-card-text>
            <!-- Location Selector for Owners -->
            <v-row v-if="myRole === 'owner' && locations.length > 1">
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
                  v-if="canManageUser(item)"
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

    <!-- Invite Dialog -->
    <v-dialog v-model="showInviteDialog" max-width="500px">
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
            <v-select
              v-if="myRole === 'owner' && locations.length > 1"
              v-model="inviteLocationId"
              :items="locations"
              item-title="name"
              item-value="id"
              label="Location"
              required
              :rules="[v => !!v || 'Location is required']"
            />
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
          v-if="selectedUser && canRemoveUser(selectedUser)"
          @click="removeUser"
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

    <!-- Success/Error Messages -->
    <v-snackbar
      v-model="showMessage"
      :color="messageType"
      :timeout="snackbarLoading ? -1 : 3000"
    >
      <template v-if="snackbarLoading">
        <v-progress-circular indeterminate color="white" size="20" class="mr-2" />
        {{ message }}
      </template>
      <template v-else>
        {{ message }}
      </template>
    </v-snackbar>
  </v-container>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { useUserStore } from '../store/users';
import { useLocationsStore } from '../store/locations';
import { useTeamsStore } from '../store/teams';
import axios from 'axios';

const userStore = useUserStore();
const locationsStore = useLocationsStore();
const teamsStore = useTeamsStore();

const user = computed(() => userStore.user);
const locations = computed(() => locationsStore.locations);
const selectedLocation = ref(null);
const teamMembers = computed(() => teamsStore.getTeamMembers(selectedLocation.value));
const loading = computed(() => teamsStore.loading);

const inviteLoading = ref(false);
const roleChangeLoading = ref(false);
const showInviteDialog = ref(false);
const showUserMenu = ref(false);
const showRoleDialog = ref(false);
const userMenuActivator = ref(null);
const selectedUser = ref(null);
const newRole = ref('');
const showMessage = ref(false);
const message = ref('');
const messageType = ref('success');
const snackbarLoading = ref(false);
const resendingId = ref(null);

const inviteEmail = ref('');
const inviteRole = ref('employee');
const inviteLocationId = ref(null);

const inviteForm = ref({
  email: '',
  role: '',
  location_id: null,
});

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

const canInvite = computed(() => {
  if (!myRole.value) return false;
  return myRole.value === 'owner' || myRole.value === 'manager';
});

const canManageUser = (member) => {
  if (!myRole.value) return false;
  if (myRole.value === 'owner') return true;
  if (myRole.value === 'manager' && member.role === 'employee') return true;
  return false;
};

const canChangeRole = (member) => {
  if (!myRole.value) return false;
  if (myRole.value === 'owner') return true;
  if (myRole.value === 'manager' && member.role === 'employee') return true;
  return false;
};

const canRemoveUser = (member) => {
  if (!myRole.value) return false;
  if (myRole.value === 'owner') return true;
  if (myRole.value === 'manager' && member.role === 'employee') return true;
  return false;
};

const getRoleColor = (role) => {
  const colors = {
    owner: 'purple',
    manager: 'blue',
    employee: 'green',
  };
  return colors[role] || 'grey';
};

const getStatusColor = (status) => {
  const colors = {
    active: 'green',
    pending: 'orange',
    inactive: 'red',
  };
  return colors[status] || 'grey';
};

const showSnackbarMessage = (msg, type, loading = false) => {
  message.value = msg;
  messageType.value = type;
  showMessage.value = true;
  snackbarLoading.value = loading;
};

const fetchTeamMembersIfNeeded = async () => {
  if (selectedLocation.value && !teamsStore.getTeamMembers(selectedLocation.value).length) {
    await teamsStore.fetchTeamMembers(selectedLocation.value);
  }
};

const sendInvitation = async () => {
  inviteLoading.value = true;
  try {
    await axios.post('/api/invitations', {
      email: inviteEmail.value,
      role: inviteRole.value,
      location_id: inviteLocationId.value || selectedLocation.value || user.value.location_id
    });
    showInviteDialog.value = false;
    inviteEmail.value = '';
    inviteRole.value = 'employee';
    inviteLocationId.value = null;
    showSnackbarMessage('Invitation sent!', 'success');
    fetchTeamMembersIfNeeded();
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
    fetchTeamMembersIfNeeded();
  } catch (error) {
    console.error('Error updating role:', error);
    showSnackbarMessage('Failed to update role', 'error');
  } finally {
    roleChangeLoading.value = false;
  }
};

const removeUser = async () => {
  if (!selectedUser.value) return;
  if (!confirm('Are you sure you want to remove this user from the location? This action cannot be reverted.')) return;
  try {
    await axios.delete(`/api/locations/${selectedLocation.value}/users/${selectedUser.value.id}`);
    showSnackbarMessage('User removed from team successfully!', 'success');
    fetchTeamMembersIfNeeded();
  } catch (error) {
    console.error('Error removing user:', error);
    showSnackbarMessage('Failed to remove user', 'error');
  }
};

const resendInvitation = async (item) => {
  try {
    resendingId.value = item.id;
    showSnackbarMessage('Resending invitation...', 'info', true);
    const id = item.id.toString().replace('invitation-', '');
    await axios.post(`/api/invitations/${id}/resend`);
    showSnackbarMessage('Invitation resent!', 'success');
  } catch (error) {
    showSnackbarMessage('Failed to resend invitation.', 'error');
  } finally {
    resendingId.value = null;
  }
};

onMounted(async () => {
  if (!user.value) await userStore.fetchUser();
  if (!locations.value.length) await locationsStore.fetchLocations();
  if (!selectedLocation.value && locations.value.length) selectedLocation.value = locations.value[0].id;
  await fetchTeamMembersIfNeeded();
});

watch(selectedLocation, async (newVal, oldVal) => {
  if (newVal && newVal !== oldVal) {
    await fetchTeamMembersIfNeeded();
  }
});
</script>
