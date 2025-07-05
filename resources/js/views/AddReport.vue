<template>
  <v-container>
    <v-row>
      <v-col cols="12">
        <v-card>
          <v-card-title class="d-flex justify-space-between align-center">
            <span>Create Daily Report</span>
            <v-btn
              icon="mdi-arrow-left"
              variant="text"
              @click="$router.push('/')"
            />
          </v-card-title>
          <v-card-text>
            <!-- Location Information -->
            <v-alert
              v-if="userLocation"
              type="info"
              variant="tonal"
              class="mb-4"
            >
              <strong>Location:</strong> {{ userLocation.name }}
              <div v-if="userLocation.address" class="text-caption mt-1">
                {{ userLocation.address }}
              </div>
            </v-alert>

            <v-form @submit.prevent="submitReport" ref="form">
              <v-row>
                <!-- Basic Information -->
                <v-col cols="12" md="6">
                  <v-text-field
                    v-model="report_date"
                    label="Report Date"
                    type="date"
                    required
                    :rules="[v => !!v || 'Date is required']"
                  />
                </v-col>
                <v-col cols="12" md="6">
                  <v-text-field
                    v-model="shift_start_time"
                    label="Shift Start Time"
                    type="time"
                    required
                    :rules="[v => !!v || 'Start time is required']"
                  />
                </v-col>
                <v-col cols="12" md="6">
                  <v-text-field
                    v-model="shift_end_time"
                    label="Shift End Time"
                    type="time"
                    required
                    :rules="[v => !!v || 'End time is required']"
                  />
                </v-col>

                <!-- Sales Information -->
                <v-col cols="12">
                  <h3 class="text-h6 mb-4">Sales Information</h3>
                </v-col>
                <v-col cols="12" md="6">
                  <v-text-field
                    v-model="cash_sales"
                    label="Cash Sales"
                    required
                    prefix="$"
                  />
                </v-col>
                <v-col cols="12" md="6">
                  <v-text-field
                    v-model="card_sales"
                    label="Card Sales"
                    required
                    prefix="$"
                  />
                </v-col>
                <v-col cols="12">
                  <v-alert type="info" variant="tonal">
                    Total Sales: ${{ formatCurrency(totalSales) }}
                  </v-alert>
                </v-col>

                <!-- Cash Drawer -->
                <v-col cols="12">
                  <h3 class="text-h6 mb-4">Cash Drawer</h3>
                </v-col>
                <v-col cols="12" md="6">
                  <v-text-field
                    v-model="opening_cash"
                    label="Opening Cash"
                    required
                    prefix="$"
                  />
                </v-col>
                <v-col cols="12" md="6">
                  <v-text-field
                    v-model="closing_cash"
                    label="Closing Cash"
                    required
                    prefix="$"
                  />
                </v-col>
                <v-col cols="12">
                  <v-alert
                    :type="cashDifference >= 0 ? 'success' : 'warning'"
                    variant="tonal"
                  >
                    Cash Difference: ${{ formatCurrency(cashDifference) }}
                  </v-alert>
                </v-col>

                <!-- Tips -->
                <v-col cols="12">
                  <h3 class="text-h6 mb-4">Tips</h3>
                </v-col>
                <v-col cols="12" md="6">
                  <v-text-field
                    v-model="tips_cash"
                    label="Cash Tips"
                    required
                    prefix="$"
                  />
                </v-col>
                <v-col cols="12" md="6">
                  <v-text-field
                    v-model="tips_card"
                    label="Card Tips"
                    required
                    prefix="$"
                  />
                </v-col>
                <v-col cols="12">
                  <v-alert type="info" variant="tonal">
                    Total Tips: ${{ formatCurrency(totalTips) }}
                  </v-alert>
                </v-col>

                <!-- Notes -->
                <v-col cols="12">
                  <h3 class="text-h6 mb-4">Notes</h3>
                </v-col>
                <v-col cols="12">
                  <v-textarea
                    v-model="inventory_notes"
                    label="Inventory Notes"
                    placeholder="Any inventory issues, waste, or stock notes..."
                    rows="3"
                    auto-grow
                  />
                </v-col>
                <v-col cols="12">
                  <v-textarea
                    v-model="shift_notes"
                    label="Shift Notes"
                    placeholder="General shift notes, incidents, or observations..."
                    rows="3"
                    auto-grow
                  />
                </v-col>

                <!-- Submit Buttons -->
                <v-col cols="12" class="d-flex gap-4">
                  <v-btn
                    color="primary"
                    type="submit"
                    :loading="loading"
                    :disabled="loading"
                  >
                    Save as Draft
                  </v-btn>
                  <v-btn
                    color="success"
                    @click="submitAndSubmit"
                    :loading="loading"
                    :disabled="loading"
                  >
                    Save & Submit for Approval
                  </v-btn>
                  <v-btn
                    variant="outlined"
                    @click="$router.push('/')"
                    :disabled="loading"
                  >
                    Cancel
                  </v-btn>
                </v-col>
              </v-row>
            </v-form>
          </v-card-text>
        </v-card>
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
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import { useLocationStore } from '../store/location';

const router = useRouter();
const report_date = ref('');
const shift_start_time = ref('');
const shift_end_time = ref('');
const cash_sales = ref('');
const card_sales = ref('');
const opening_cash = ref('');
const closing_cash = ref('');
const tips_cash = ref('');
const tips_card = ref('');
const inventory_notes = ref('');
const shift_notes = ref('');

const loading = ref(false);
const showMessage = ref(false);
const message = ref('');
const messageType = ref('success');
const user = ref(null);
const locationStore = useLocationStore();
const currentLocationId = computed(() => locationStore.currentLocationId);

const userLocation = computed(() => {
  if (!user.value || !user.value.locations) return null;
  return user.value.locations.find(l => l.id === currentLocationId.value);
});

const totalSales = computed(() => {
  return parseFloat(cash_sales.value || 0) + parseFloat(card_sales.value || 0);
});

const totalTips = computed(() => {
  return parseFloat(tips_cash.value || 0) + parseFloat(tips_card.value || 0);
});

const cashDifference = computed(() => {
  const opening = parseFloat(opening_cash.value || 0);
  const closing = parseFloat(closing_cash.value || 0);
  const cashSales = parseFloat(cash_sales.value || 0);
  const cashTips = parseFloat(tips_cash.value || 0);
  return closing - opening - cashSales - cashTips;
});

const formatCurrency = (amount) => {
  return parseFloat(amount || 0).toFixed(2);
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

const submitReport = async (submitForApproval = false) => {
  loading.value = true;
  try {
    // Ensure backend user location is set before submitting report
    await axios.put('/api/users/me/location', { location_id: currentLocationId.value });

    // Convert empty strings to 0 for numeric fields
    const submitData = {
      location_id: currentLocationId.value,
      report_date: report_date.value,
      shift_start_time: shift_start_time.value,
      shift_end_time: shift_end_time.value,
      cash_sales: parseFloat(cash_sales.value || 0),
      card_sales: parseFloat(card_sales.value || 0),
      opening_cash: parseFloat(opening_cash.value || 0),
      closing_cash: parseFloat(closing_cash.value || 0),
      tips_cash: parseFloat(tips_cash.value || 0),
      tips_card: parseFloat(tips_card.value || 0),
      inventory_notes: inventory_notes.value,
      shift_notes: shift_notes.value,
    };

    const response = await axios.post('/api/reports', submitData);

    if (submitForApproval) {
      // Submit for approval
      const submitResponse = await axios.post(`/api/reports/${response.data.data.id}/submit`);
      const submitMessage = submitResponse.data.message;
      showSuccessMessage(submitMessage);
    } else {
      showSuccessMessage('Report saved as draft successfully!');
    }

    // Redirect to dashboard after a short delay
    setTimeout(() => {
      window.location.href = '/';
    }, 1500);
  } catch (error) {
    console.error('Error creating report:', error);
    let errorMessage = 'Failed to create report';
    if (error.response?.data?.message) {
      errorMessage = error.response.data.message;
    } else if (error.response?.data?.errors?.location_id) {
      errorMessage = error.response.data.errors.location_id[0];
    }
    showErrorMessage(errorMessage);
  } finally {
    loading.value = false;
  }
};

const submitAndSubmit = () => {
  submitReport(true);
};

const fetchUserAndLocation = async () => {
  try {
    // Only fetch user data if we don't have it cached
    if (!user.value) {
      const userResponse = await axios.get('/api/user');
      user.value = userResponse.data.user;
    }
  } catch (error) {
    console.error('Error fetching user/location data:', error);
  }
};

// Initialize form with today's date
onMounted(() => {
  const today = new Date();
  const year = today.getFullYear();
  const month = String(today.getMonth() + 1).padStart(2, '0');
  const day = String(today.getDate()).padStart(2, '0');
  report_date.value = `${year}-${month}-${day}`;
  window.addEventListener('user-location-ready', userLocationReadyHandler);
  if (!currentLocationId.value || !user.value) {
    fetchUserAndLocation();
  }
});

onUnmounted(() => {
  window.removeEventListener('user-location-ready', userLocationReadyHandler);
});

function userLocationReadyHandler(e) {
  const { user: userData } = e.detail;
  if (!user.value) {
    user.value = userData;
  }
}
</script>
