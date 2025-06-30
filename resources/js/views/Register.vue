<template>
  <div class="register-bg">
    <v-container class="fill-height" fluid>
      <v-row align="center" justify="center" class="fill-height">
        <v-col cols="12" sm="8" md="4">
          <v-card class="elevation-12 pa-4">
            <v-toolbar color="primary" dark flat>
              <v-toolbar-title class="d-flex align-center">
                <!-- Logo placeholder -->
                <!-- <img src="/logo.png" alt="ShiftEnd Logo" height="28" class="mr-2" /> -->
                <span class="font-weight-bold text-h6">Register</span>
              </v-toolbar-title>
            </v-toolbar>
            <v-card-text>
              <v-form ref="form" @submit.prevent="register">
                <v-text-field
                  v-model="name"
                  label="Name"
                  prepend-icon="mdi-account"
                  required
                  :rules="[v => !!v || 'Name is required']"
                />
                <v-text-field
                  v-model="email"
                  label="Email"
                  prepend-icon="mdi-email"
                  type="email"
                  required
                  :rules="[v => !!v || 'Email is required', v => /.+@.+\..+/.test(v) || 'Email must be valid']"
                />
                <v-text-field
                  v-model="password"
                  label="Password"
                  prepend-icon="mdi-lock"
                  type="password"
                  required
                  :rules="[v => !!v || 'Password is required', v => v.length >= 8 || 'Password must be at least 8 characters']"
                />
                <v-text-field
                  v-model="password_confirmation"
                  label="Confirm Password"
                  prepend-icon="mdi-lock-check"
                  type="password"
                  required
                  :rules="[v => !!v || 'Please confirm your password', v => v === password || 'Passwords must match']"
                />
              </v-form>
            </v-card-text>
            <v-card-actions>
              <v-spacer />
              <v-btn color="primary" @click="register" :loading="loading">Register</v-btn>
            </v-card-actions>
            <v-card-text class="text-center">
              <v-alert v-if="error" type="error" class="mb-4">{{ error }}</v-alert>
              <div class="mt-4">
                <router-link to="/login">Already have an account? Login</router-link>
              </div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>
    </v-container>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';

const router = useRouter();
const form = ref(null);
const name = ref('');
const email = ref('');
const password = ref('');
const password_confirmation = ref('');
const loading = ref(false);
const error = ref('');

const register = async () => {
  if (!form.value.validate()) return;

  loading.value = true;
  error.value = '';

  try {
    const response = await axios.post('/api/register', {
      name: name.value,
      email: email.value,
      password: password.value,
      password_confirmation: password_confirmation.value,
    });

    // Show success message and redirect to login
    error.value = response.data.message;

    // Redirect to login page after successful registration
    setTimeout(() => {
      router.push('/login?message=' + encodeURIComponent(response.data.message));
    }, 2000);
  } catch (err) {
    console.error('Registration failed:', err);
    const errorMessage = err.response?.data?.message || 'Registration failed. Please try again.';
    error.value = errorMessage;
  } finally {
    loading.value = false;
  }
};
</script>

<style scoped>
.register-bg {
  min-height: 100vh;
  min-width: 100vw;
  background: linear-gradient(135deg, #43cea2 0%, #185a9d 100%);
  display: flex;
  align-items: center;
  justify-content: center;
}
.fill-height {
  min-height: 100vh !important;
}
.v-card {
  border-radius: 18px;
}
</style>
