<template>
  <v-container>
    <v-row>
      <v-col cols="12">
        <v-card>
          <v-card-title class="d-flex justify-space-between align-center">
            <span>Report History</span>
            <v-btn
              color="primary"
              @click="$router.push('/add-report')"
              prepend-icon="mdi-plus"
            >
              Add Report
            </v-btn>
          </v-card-title>
          <v-card-text>
            <!-- Filters -->
            <v-row class="mb-4">
              <v-col cols="12" md="3">
                <v-select
                  v-model="filters.status"
                  :items="statusOptions"
                  label="Status"
                  clearable
                  @update:model-value="fetchReports"
                />
              </v-col>
              <v-col cols="12" md="3">
                <v-text-field
                  v-model="filters.search"
                  label="Search"
                  prepend-inner-icon="mdi-magnify"
                  clearable
                  @update:model-value="fetchReports"
                />
              </v-col>
              <v-col cols="12" md="3">
                <v-text-field
                  v-model="filters.dateFrom"
                  label="From Date"
                  type="date"
                  clearable
                  @update:model-value="fetchReports"
                />
              </v-col>
              <v-col cols="12" md="3">
                <v-text-field
                  v-model="filters.dateTo"
                  label="To Date"
                  type="date"
                  clearable
                  @update:model-value="fetchReports"
                />
              </v-col>
            </v-row>

            <!-- Reports Table -->
            <v-data-table
              :headers="headers"
              :items="reports"
              :loading="loading"
              :items-per-page="10"
              class="elevation-1"
            >
              <template v-slot:item.report_date="{ item }">
                {{ formatDate(item.report_date) }}
              </template>
              <template v-slot:item.location.name="{ item }">
                {{ item.location?.name || 'N/A' }}
              </template>
              <template v-slot:item.total_sales="{ item }">
                ${{ formatCurrency(item.total_sales) }}
              </template>
              <template v-slot:item.total_tips="{ item }">
                ${{ formatCurrency(item.total_tips) }}
              </template>
              <template v-slot:item.cash_difference="{ item }">
                <span :class="item.cash_difference >= 0 ? 'text-success' : 'text-error'">
                  ${{ formatCurrency(item.cash_difference) }}
                </span>
              </template>
              <template v-slot:item.status="{ item }">
                <v-chip
                  :color="getStatusColor(item.status)"
                  :text="item.status"
                  size="small"
                />
              </template>
              <template v-slot:item.actions="{ item }">
                <v-menu>
                  <template #activator="{ props }">
                    <v-btn
                      icon="mdi-dots-vertical"
                      size="small"
                      variant="text"
                      v-bind="props"
                    />
                  </template>
                  <v-list>
                    <v-list-item
                      prepend-icon="mdi-eye"
                      title="View Details"
                      @click="viewReport(item.id)"
                    />
                    <v-list-item
                      v-if="item.status === 'draft' && item.user_id === user?.id"
                      prepend-icon="mdi-pencil"
                      title="Edit"
                      @click="editReport(item.id)"
                    />
                    <v-list-item
                      v-if="item.status === 'draft' && item.user_id === user?.id"
                      prepend-icon="mdi-delete"
                      title="Delete"
                      @click="deleteReport(item.id)"
                    />
                    <v-list-item
                      v-if="item.status === 'submitted' && canApprove(item)"
                      prepend-icon="mdi-check"
                      title="Approve"
                      @click="approveReport(item.id, 'approve')"
                    />
                    <v-list-item
                      v-if="item.status === 'submitted' && canApprove(item)"
                      prepend-icon="mdi-close"
                      title="Reject"
                      @click="approveReport(item.id, 'reject')"
                    />
                  </v-list>
                </v-menu>
              </template>
            </v-data-table>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Approval Dialog -->
    <v-dialog v-model="showApprovalDialog" max-width="500px">
      <v-card>
        <v-card-title>Approve/Reject Report</v-card-title>
        <v-card-text>
          <v-textarea
            v-model="approvalNotes"
            label="Notes (optional)"
            placeholder="Add any notes about this approval/rejection..."
            rows="3"
          />
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn
            color="success"
            @click="confirmApproval('approve')"
            :loading="approvalLoading"
          >
            Approve
          </v-btn>
          <v-btn
            color="error"
            @click="confirmApproval('reject')"
            :loading="approvalLoading"
          >
            Reject
          </v-btn>
          <v-btn
            variant="outlined"
            @click="showApprovalDialog = false"
            :disabled="approvalLoading"
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
import { useRouter } from 'vue-router';
import axios from 'axios';

const router = useRouter();
const reports = ref([]);
const loading = ref(false);
const user = ref(null);
const showApprovalDialog = ref(false);
const approvalLoading = ref(false);
const approvalNotes = ref('');
const currentReportId = ref(null);
const currentAction = ref('');
const showMessage = ref(false);
const message = ref('');
const messageType = ref('success');

const filters = ref({
  status: '',
  search: '',
  dateFrom: '',
  dateTo: '',
});

const statusOptions = [
  { title: 'Draft', value: 'draft' },
  { title: 'Submitted', value: 'submitted' },
  { title: 'Approved', value: 'approved' },
  { title: 'Rejected', value: 'rejected' },
];

const headers = [
  { title: 'Date', key: 'report_date', sortable: true },
  { title: 'Location', key: 'location.name', sortable: true },
  { title: 'Total Sales', key: 'total_sales', sortable: true },
  { title: 'Total Tips', key: 'total_tips', sortable: true },
  { title: 'Cash Difference', key: 'cash_difference', sortable: true },
  { title: 'Status', key: 'status', sortable: true },
  { title: 'Actions', key: 'actions', sortable: false },
];

const fetchReports = async () => {
  loading.value = true;
  try {
    const response = await axios.get('/api/reports');
    reports.value = response.data.data || [];
  } catch (error) {
    console.error('Error fetching reports:', error);
    showErrorMessage('Failed to load reports');
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

const getStatusColor = (status) => {
  const colors = {
    draft: 'grey',
    submitted: 'orange',
    approved: 'green',
    rejected: 'red'
  };
  return colors[status] || 'grey';
};

const canApprove = (report) => {
  if (!user.value) return false;
  if (user.value.role === 'owner') return true;
  if (user.value.role === 'manager' && user.value.location_id === report.location_id) return true;
  return false;
};

const viewReport = (id) => {
  router.push(`/reports/${id}`);
};

const editReport = (id) => {
  router.push(`/reports/${id}/edit`);
};

const deleteReport = async (id) => {
  if (!confirm('Are you sure you want to delete this report?')) return;

  try {
    await axios.delete(`/api/reports/${id}`);
    showSuccessMessage('Report deleted successfully');
    fetchReports();
  } catch (error) {
    console.error('Error deleting report:', error);
    showErrorMessage('Failed to delete report');
  }
};

const approveReport = (id, action) => {
  currentReportId.value = id;
  currentAction.value = action;
  showApprovalDialog.value = true;
};

const confirmApproval = async (action) => {
  approvalLoading.value = true;
  try {
    await axios.post(`/api/reports/${currentReportId.value}/approve`, {
      action: action,
      notes: approvalNotes.value
    });

    showSuccessMessage(`Report ${action}ed successfully`);
    showApprovalDialog.value = false;
    approvalNotes.value = '';
    fetchReports();
  } catch (error) {
    console.error('Error approving report:', error);
    showErrorMessage('Failed to approve report');
  } finally {
    approvalLoading.value = false;
  }
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
  fetchUser();
  fetchReports();
});
</script>
