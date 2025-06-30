import { createRouter, createWebHistory } from 'vue-router';

const routes = [
  { path: '/', name: 'Dashboard', component: () => import('./views/Dashboard.vue') },
  { path: '/onboarding', name: 'Onboarding', component: () => import('./views/Onboarding.vue') },
  { path: '/add-report', name: 'AddReport', component: () => import('./views/AddReport.vue') },
  { path: '/reports/:id', name: 'ViewReport', component: () => import('./views/ViewReport.vue') },
  { path: '/reports/:id/edit', name: 'EditReport', component: () => import('./views/AddReport.vue') },
  { path: '/history', name: 'History', component: () => import('./views/History.vue') },
  { path: '/locations', name: 'Locations', component: () => import('./views/Locations.vue') },
  { path: '/team', name: 'Team', component: () => import('./views/Team.vue') },
  { path: '/pending-users', name: 'PendingUsers', component: () => import('./views/PendingUsers.vue') },
  { path: '/settings', name: 'Settings', component: () => import('./views/Settings.vue') },
  { path: '/login', name: 'Login', component: () => import('./views/Login.vue') },
  { path: '/register', name: 'Register', component: () => import('./views/Register.vue') },
  { path: '/invite/:inviteCode', name: 'Invitation', component: () => import('./views/Invitation.vue') },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

export default router;
