<template>
  <v-container>
    <v-row>
      <v-col cols="12">
        <v-card>
          <v-card-title class="d-flex justify-space-between align-center">
            <span>Pending Users</span>
            <v-btn
              icon="mdi-refresh"
              variant="text"
              @click="loadPendingUsers"
              :loading="loading"
            />
          </v-card-title>
          <v-card-text>
            <v-alert
              v-if="pendingUsers.length === 0"
              type="info"
              variant="tonal"
            >
              No pending users found.
            </v-alert>

            <v-data-table
              v-else
              :headers="headers"
              :items="pendingUsers"
              :loading="loading"
              class="mt-4"
            >
              <template v-slot:item.created_at="{ item }">
                {{ formatDate(item.created_at) }}
              </template>

              <template v-slot:item.actions="{ item }">
                <v-btn
                  color="primary"
                  size="small"
                  variant="outlined"
                  @click="inviteUser(item)"
                >
                  Send Invitation
                </v-btn>
              </template>
            </v-data-table>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Invite Dialog -->
    <v-dialog v-model="showInviteDialog" max-width="500px">
      <v-card>
        <v-card-title>Send Invitation</v-card-title>
        <v-card-text>
          <v-form @submit.prevent="sendInvitation" ref="inviteForm">
            <v-text-field
              v-model="selectedUser.email"
              label="Email Address"
              readonly
            />
            <v-text-field
              v-model="selectedUser.name"
              label="Name"
              readonly
            />
            <v-select
              v-model="inviteForm.role"
              :items="roleOptions"
              label="Role"
              required
              :rules="[v => !!v || 'Role is required']"
            />
            <v-select
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
import { ref, onMounted } from 'vue';
import axios from 'axios';

const loading = ref(false);
const inviteLoading = ref(false);
const showInviteDialog = ref(false);
const showMessage = ref(false);
const message = ref('');
const messageType = ref('success');

const pendingUsers = ref([]);
const locations = ref([]);
const selectedUser = ref(null);

const inviteForm = ref({
  role: '',
  location_id: null,
});

const headers = [
  { title: 'Name', key: 'name', sortable: true },
  { title: 'Email', key: 'email', sortable: true },
  { title: 'Registered', key: 'created_at', sortable: true },
  { title: 'Actions', key: 'actions', sortable: false },
];

const roleOptions = [
  { title: 'Employee', value: 'employee' },
  { title: 'Manager', value: 'manager' },
];

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

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
};

const loadPendingUsers = async () => {
  loading.value = true;
  try {
    const response = await axios.get('/api/users/pending');
    pendingUsers.value = response.data.data || [];
  } catch (error) {
    console.error('Error loading pending users:', error);
    showErrorMessage('Failed to load pending users');
  } finally {
    loading.value = false;
  }
};

const fetchLocations = async () => {
  try {
    const response = await axios.get('/api/locations');
    locations.value = response.data.data;
  } catch (error) {
    console.error('Error fetching locations:', error);
  }
};

const inviteUser = (user) => {
  selectedUser.value = user;
  inviteForm.value = {
    role: 'employee',
    location_id: locations.value.length > 0 ? locations.value[0].id : null,
  };
  showInviteDialog.value = true;
};

const sendInvitation = async () => {
  inviteLoading.value = true;
  try {
    await axios.post('/api/invitations', {
      email: selectedUser.value.email,
      role: inviteForm.value.role,
      location_id: inviteForm.value.location_id,
    });

    showSuccessMessage('Invitation sent successfully!');
    showInviteDialog.value = false;
    loadPendingUsers();
  } catch (error) {
    console.error('Error sending invitation:', error);
    const errorMessage = error.response?.data?.message || 'Failed to send invitation';
    showErrorMessage(errorMessage);
  } finally {
    inviteLoading.value = false;
  }
};

onMounted(async () => {
  await fetchLocations();
  await loadPendingUsers();
});
</script>
