<template>
  <v-container fluid>
    <v-row>
      <v-col cols="12">
        <h1 class="text-h4 mb-4">Dashboard</h1>
      </v-col>
    </v-row>

    <v-row>
      <v-col cols="12" md="6" lg="3">
        <v-card>
          <v-card-title>Total Sales</v-card-title>
          <v-card-text>
            <div class="text-h3">${{ formatCurrency(totalSales) }}</div>
            <div class="text-caption">This week</div>
          </v-card-text>
        </v-card>
      </v-col>

      <v-col cols="12" md="6" lg="3">
        <v-card>
          <v-card-title>Total Tips</v-card-title>
          <v-card-text>
            <div class="text-h3">${{ formatCurrency(totalTips) }}</div>
            <div class="text-caption">This week</div>
          </v-card-text>
        </v-card>
      </v-col>

      <v-col cols="12" md="6" lg="3">
        <v-card>
          <v-card-title>Reports</v-card-title>
          <v-card-text>
            <div class="text-h3">{{ reports.length }}</div>
            <div class="text-caption">Total reports</div>
          </v-card-text>
        </v-card>
      </v-col>

      <v-col cols="12" md="6" lg="3">
        <v-card>
          <v-card-title>Pending Approval</v-card-title>
          <v-card-text>
            <div class="text-h3">{{ pendingReports.length }}</div>
            <div class="text-caption">Awaiting review</div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <v-row>
      <v-col cols="12" md="8">
        <v-card>
          <v-card-title class="d-flex justify-space-between align-center">
            Recent Reports
            <v-btn color="primary" @click="$router.push('/add-report')" prepend-icon="mdi-plus">
              Add Report
            </v-btn>
          </v-card-title>
          <v-card-text>
            <v-data-table
              :headers="reportHeaders"
              :items="reports"
              :loading="loading"
              class="elevation-1"
            >
              <template #item.report_date="{ item }">
                {{ formatDate(item.report_date) }}
              </template>
              <template #item.total_sales="{ item }">
                ${{ formatCurrency(item.total_sales) }}
              </template>
              <template #item.total_tips="{ item }">
                ${{ formatCurrency(item.total_tips) }}
              </template>
              <template #item.status="{ item }">
                <v-chip
                  :color="getStatusColor(item.status)"
                  :text="item.status"
                  size="small"
                />
              </template>
              <template #item.actions="{ item }">
                <v-btn
                  icon="mdi-eye"
                  size="small"
                  variant="text"
                  @click="viewReport(item.id)"
                />
                <v-btn
                  v-if="item.status === 'draft'"
                  icon="mdi-pencil"
                  size="small"
                  variant="text"
                  @click="editReport(item.id)"
                />
              </template>
            </v-data-table>
          </v-card-text>
        </v-card>
      </v-col>

      <v-col cols="12" md="4">
        <v-card>
          <v-card-title>Quick Actions</v-card-title>
          <v-card-text>
            <v-list>
              <v-list-item
                prepend-icon="mdi-plus"
                title="Create New Report"
                @click="$router.push('/add-report')"
              />
              <v-list-item
                prepend-icon="mdi-history"
                title="View All Reports"
                @click="$router.push('/history')"
              />
              <v-list-item
                v-if="user?.role === 'owner'"
                prepend-icon="mdi-store"
                title="Manage Locations"
                @click="$router.push('/locations')"
              />
              <v-list-item
                v-if="user?.role === 'owner' || user?.role === 'manager'"
                prepend-icon="mdi-account-group"
                title="Team Management"
                @click="$router.push('/team')"
              />
            </v-list>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
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

const reportHeaders = [
  { title: 'Date', key: 'report_date' },
  { title: 'Location', key: 'location.name' },
  { title: 'Total Sales', key: 'total_sales' },
  { title: 'Total Tips', key: 'total_tips' },
  { title: 'Status', key: 'status' },
  { title: 'Actions', key: 'actions', sortable: false },
];

const totalSales = computed(() => {
  return reports.value.reduce((sum, report) => sum + parseFloat(report.total_sales || 0), 0);
});

const totalTips = computed(() => {
  return reports.value.reduce((sum, report) => sum + parseFloat(report.total_tips || 0), 0);
});

const pendingReports = computed(() => {
  return reports.value.filter(report => report.status === 'submitted');
});

const fetchReports = async () => {
  loading.value = true;
  try {
    const response = await axios.get('/api/reports');
    reports.value = response.data.data || [];
  } catch (error) {
    console.error('Error fetching reports:', error);
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

const viewReport = (id) => {
  router.push(`/reports/${id}`);
};

const editReport = (id) => {
  router.push(`/reports/${id}/edit`);
};

onMounted(() => {
  fetchUser();
  fetchReports();
});
</script>
