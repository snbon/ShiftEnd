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
            <v-row v-if="user?.role === 'owner' && locations.length > 1">
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
              <template v-slot:item.role="{ item }">
                <v-chip
                  :color="getRoleColor(item.role)"
                  size="small"
                >
                  {{ item.role }}
                </v-chip>
              </template>

              <template v-slot:item.status="{ item }">
                <v-chip
                  :color="getStatusColor(item.status)"
                  size="small"
                >
                  {{ item.status }}
                </v-chip>
              </template>

              <template v-slot:item.actions="{ item }">
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
              v-model="inviteForm.email"
              label="Email Address"
              type="email"
              required
              :rules="[v => !!v || 'Email is required', v => /.+@.+\..+/.test(v) || 'Email must be valid']"
            />
            <v-select
              v-model="inviteForm.role"
              :items="roleOptions"
              label="Role"
              required
              :rules="[v => !!v || 'Role is required']"
            />
            <v-select
              v-if="user?.role === 'owner'"
              v-model="inviteForm.location_id"
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
      :timeout="3000"
    >
      {{ message }}
    </v-snackbar>
  </v-container>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';

const loading = ref(false);
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

const user = ref(null);
const locations = ref([]);
const selectedLocation = ref(null);
const teamMembers = ref([]);
const invitations = ref([]);

const inviteForm = ref({
  email: '',
  role: '',
  location_id: null,
});

const headers = [
  { title: 'Name', key: 'name', sortable: true },
  { title: 'Email', key: 'email', sortable: true },
  { title: 'Role', key: 'role', sortable: true },
  { title: 'Status', key: 'status', sortable: true },
  { title: 'Joined', key: 'created_at', sortable: true },
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

const canInvite = computed(() => {
  if (!user.value) return false;
  return user.value.role === 'owner' || user.value.role === 'manager';
});

const canManageUser = (member) => {
  if (!user.value) return false;
  if (user.value.role === 'owner') return true;
  if (user.value.role === 'manager' && member.role === 'employee') return true;
  return false;
};

const canChangeRole = (member) => {
  if (!user.value) return false;
  if (user.value.role === 'owner') return true;
  if (user.value.role === 'manager' && member.role === 'employee') return true;
  return false;
};

const canRemoveUser = (member) => {
  if (!user.value) return false;
  if (user.value.role === 'owner') return true;
  if (user.value.role === 'manager' && member.role === 'employee') return true;
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

const showSuccessMessage = (msg) => {
  message.value = msg;
  messageType.value = 'success';
  showMessage.value = true;
};

const showErrorMessage = (msg) => {
  message.value = msg;
  messageType.value = 'error';
  showMessage.value = true;
};

const fetchUser = async () => {
  try {
    const response = await axios.get('/api/user');
    user.value = response.data.user;
  } catch (error) {
    console.error('Error fetching user:', error);
  }
};

const fetchLocations = async () => {
  try {
    const response = await axios.get('/api/locations');
    locations.value = response.data.data;
    if (locations.value.length > 0 && !selectedLocation.value) {
      selectedLocation.value = locations.value[0].id;
    }
  } catch (error) {
    console.error('Error fetching locations:', error);
  }
};

const loadTeamMembers = async () => {
  if (!selectedLocation.value) return;

  loading.value = true;
  try {
    // Fetch users at the selected location
    const usersResponse = await axios.get(`/api/locations/${selectedLocation.value}`);
    const location = usersResponse.data.data;
    teamMembers.value = location.users || [];

    // Fetch pending invitations
    const invitationsResponse = await axios.get(`/api/invitations?location_id=${selectedLocation.value}`);
    invitations.value = invitationsResponse.data.data || [];
  } catch (error) {
    console.error('Error loading team members:', error);
    showErrorMessage('Failed to load team members');
  } finally {
    loading.value = false;
  }
};

const sendInvitation = async () => {
  inviteLoading.value = true;
  try {
    const locationId = user.value.role === 'owner' ? inviteForm.value.location_id : user.value.location_id;

    await axios.post('/api/invitations', {
      email: inviteForm.value.email,
      role: inviteForm.value.role,
      location_id: locationId,
    });

    showSuccessMessage('Invitation sent successfully!');
    showInviteDialog.value = false;
    inviteForm.value = { email: '', role: '', location_id: null };
    loadTeamMembers();
  } catch (error) {
    console.error('Error sending invitation:', error);
    const errorMessage = error.response?.data?.message || 'Failed to send invitation';
    showErrorMessage(errorMessage);
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
    await axios.put(`/api/users/${selectedUser.value.id}`, {
      role: newRole.value,
    });

    showSuccessMessage('Role updated successfully!');
    showRoleDialog.value = false;
    loadTeamMembers();
  } catch (error) {
    console.error('Error updating role:', error);
    showErrorMessage('Failed to update role');
  } finally {
    roleChangeLoading.value = false;
  }
};

const removeUser = async () => {
  if (!selectedUser.value) return;

  try {
    await axios.delete(`/api/users/${selectedUser.value.id}`);
    showSuccessMessage('User removed from team successfully!');
    loadTeamMembers();
  } catch (error) {
    console.error('Error removing user:', error);
    showErrorMessage('Failed to remove user');
  }
};

onMounted(async () => {
  await fetchUser();
  await fetchLocations();
  await loadTeamMembers();
});
</script>
