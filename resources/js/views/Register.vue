<template>
  <v-container fluid fill-height class="register-container">
    <v-row align="center" justify="center">
      <v-col cols="12" sm="8" md="4">
        <v-card class="elevation-12">
          <v-toolbar color="primary" dark flat>
            <v-toolbar-title>Register</v-toolbar-title>
          </v-toolbar>
          <v-card-text>
            <v-form @submit.prevent="register">
              <v-text-field v-model="name" label="Name" prepend-icon="mdi-account" required />
              <v-text-field v-model="email" label="Email" prepend-icon="mdi-email" type="email" required />
              <v-text-field v-model="password" label="Password" prepend-icon="mdi-lock" type="password" required />
              <v-text-field v-model="password_confirmation" label="Confirm Password" prepend-icon="mdi-lock-check" type="password" required />
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
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';

const router = useRouter();
const name = ref('');
const email = ref('');
const password = ref('');
const password_confirmation = ref('');
const loading = ref(false);
const error = ref('');

const register = async () => {
  loading.value = true;
  error.value = '';
  try {
    await axios.post('/api/register', {
      name: name.value,
      email: email.value,
      password: password.value,
      password_confirmation: password_confirmation.value,
    });
    router.push('/login');
  } catch (err) {
    if (err.response && err.response.data && err.response.data.message) {
      error.value = err.response.data.message;
    } else if (err.response && err.response.data && err.response.data.errors) {
      error.value = Object.values(err.response.data.errors).flat().join(' ');
    } else {
      error.value = 'Registration failed. Please try again.';
    }
  } finally {
    loading.value = false;
  }
};
</script>

<style scoped>
.register-container {
  background: linear-gradient(135deg, #43cea2 0%, #185a9d 100%);
}
</style>
