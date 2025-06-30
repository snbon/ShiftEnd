<template>
  <v-container fluid fill-height class="login-container">
    <v-row align="center" justify="center">
      <v-col cols="12" sm="8" md="4">
        <v-card class="elevation-12">
          <v-toolbar color="primary" dark flat>
            <v-toolbar-title>ShiftEnd Login</v-toolbar-title>
          </v-toolbar>

          <v-card-text>
            <v-form @submit.prevent="login">
              <v-text-field
                v-model="email"
                label="Email"
                name="email"
                prepend-icon="mdi-email"
                type="email"
                required
              />

              <v-text-field
                v-model="password"
                label="Password"
                name="password"
                prepend-icon="mdi-lock"
                type="password"
                required
              />
            </v-form>
          </v-card-text>

          <v-card-actions>
            <v-spacer />
            <v-btn color="primary" @click="login" :loading="loading">
              Login
            </v-btn>
          </v-card-actions>

          <v-card-text class="text-center">
            <v-alert v-if="error" type="error" class="mb-4">
              {{ error }}
              <template v-if="error === 'Please verify your email before logging in.'">
                <br />
                <v-btn variant="text" color="primary" size="small" @click="resendVerification" :loading="resending">
                  Resend verification email
                </v-btn>
              </template>
            </v-alert>
            <v-alert v-if="resendMessage" type="success" class="mb-4">
              {{ resendMessage }}
            </v-alert>

            <div class="mt-4">
              <router-link to="/register">Don't have an account? Register</router-link>
            </div>

            <div class="mt-4">
              <p class="text-caption">Demo Credentials:</p>
              <p class="text-caption">Owner: owner@shiftend.com / password</p>
              <p class="text-caption">Manager: manager@shiftend.com / password</p>
              <p class="text-caption">Employee: employee@shiftend.com / password</p>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';

const router = useRouter();
const email = ref('');
const password = ref('');
const loading = ref(false);
const error = ref('');
const resendMessage = ref('');
const resending = ref(false);

const login = async () => {
  loading.value = true;
  error.value = '';

  try {
    const response = await axios.post('/api/login', {
      email: email.value,
      password: password.value,
    });

    if (response.data.user) {
      // Store user info in localStorage
      localStorage.setItem('user', JSON.stringify(response.data.user));

      // Clear form
      email.value = '';
      password.value = '';

      // Use window.location.href to trigger a full page reload
      // This ensures App.vue re-initializes and shows the sidebar
      window.location.href = '/';
    }
  } catch (err) {
    if (err.response && err.response.data.message) {
      error.value = err.response.data.message;
    } else {
      error.value = 'Login failed. Please try again.';
    }
  } finally {
    loading.value = false;
  }
};

const resendVerification = async () => {
  resending.value = true;
  resendMessage.value = '';
  try {
    const response = await axios.post('/api/resend-verification', { email: email.value });
    resendMessage.value = response.data.message;
  } catch (err) {
    resendMessage.value = err.response?.data?.message || 'Failed to resend verification email.';
  } finally {
    resending.value = false;
  }
};
</script>

<style scoped>
.login-container {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}
</style>
