<template>
  <v-container>
    <v-row>
      <v-col cols="12">
        <v-card>
          <v-card-title class="d-flex justify-space-between align-center">
            <span>Manage Locations</span>
            <v-btn
              color="primary"
              @click="showAddDialog = true"
              prepend-icon="mdi-plus"
            >
              Add Location
            </v-btn>
          </v-card-title>
          <v-card-text>
            <v-data-table
              :headers="headers"
              :items="locations"
              :loading="loading"
              class="elevation-1"
            >
              <template v-slot:item.actions="{ item }">
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

    <!-- Add/Edit Location Dialog -->
    <v-dialog v-model="showAddDialog" max-width="600px">
      <v-card>
        <v-card-title>
          {{ editingLocation ? 'Edit Location' : 'Add New Location' }}
        </v-card-title>
        <v-card-text>
          <v-form @submit.prevent="saveLocation" ref="locationForm">
            <v-row>
              <v-col cols="12">
                <v-text-field
                  v-model="locationForm.name"
                  label="Location Name"
                  required
                  :rules="[v => !!v || 'Name is required']"
                />
              </v-col>
              <v-col cols="12">
                <v-text-field
                  v-model="locationForm.address"
                  label="Address"
                  required
                  :rules="[v => !!v || 'Address is required']"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="locationForm.city"
                  label="City"
                  required
                  :rules="[v => !!v || 'City is required']"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="locationForm.state"
                  label="State"
                  required
                  :rules="[v => !!v || 'State is required']"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="locationForm.zip_code"
                  label="ZIP Code"
                  required
                  :rules="[v => !!v || 'ZIP code is required']"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="locationForm.phone"
                  label="Phone Number"
                  required
                  :rules="[v => !!v || 'Phone number is required']"
                />
              </v-col>
              <v-col cols="12">
                <v-textarea
                  v-model="locationForm.description"
                  label="Description"
                  placeholder="Optional description of the location..."
                  rows="3"
                />
              </v-col>
            </v-row>
          </v-form>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn
            color="primary"
            @click="saveLocation"
            :loading="saving"
            :disabled="saving"
          >
            {{ editingLocation ? 'Update' : 'Save' }}
          </v-btn>
          <v-btn
            variant="outlined"
            @click="closeDialog"
            :disabled="saving"
          >
            Cancel
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
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import { useLocationsStore } from '../store/locations';

const locationsStore = useLocationsStore();
const locations = computed(() => locationsStore.locations);
const loading = ref(false);
const saving = ref(false);
const showAddDialog = ref(false);
const editingLocation = ref(null);
const showMessage = ref(false);
const message = ref('');
const messageType = ref('success');

const locationForm = ref({
  name: '',
  address: '',
  city: '',
  state: '',
  zip_code: '',
  phone: '',
  description: '',
});

const headers = [
  { title: 'Name', key: 'name', sortable: true },
  { title: 'Address', key: 'address', sortable: true },
  { title: 'City', key: 'city', sortable: true },
  { title: 'State', key: 'state', sortable: true },
  { title: 'Phone', key: 'phone', sortable: true },
  { title: 'Actions', key: 'actions', sortable: false },
];

const fetchLocationsIfNeeded = async () => {
  if (!locations.value.length) {
    loading.value = true;
    await locationsStore.fetchLocations();
    loading.value = false;
  }
};

const saveLocation = async () => {
  saving.value = true;
  try {
    if (editingLocation.value) {
      await axios.put(`/api/locations/${editingLocation.value.id}`, locationForm.value);
      showSuccessMessage('Location updated successfully');
    } else {
      await axios.post('/api/locations', locationForm.value);
      showSuccessMessage('Location created successfully');
    }

    closeDialog();
    fetchLocationsIfNeeded();
  } catch (error) {
    console.error('Error saving location:', error);
    const errorMessage = error.response?.data?.message || 'Failed to save location';
    showErrorMessage(errorMessage);
  } finally {
    saving.value = false;
  }
};

const editLocation = (location) => {
  editingLocation.value = location;
  locationForm.value = { ...location };
  showAddDialog.value = true;
};

const deleteLocation = async (id) => {
  if (!confirm('Are you sure you want to delete this location?')) return;

  try {
    await axios.delete(`/api/locations/${id}`);
    showSuccessMessage('Location deleted successfully');
    fetchLocationsIfNeeded();
  } catch (error) {
    console.error('Error deleting location:', error);
    showErrorMessage('Failed to delete location');
  }
};

const closeDialog = () => {
  showAddDialog.value = false;
  editingLocation.value = null;
  locationForm.value = {
    name: '',
    address: '',
    city: '',
    state: '',
    zip_code: '',
    phone: '',
    description: '',
  };
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

onMounted(() => {
  fetchLocationsIfNeeded();
});
</script>
