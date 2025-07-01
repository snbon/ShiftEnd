<template>
  <v-container>
    <v-row>
      <v-col cols="12">
        <h1 class="text-h4 mb-4">Settings</h1>
      </v-col>
    </v-row>

    <v-row>
      <!-- Current Location & Default Location -->
      <v-col cols="12" md="6">
        <v-card>
          <v-card-title>Location Settings</v-card-title>
          <v-card-text>
            <!-- Single Location Display -->
            <div v-if="userLocations.length === 1">
              <v-list>
                <v-list-item>
                  <template #prepend>
                    <v-icon>mdi-store</v-icon>
                  </template>
                  <v-list-item-title>Current Location</v-list-item-title>
                  <v-list-item-subtitle>{{ userLocations[0].name }}</v-list-item-subtitle>
                </v-list-item>
              </v-list>
            </div>

            <!-- Multiple Locations Selector -->
            <div v-else-if="userLocations.length > 1">
              <v-select
                v-model="currentLocationId"
                :items="userLocations"
                item-title="name"
                item-value="id"
                label="Current Location"
                prepend-icon="mdi-store"
                @update:model-value="switchLocation"
              />
            </div>

            <!-- No Locations -->
            <div v-else>
              <v-alert type="info" variant="tonal">
                No locations assigned. Please complete onboarding first.
              </v-alert>
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- User Profile -->
      <v-col cols="12" md="6">
        <v-card>
          <v-card-title>Profile</v-card-title>
          <v-card-text>
            <v-list>
              <v-list-item>
                <template #prepend>
                  <v-icon>mdi-account</v-icon>
                </template>
                <v-list-item-title>Name</v-list-item-title>
                <v-list-item-subtitle>{{ user?.name }}</v-list-item-subtitle>
              </v-list-item>

              <v-list-item>
                <template #prepend>
                  <v-icon>mdi-email</v-icon>
                </template>
                <v-list-item-title>Email</v-list-item-title>
                <v-list-item-subtitle>{{ user?.email }}</v-list-item-subtitle>
              </v-list-item>

              <v-list-item>
                <template #prepend>
                  <v-icon>mdi-shield</v-icon>
                </template>
                <v-list-item-title>Role</v-list-item-title>
                <v-list-item-subtitle>{{ myRole }}</v-list-item-subtitle>
              </v-list-item>
            </v-list>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Location Management (Owners Only) -->
    <v-row v-if="myRole === 'owner'">
      <v-col cols="12">
        <v-card>
          <v-card-title class="d-flex justify-space-between align-center">
            <span>My Locations</span>
            <v-btn color="primary" @click="showCreateLocation = true" prepend-icon="mdi-plus">
              Add Location
            </v-btn>
          </v-card-title>
          <v-card-text>
            <v-data-table
              :headers="locationHeaders"
              :items="userLocations"
              :loading="loading"
              class="elevation-1"
            >
              <template #item.is_active="{ item }">
                <v-chip
                  :color="item.is_active ? 'green' : 'grey'"
                  :text="item.is_active ? 'Active' : 'Inactive'"
                  size="small"
                />
              </template>
              <template #item.actions="{ item }">
                <v-btn
                  icon="mdi-eye"
                  size="small"
                  variant="text"
                  @click="viewLocation(item.id)"
                />
                <v-btn
                  icon="mdi-pencil"
                  size="small"
                  variant="text"
                  @click="editLocation(item)"
                />
                <v-btn
                  icon="mdi-delete"
                  size="small"
                  variant="text"
                  color="error"
                  @click="deleteLocation(item.id)"
                />
              </template>
            </v-data-table>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Team Management (Owners and Managers) -->
    <v-row v-if="myRole === 'owner' || myRole === 'manager'">
      <v-col cols="12">
        <v-card>
          <v-card-title class="d-flex justify-space-between align-center">
            <span>Team Members</span>
            <v-btn color="primary" @click="showInviteUser = true" prepend-icon="mdi-account-plus">
              Invite User
            </v-btn>
          </v-card-title>
          <v-card-text>
            <v-data-table
              :headers="userHeaders"
              :items="teamMembers"
              :loading="loading"
              class="elevation-1"
            >
              <template #item.role="{ item }">
                <v-chip
                  :color="getRoleColor(item.role)"
                  :text="item.role"
                  size="small"
                />
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
                  v-if="canManageUser(item)"
                  icon="mdi-pencil"
                  size="small"
                  variant="text"
                  @click="editUserRole(item)"
                />
                <v-btn
                  v-if="canRemoveUser(item)"
                  icon="mdi-delete"
                  size="small"
                  variant="text"
                  color="error"
                  @click="removeUser(item.id)"
                />
                <v-btn
                  v-if="item.status === 'invitation_sent'"
                  icon="mdi-email"
                  size="small"
                  variant="text"
                  @click="resendInvitation(item)"
                  :title="'Resend Invitation'"
                />
              </template>
            </v-data-table>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Create Location Dialog -->
    <v-dialog v-model="showCreateLocation" max-width="500px">
      <v-card>
        <v-card-title>Create New Location</v-card-title>
        <v-card-text>
          <v-form @submit.prevent="createLocation" ref="locationForm">
            <v-text-field
              v-model="newLocation.name"
              label="Location Name"
              required
              :rules="[v => !!v || 'Location name is required']"
            />
            <v-text-field
              v-model="newLocation.address"
              label="Address"
              required
              :rules="[v => !!v || 'Address is required']"
            />
            <v-text-field
              v-model="newLocation.phone"
              label="Phone Number"
              required
              :rules="[v => !!v || 'Phone number is required']"
            />
          </v-form>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn @click="showCreateLocation = false">Cancel</v-btn>
          <v-btn color="primary" @click="createLocation" :loading="creating">Create</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Invite User Dialog -->
    <v-dialog v-model="showInviteUser" max-width="500px">
      <v-card>
        <v-card-title>Invite User</v-card-title>
        <v-card-text>
          <v-form @submit.prevent="inviteUser" ref="inviteForm">
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
              v-if="userLocations.length > 1"
              v-model="inviteLocationId"
              :items="userLocations"
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
          <v-btn @click="showInviteUser = false">Cancel</v-btn>
          <v-btn color="primary" @click="inviteUser" :loading="inviting">Send Invitation</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Edit User Role Dialog -->
    <v-dialog v-model="showEditUserRole" max-width="400px">
      <v-card>
        <v-card-title>Edit User Role</v-card-title>
        <v-card-text>
          <v-select
            v-model="editingUserRole"
            :items="roleOptions"
            label="Role"
            required
          />
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn @click="showEditUserRole = false">Cancel</v-btn>
          <v-btn color="primary" @click="updateUserRole" :loading="updatingRole">Update</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Snackbar for feedback -->
    <v-snackbar
      v-model="showMessageRef"
      :color="messageType"
      :timeout="3000"
    >
      {{ message }}
    </v-snackbar>
  </v-container>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import { useLocationStore } from '../store/location';

const router = useRouter();
const user = ref(null);
const userLocations = ref([]);
const teamMembers = ref([]);
const loading = ref(false);
const creating = ref(false);
const inviting = ref(false);
const updatingRole = ref(false);

// Form data
const showCreateLocation = ref(false);
const showInviteUser = ref(false);
const showEditUserRole = ref(false);
const newLocation = ref({ name: '', address: '', phone: '' });
const inviteEmail = ref('');
const inviteRole = ref('employee');
const editingUser = ref(null);
const editingUserRole = ref('employee');
const inviteLocationId = ref(null);

// Table headers
const locationHeaders = [
  { title: 'Name', key: 'name' },
  { title: 'Address', key: 'address' },
  { title: 'Phone', key: 'phone' },
  { title: 'Status', key: 'is_active' },
  { title: 'Actions', key: 'actions', sortable: false },
];

const userHeaders = [
  { title: 'Name', key: 'name' },
  { title: 'Email', key: 'email' },
  { title: 'Role', key: 'role' },
  { title: 'Status', key: 'status' },
  { title: 'Actions', key: 'actions', sortable: false },
];

const roleOptions = [
  { title: 'Employee', value: 'employee' },
  { title: 'Manager', value: 'manager' },
  { title: 'Owner', value: 'owner' },
];

const locationStore = useLocationStore();
const currentLocationId = computed({
  get: () => locationStore.currentLocationId,
  set: (id) => locationStore.setLocation(id),
});

const myRole = computed(() => {
  if (!user.value || !user.value.locations) return null;
  const loc = user.value.locations.find(l => l.id === currentLocationId.value);
  return loc?.pivot?.role || null;
});

const fetchData = async () => {
  loading.value = true;
  try {
    const userResponse = await axios.get('/api/user');
    user.value = userResponse.data.user;
    userLocations.value = user.value.locations || [];
    if (userLocations.value.length > 0 && !currentLocationId.value) {
      currentLocationId.value = userLocations.value[0].id;
    }
    await fetchTeamMembers();
  } catch (error) {
    console.error('Error fetching data:', error);
  } finally {
    loading.value = false;
  }
};

const fetchTeamMembers = async () => {
  if (!currentLocationId.value) return;
  try {
    const response = await axios.get(`/api/locations/${currentLocationId.value}/team`);
    teamMembers.value = response.data.team || [];
  } catch (error) {
    console.error('Error fetching team members:', error);
  }
};

const updateDefaultLocation = async () => {
  // This function is no longer needed as current location is always the default
  console.log('Default location is always the same as current location');
};

const createLocation = async () => {
  creating.value = true;
  try {
    await axios.post('/api/locations', newLocation.value);
    showCreateLocation.value = false;
    newLocation.value = { name: '', address: '', phone: '' };
    await fetchData();
  } catch (error) {
    console.error('Error creating location:', error);
  } finally {
    creating.value = false;
  }
};

const inviteUser = async () => {
  inviting.value = true;
  try {
    await axios.post('/api/invitations', {
      email: inviteEmail.value,
      role: inviteRole.value,
      location_id: inviteLocationId.value || currentLocationId.value
    });
    showInviteUser.value = false;
    inviteEmail.value = '';
    inviteRole.value = 'employee';
    inviteLocationId.value = null;
  } catch (error) {
    console.error('Error inviting user:', error);
  } finally {
    inviting.value = false;
  }
};

const editUserRole = (user) => {
  editingUser.value = user;
  editingUserRole.value = user.role;
  showEditUserRole.value = true;
};

const updateUserRole = async () => {
  updatingRole.value = true;
  try {
    await axios.put(`/api/users/${editingUser.value.id}/role`, {
      role: editingUserRole.value
    });
    showEditUserRole.value = false;
    await fetchData();
  } catch (error) {
    console.error('Error updating user role:', error);
  } finally {
    updatingRole.value = false;
  }
};

const deleteLocation = async (locationId) => {
  if (confirm('Are you sure you want to delete this location?')) {
    try {
      await axios.delete(`/api/locations/${locationId}`);
      await fetchData();
    } catch (error) {
      console.error('Error deleting location:', error);
    }
  }
};

const removeUser = async (userId) => {
  if (confirm('Are you sure you want to remove this user from the location? This action cannot be reverted.')) {
    try {
      await axios.delete(`/api/locations/${currentLocationId.value}/users/${userId}`);
      await fetchTeamMembers();
    } catch (error) {
      console.error('Error removing user:', error);
    }
  }
};

const viewLocation = (locationId) => {
  router.push(`/locations/${locationId}`);
};

const editLocation = (location) => {
  // Implement edit location functionality
  console.log('Edit location:', location);
};

const getRoleColor = (role) => {
  const colors = {
    owner: 'red',
    manager: 'orange',
    employee: 'blue'
  };
  return colors[role] || 'grey';
};

const canManageUser = (targetUser) => {
  if (myRole.value === 'owner') return true;
  if (myRole.value === 'manager' && targetUser.role === 'employee') return true;
  return false;
};

const canRemoveUser = (targetUser) => {
  if (myRole.value === 'owner') return true;
  if (myRole.value === 'manager' && targetUser.role === 'employee') return true;
  return false;
};

const switchLocation = async () => {
  await axios.put(`/api/users/me/location`, {
    location_id: currentLocationId.value
  });
  // Refetch user and update localStorage
  const userResponse = await axios.get('/api/user');
  localStorage.setItem('user', JSON.stringify(userResponse.data.user));
  // Emit a global event for location change
  window.dispatchEvent(new CustomEvent('location-changed', { detail: { locationId: currentLocationId.value } }));
  // Optionally, you can show a message or refetch local data here
};

const resendInvitation = async (item) => {
  try {
    const id = item.id.toString().replace('invitation-', '');
    await axios.post(`/api/invitations/${id}/resend`);
    showMessage('Invitation resent!', 'success');
  } catch (error) {
    showMessage('Failed to resend invitation.', 'error');
  }
};

const showMessage = (msg, type) => {
  message.value = msg;
  messageType.value = type;
  showMessageRef.value = true;
};
const showMessageRef = ref(false);
const message = ref('');
const messageType = ref('success');

onMounted(() => {
  fetchData();
});
</script>
