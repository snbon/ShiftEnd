<template>
  <v-container>
    <v-row justify="center">
      <v-col cols="12" md="8" lg="6">
        <v-card>
          <v-card-title class="text-center">
            <v-icon size="large" color="primary" class="mb-2">mdi-store-plus</v-icon>
            <div>Welcome to ShiftEnd!</div>
          </v-card-title>

          <v-card-text class="text-center">
            <p class="text-body-1 mb-6">
              Let's get you started. Choose how you'd like to use ShiftEnd:
            </p>

            <v-row>
              <v-col cols="12" md="6">
                <v-card
                  variant="outlined"
                  class="h-100 cursor-pointer"
                  :class="{ 'border-primary': selectedOption === 'create' }"
                  @click="selectedOption = 'create'"
                >
                  <v-card-text class="text-center">
                    <v-icon size="large" color="primary" class="mb-4">mdi-store-plus</v-icon>
                    <h3 class="text-h6 mb-2">Create New Location</h3>
                    <p class="text-body-2 text-medium-emphasis">
                      I want to create a new restaurant or business location and manage my team.
                    </p>
                  </v-card-text>
                </v-card>
              </v-col>

              <v-col cols="12" md="6">
                <v-card
                  variant="outlined"
                  class="h-100 cursor-pointer"
                  :class="{ 'border-primary': selectedOption === 'join' }"
                  @click="selectedOption = 'join'"
                >
                  <v-card-text class="text-center">
                    <v-icon size="large" color="primary" class="mb-4">mdi-account-plus</v-icon>
                    <h3 class="text-h6 mb-2">Join Existing Team</h3>
                    <p class="text-body-2 text-medium-emphasis">
                      I want to join an existing restaurant or business team.
                    </p>
                  </v-card-text>
                </v-card>
              </v-col>
            </v-row>

            <!-- Create Location Form -->
            <v-expand-transition>
              <div v-if="selectedOption === 'create'">
                <v-divider class="my-6"></v-divider>
                <h3 class="text-h6 mb-4">Create Your Location</h3>
                <v-form @submit.prevent="createLocation" ref="locationForm">
                  <v-text-field
                    v-model="locationName"
                    label="Location Name"
                    required
                    :rules="[v => !!v || 'Location name is required']"
                  />
                  <v-text-field
                    v-model="locationAddress"
                    label="Address"
                    required
                    :rules="[v => !!v || 'Address is required']"
                  />
                  <v-text-field
                    v-model="locationPhone"
                    label="Phone Number"
                    required
                    :rules="[v => !!v || 'Phone number is required']"
                  />
                  <v-btn
                    color="primary"
                    size="large"
                    block
                    :loading="creating"
                    @click="createLocation"
                  >
                    Create Location & Continue
                  </v-btn>
                </v-form>
              </div>
            </v-expand-transition>

            <!-- Join Team Form -->
            <v-expand-transition>
              <div v-if="selectedOption === 'join'">
                <v-divider class="my-6"></v-divider>
                <h3 class="text-h6 mb-4">Join Existing Team</h3>

                <v-alert type="info" variant="tonal" class="mb-4">
                  <strong>Option 1:</strong> Enter an invitation code if you have one
                </v-alert>

                <v-form @submit.prevent="joinWithCode" ref="joinForm">
                  <v-text-field
                    v-model="inviteCode"
                    label="Invitation Code"
                    placeholder="Enter the 8-character invitation code"
                    :rules="[v => !v || v.length === 8 || 'Invitation code must be 8 characters']"
                  />
                  <v-btn
                    color="primary"
                    size="large"
                    block
                    :loading="joining"
                    :disabled="!inviteCode || inviteCode.length !== 8"
                    @click="joinWithCode"
                  >
                    Join with Code
                  </v-btn>
                </v-form>

                <v-divider class="my-4"></v-divider>

                <v-alert type="info" variant="tonal" class="mb-4">
                  <strong>Option 2:</strong> Wait for an invitation from your manager
                </v-alert>
              </div>
            </v-expand-transition>
          </v-card-text>
        </v-card>
        <!-- Logout button at the bottom -->
        <div class="d-flex justify-center mt-6">
          <v-btn color="error" variant="text" @click="logout">
            <v-icon left>mdi-logout</v-icon>
            Logout
          </v-btn>
        </div>
      </v-col>
    </v-row>

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
import { useRouter } from 'vue-router';
import axios from 'axios';

const router = useRouter();
const selectedOption = ref('');
const creating = ref(false);
const joining = ref(false);
const showMessage = ref(false);
const message = ref('');
const messageType = ref('success');

// Separate refs for location form fields
const locationName = ref('');
const locationAddress = ref('');
const locationPhone = ref('');

const inviteCode = ref('');

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

const createLocation = async () => {
  creating.value = true;
  try {
    const response = await axios.post('/api/locations', {
      name: locationName.value,
      address: locationAddress.value,
      phone: locationPhone.value,
    });

    // Update user role to owner
    await axios.put(`/api/users/me/role`, {
      role: 'owner'
    });

    showSuccessMessage('Location created successfully! You are now the owner.');

    // Redirect to dashboard after a short delay
    setTimeout(() => {
      router.push('/');
    }, 1500);
  } catch (error) {
    console.error('Error creating location:', error);
    const errorMessage = error.response?.data?.message || 'Failed to create location';
    showErrorMessage(errorMessage);
  } finally {
    creating.value = false;
  }
};

const joinWithCode = async () => {
  joining.value = true;
  try {
    await axios.post(`/api/invitations/${inviteCode.value}/accept`);
    showSuccessMessage('Successfully joined the team!');

    // Redirect to dashboard after a short delay
    setTimeout(() => {
      router.push('/');
    }, 1500);
  } catch (error) {
    console.error('Error joining with code:', error);
    const errorMessage = error.response?.data?.message || 'Failed to join with invitation code';
    showErrorMessage(errorMessage);
  } finally {
    joining.value = false;
  }
};

const logout = async () => {
  try {
    await axios.post('/api/logout');
    localStorage.removeItem('user');
    window.location.href = '/login';
  } catch (error) {
    localStorage.removeItem('user');
    window.location.href = '/login';
  }
};

onMounted(() => {
  // Check if user needs onboarding
  // This could be based on user status or a flag
});
</script>

<style scoped>
.cursor-pointer {
  cursor: pointer;
}

.border-primary {
  border-color: rgb(var(--v-theme-primary)) !important;
}
</style>
