<template>
  <v-container>
    <v-row>
      <v-col cols="12">
        <div class="d-flex align-center mb-4">
          <v-btn icon="mdi-arrow-left" variant="text" @click="$router.back()" class="mr-4" />
          <h1 class="text-h4">Report Details</h1>
        </div>
      </v-col>
    </v-row>

    <v-row v-if="loading">
      <v-col cols="12" class="text-center">
        <v-progress-circular indeterminate color="primary" size="64" />
      </v-col>
    </v-row>

    <v-row v-else-if="report">
      <v-col cols="12" md="8">
        <v-card>
          <v-card-title class="d-flex justify-space-between align-center">
            <span>Report #{{ report.id }}</span>
            <v-chip
              :color="getStatusColor(report.status)"
              :text="report.status"
              size="small"
            />
          </v-card-title>

          <v-card-text>
            <v-row>
              <v-col cols="12" md="6">
                <v-list>
                  <v-list-item>
                    <template #prepend>
                      <v-icon>mdi-calendar</v-icon>
                    </template>
                    <v-list-item-title>Date</v-list-item-title>
                    <v-list-item-subtitle>{{ formatDate(report.report_date) }}</v-list-item-subtitle>
                  </v-list-item>

                  <v-list-item>
                    <template #prepend>
                      <v-icon>mdi-store</v-icon>
                    </template>
                    <v-list-item-title>Location</v-list-item-title>
                    <v-list-item-subtitle>{{ report.location?.name || 'N/A' }}</v-list-item-subtitle>
                  </v-list-item>

                  <v-list-item>
                    <template #prepend>
                      <v-icon>mdi-account</v-icon>
                    </template>
                    <v-list-item-title>Submitted By</v-list-item-title>
                    <v-list-item-subtitle>{{ report.user?.name || 'N/A' }}</v-list-item-subtitle>
                  </v-list-item>
                </v-list>
              </v-col>

              <v-col cols="12" md="6">
                <v-list>
                  <v-list-item>
                    <template #prepend>
                      <v-icon>mdi-cash</v-icon>
                    </template>
                    <v-list-item-title>Total Sales</v-list-item-title>
                    <v-list-item-subtitle>${{ formatCurrency(report.total_sales) }}</v-list-item-subtitle>
                  </v-list-item>

                  <v-list-item>
                    <template #prepend>
                      <v-icon>mdi-gift</v-icon>
                    </template>
                    <v-list-item-title>Total Tips</v-list-item-title>
                    <v-list-item-subtitle>${{ formatCurrency(report.total_tips) }}</v-list-item-subtitle>
                  </v-list-item>

                  <v-list-item>
                    <template #prepend>
                      <v-icon>mdi-clock</v-icon>
                    </template>
                    <v-list-item-title>Created</v-list-item-title>
                    <v-list-item-subtitle>{{ formatDateTime(report.created_at) }}</v-list-item-subtitle>
                  </v-list-item>
                </v-list>
              </v-col>
            </v-row>

            <!-- Additional Report Fields -->
            <v-divider class="my-4"></v-divider>

            <v-row>
              <v-col cols="12" md="6">
                <v-text-field
                  :model-value="report.cash_sales || 0"
                  label="Cash Sales"
                  readonly
                  prepend-icon="mdi-cash"
                />
                <v-text-field
                  :model-value="report.card_sales || 0"
                  label="Card Sales"
                  readonly
                  prepend-icon="mdi-credit-card"
                />
                <v-text-field
                  :model-value="report.delivery_sales || 0"
                  label="Delivery Sales"
                  readonly
                  prepend-icon="mdi-truck-delivery"
                />
              </v-col>

              <v-col cols="12" md="6">
                <v-text-field
                  :model-value="report.cash_tips || 0"
                  label="Cash Tips"
                  readonly
                  prepend-icon="mdi-gift"
                />
                <v-text-field
                  :model-value="report.card_tips || 0"
                  label="Card Tips"
                  readonly
                  prepend-icon="mdi-credit-card"
                />
                <v-text-field
                  :model-value="report.hours_worked || 0"
                  label="Hours Worked"
                  readonly
                  prepend-icon="mdi-clock"
                />
              </v-col>
            </v-row>

            <!-- Notes -->
            <v-divider class="my-4"></v-divider>

            <v-textarea
              :model-value="report.notes || 'No notes provided'"
              label="Notes"
              readonly
              rows="3"
              prepend-icon="mdi-note-text"
            />
          </v-card-text>
        </v-card>
      </v-col>

      <v-col cols="12" md="4">
        <v-card>
          <v-card-title>Actions</v-card-title>
          <v-card-text>
            <v-list>
              <v-list-item
                v-if="report.status === 'draft'"
                prepend-icon="mdi-pencil"
                title="Edit Report"
                @click="editReport"
              />
              <v-list-item
                v-if="report.status === 'submitted' && canApprove"
                prepend-icon="mdi-check"
                title="Approve Report"
                @click="approveReport"
              />
              <v-list-item
                v-if="report.status === 'submitted' && canApprove"
                prepend-icon="mdi-close"
                title="Reject Report"
                @click="rejectReport"
              />
              <v-list-item
                prepend-icon="mdi-delete"
                title="Delete Report"
                @click="deleteReport"
              />
            </v-list>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <v-row v-else>
      <v-col cols="12" class="text-center">
        <v-alert type="error">
          Report not found
        </v-alert>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import axios from 'axios';

const router = useRouter();
const route = useRoute();
const report = ref(null);
const loading = ref(true);
const user = ref(null);

const canApprove = computed(() => {
  return user.value?.role === 'owner' || user.value?.role === 'manager';
});

const fetchReport = async () => {
  loading.value = true;
  try {
    const response = await axios.get(`/api/reports/${route.params.id}`);
    report.value = response.data.data;
  } catch (error) {
    console.error('Error fetching report:', error);
  } finally {
    loading.value = false;
  }
};

const fetchUser = async () => {
  try {
    const response = await axios.get('/api/user');
    user.value = response.data.user;
  } catch (error) {
    console.error('Error fetching user:', error);
  }
};

const formatCurrency = (amount) => {
  return parseFloat(amount || 0).toFixed(2);
};

const formatDate = (date) => {
  return new Date(date).toLocaleDateString();
};

const formatDateTime = (date) => {
  return new Date(date).toLocaleString();
};

const getStatusColor = (status) => {
  const colors = {
    draft: 'grey',
    submitted: 'orange',
    approved: 'green',
    rejected: 'red'
  };
  return colors[status] || 'grey';
};

const editReport = () => {
  router.push(`/reports/${report.value.id}/edit`);
};

const approveReport = async () => {
  try {
    await axios.post(`/api/reports/${report.value.id}/approve`);
    await fetchReport(); // Refresh the report
  } catch (error) {
    console.error('Error approving report:', error);
  }
};

const rejectReport = async () => {
  // Implement reject functionality
  console.log('Reject report');
};

const deleteReport = async () => {
  if (confirm('Are you sure you want to delete this report?')) {
    try {
      await axios.delete(`/api/reports/${report.value.id}`);
      router.push('/');
    } catch (error) {
      console.error('Error deleting report:', error);
    }
  }
};

onMounted(() => {
  fetchUser();
  fetchReport();
});
</script>
