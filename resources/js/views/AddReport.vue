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
            <v-form @submit.prevent="submitReport" ref="form">
              <v-row>
                <!-- Basic Information -->
                <v-col cols="12" md="6">
                  <v-text-field
                    v-model="form.report_date"
                    label="Report Date"
                    type="date"
                    required
                    :rules="[v => !!v || 'Date is required']"
                  />
                </v-col>
                <v-col cols="12" md="6">
                  <v-text-field
                    v-model="form.shift_start_time"
                    label="Shift Start Time"
                    type="time"
                    required
                    :rules="[v => !!v || 'Start time is required']"
                  />
                </v-col>
                <v-col cols="12" md="6">
                  <v-text-field
                    v-model="form.shift_end_time"
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
                    v-model="form.cash_sales"
                    label="Cash Sales"
                    type="number"
                    step="0.01"
                    min="0"
                    required
                    prefix="$"
                    :rules="[v => v === '' || v >= 0 || 'Cash sales must be positive']"
                  />
                </v-col>
                <v-col cols="12" md="6">
                  <v-text-field
                    v-model="form.card_sales"
                    label="Card Sales"
                    type="number"
                    step="0.01"
                    min="0"
                    required
                    prefix="$"
                    :rules="[v => v === '' || v >= 0 || 'Card sales must be positive']"
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
                    v-model="form.opening_cash"
                    label="Opening Cash"
                    type="number"
                    step="0.01"
                    min="0"
                    required
                    prefix="$"
                    :rules="[v => v === '' || v >= 0 || 'Opening cash must be positive']"
                  />
                </v-col>
                <v-col cols="12" md="6">
                  <v-text-field
                    v-model="form.closing_cash"
                    label="Closing Cash"
                    type="number"
                    step="0.01"
                    min="0"
                    required
                    prefix="$"
                    :rules="[v => v === '' || v >= 0 || 'Closing cash must be positive']"
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
                    v-model="form.tips_cash"
                    label="Cash Tips"
                    type="number"
                    step="0.01"
                    min="0"
                    required
                    prefix="$"
                    :rules="[v => v === '' || v >= 0 || 'Cash tips must be positive']"
                  />
                </v-col>
                <v-col cols="12" md="6">
                  <v-text-field
                    v-model="form.tips_card"
                    label="Card Tips"
                    type="number"
                    step="0.01"
                    min="0"
                    required
                    prefix="$"
                    :rules="[v => v === '' || v >= 0 || 'Card tips must be positive']"
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
                    v-model="form.inventory_notes"
                    label="Inventory Notes"
                    placeholder="Any inventory issues, waste, or stock notes..."
                    rows="3"
                    auto-grow
                  />
                </v-col>
                <v-col cols="12">
                  <v-textarea
                    v-model="form.shift_notes"
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
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';

const router = useRouter();
const form = ref({
  report_date: '',
  shift_start_time: '',
  shift_end_time: '',
  cash_sales: '',
  card_sales: '',
  opening_cash: '',
  closing_cash: '',
  tips_cash: '',
  tips_card: '',
  inventory_notes: '',
  shift_notes: '',
});

const loading = ref(false);
const showMessage = ref(false);
const message = ref('');
const messageType = ref('success');

const totalSales = computed(() => {
  return parseFloat(form.value.cash_sales || 0) + parseFloat(form.value.card_sales || 0);
});

const totalTips = computed(() => {
  return parseFloat(form.value.tips_cash || 0) + parseFloat(form.value.tips_card || 0);
});

const cashDifference = computed(() => {
  const opening = parseFloat(form.value.opening_cash || 0);
  const closing = parseFloat(form.value.closing_cash || 0);
  const cashSales = parseFloat(form.value.cash_sales || 0);
  const cashTips = parseFloat(form.value.tips_cash || 0);
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
    // Convert empty strings to 0 for numeric fields
    const submitData = {
      ...form.value,
      cash_sales: form.value.cash_sales || 0,
      card_sales: form.value.card_sales || 0,
      opening_cash: form.value.opening_cash || 0,
      closing_cash: form.value.closing_cash || 0,
      tips_cash: form.value.tips_cash || 0,
      tips_card: form.value.tips_card || 0,
    };

    const response = await axios.post('/api/reports', submitData);

    if (submitForApproval) {
      // Submit for approval
      await axios.post(`/api/reports/${response.data.data.id}/submit`);
      showSuccessMessage('Report created and submitted for approval successfully!');
    } else {
      showSuccessMessage('Report saved as draft successfully!');
    }

    // Redirect to dashboard after a short delay
    setTimeout(() => {
      router.push('/');
    }, 1500);
  } catch (error) {
    console.error('Error creating report:', error);
    const errorMessage = error.response?.data?.message || 'Failed to create report';
    showErrorMessage(errorMessage);
  } finally {
    loading.value = false;
  }
};

const submitAndSubmit = () => {
  submitReport(true);
};

// Initialize form with today's date
onMounted(() => {
  const today = new Date();
  const year = today.getFullYear();
  const month = String(today.getMonth() + 1).padStart(2, '0');
  const day = String(today.getDate()).padStart(2, '0');
  form.value.report_date = `${year}-${month}-${day}`;
});
</script>
