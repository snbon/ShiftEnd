<template>
  <v-container>
    <v-row justify="center">
      <v-col cols="12" md="8" lg="6">
        <v-card>
          <v-card-title class="text-center">
            <v-icon size="large" color="primary" class="mb-2">mdi-account-plus</v-icon>
            <div>Team Invitation</div>
          </v-card-title>

          <v-card-text v-if="loading" class="text-center">
            <v-progress-circular indeterminate color="primary" />
            <div class="mt-4">Loading invitation...</div>
          </v-card-text>

          <v-card-text v-else-if="error" class="text-center">
            <v-icon size="large" color="error" class="mb-2">mdi-alert-circle</v-icon>
            <div class="text-h6 text-error mb-2">{{ error }}</div>
            <v-btn
              color="primary"
              @click="$router.push('/')"
            >
              Go to Dashboard
            </v-btn>
          </v-card-text>

          <v-card-text v-else-if="invitation" class="text-center">
            <div class="text-h6 mb-4">
              You've been invited to join
            </div>

            <v-card variant="outlined" class="mb-4">
              <v-card-text>
                <div class="text-h5 font-weight-bold mb-2">
                  {{ invitation.location.name }}
                </div>
                <div v-if="invitation.location.address" class="text-body-2 text-medium-emphasis mb-2">
                  {{ invitation.location.address }}
                </div>
                <div class="text-body-2 text-medium-emphasis">
                  Invited by: {{ invitation.inviter.name }}
                </div>
              </v-card-text>
            </v-card>

            <div class="mb-4">
              <v-chip
                :color="getRoleColor(invitation.role)"
                size="large"
              >
                {{ invitation.role }} Role
              </v-chip>
            </div>

            <div class="text-body-2 text-medium-emphasis mb-4">
              <v-icon size="small" class="mr-1">mdi-clock-outline</v-icon>
              Expires: {{ formatDate(invitation.expires_at) }}
            </div>

            <!-- Onboarding Registration Form for Invited Users -->
            <div v-if="!isLoggedIn">
              <v-alert type="info" variant="tonal" class="mb-4">
                Please complete your registration to join the team.
              </v-alert>
              <v-form @submit.prevent="registerInvitedUser" ref="registerForm">
                <v-text-field
                  v-model="registerName"
                  label="Your Name"
                  prepend-icon="mdi-account"
                  required
                  :rules="[v => !!v || 'Name is required']"
                />
                <v-text-field
                  v-model="registerEmail"
                  label="Email"
                  prepend-icon="mdi-email"
                  type="email"
                  readonly
                />
                <v-text-field
                  v-model="registerPassword"
                  label="Password"
                  prepend-icon="mdi-lock"
                  type="password"
                  required
                  :rules="[v => !!v || 'Password is required', v => v.length >= 8 || 'Password must be at least 8 characters']"
                />
                <v-text-field
                  v-model="registerPasswordConfirm"
                  label="Confirm Password"
                  prepend-icon="mdi-lock-check"
                  type="password"
                  required
                  :rules="[v => !!v || 'Please confirm your password', v => v === registerPassword || 'Passwords must match']"
                />
                <v-btn
                  color="primary"
                  size="large"
                  block
                  :loading="registering"
                  @click="registerInvitedUser"
                >
                  Create Account & Join Team
                </v-btn>
              </v-form>
              <v-alert v-if="registerError" type="error" class="mt-4">{{ registerError }}</v-alert>
              <v-alert v-if="registerSuccess" type="success" class="mt-4">{{ registerSuccess }}</v-alert>
            </div>

            <!-- Existing logic for logged in users -->
            <div v-else-if="canAccept" class="mb-4">
              <v-btn
                color="success"
                size="large"
                :loading="accepting"
                @click="acceptInvitation"
              >
                Accept Invitation
              </v-btn>
            </div>

            <div v-else class="mb-4">
              <v-alert type="warning" variant="tonal">
                {{ getAcceptanceError() }}
              </v-alert>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';

const route = useRoute();
const router = useRouter();

const loading = ref(true);
const accepting = ref(false);
const error = ref('');
const invitation = ref(null);
const user = ref(null);

const registerName = ref('');
const registerEmail = ref('');
const registerPassword = ref('');
const registerPasswordConfirm = ref('');
const registering = ref(false);
const registerError = ref('');
const registerSuccess = ref('');

const inviteCode = computed(() => route.params.inviteCode);

const isLoggedIn = computed(() => !!user.value);

const canAccept = computed(() => {
  if (!invitation.value || !user.value) return false;

  // Check if invitation is still valid
  if (invitation.value.status !== 'pending') return false;

  // Check if user email matches invitation email
  if (invitation.value.email !== user.value.email) return false;

  // Check if user is already assigned to a location
  if (user.value.location_id) return false;

  return true;
});

const getRoleColor = (role) => {
  const colors = {
    manager: 'blue',
    employee: 'green',
  };
  return colors[role] || 'grey';
};

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
};

const getAcceptanceError = () => {
  if (!invitation.value) return 'Invalid invitation';

  if (invitation.value.status !== 'pending') {
    return 'This invitation has already been accepted or has expired';
  }

  if (invitation.value.email !== user.value?.email) {
    return 'This invitation was sent to a different email address';
  }

  if (user.value?.location_id) {
    return 'You are already assigned to a location';
  }

  return 'Unable to accept this invitation';
};

const fetchInvitation = async () => {
  try {
    const response = await axios.get(`/api/invitations/public/${inviteCode.value}`);
    invitation.value = response.data.data;
    // Pre-fill the email for registration
    registerEmail.value = invitation.value.email;
  } catch (error) {
    console.error('Error fetching invitation:', error);
    if (error.response?.status === 404) {
      error.value = 'Invitation not found or has expired';
    } else {
      error.value = 'Failed to load invitation';
    }
  } finally {
    loading.value = false;
  }
};

const registerInvitedUser = async () => {
  registering.value = true;
  registerError.value = '';
  registerSuccess.value = '';
  try {
    await axios.post(`/api/invitations/public/${inviteCode.value}/accept`, {
      name: registerName.value,
      email: registerEmail.value,
      password: registerPassword.value,
      password_confirmation: registerPasswordConfirm.value,
    });
    registerSuccess.value = 'Registration successful! Please check your email to verify your account.';
    setTimeout(() => {
      router.push({ path: '/login', query: { message: 'Registration successful! Please check your email to verify your account.' } });
    }, 2000);
  } catch (error) {
    registerError.value = error.response?.data?.message || 'Registration failed. Please try again.';
  } finally {
    registering.value = false;
  }
};

const acceptInvitation = async () => {
  accepting.value = true;
  try {
    await axios.post(`/api/invitations/${inviteCode.value}/accept`);

    // Redirect to dashboard with success message
    router.push('/?invitation_accepted=1');
  } catch (error) {
    console.error('Error accepting invitation:', error);
    const errorMessage = error.response?.data?.message || 'Failed to accept invitation';
    error.value = errorMessage;
  } finally {
    accepting.value = false;
  }
};

onMounted(() => {
  fetchInvitation();
});
</script>
