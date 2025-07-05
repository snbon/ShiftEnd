import { createRouter, createWebHistory } from 'vue-router';

const routes = [
  { path: '/', name: 'Dashboard', component: () => import('./views/Dashboard.vue') },
  { path: '/onboarding', name: 'Onboarding', component: () => import('./views/Onboarding.vue') },
  { path: '/add-report', name: 'AddReport', component: () => import('./views/AddReport.vue') },
  { path: '/reports/:id', name: 'ViewReport', component: () => import('./views/ViewReport.vue') },
  { path: '/reports/:id/edit', name: 'EditReport', component: () => import('./views/AddReport.vue') },
  { path: '/history', name: 'History', component: () => import('./views/History.vue') },
  { path: '/locations', name: 'Locations', component: () => import('./views/Locations.vue') },
  // { path: '/team', name: 'Team', component: () => import('./views/Team.vue') },
  { path: '/pending-users', name: 'PendingUsers', component: () => import('./views/PendingUsers.vue') },
  { path: '/settings', name: 'Settings', component: () => import('./views/Settings.vue') },
  { path: '/login', name: 'Login', component: () => import('./views/Login.vue') },
  { path: '/register', name: 'Register', component: () => import('./views/Register.vue') },
  { path: '/invite/:inviteCode', name: 'Invitation', component: () => import('./views/Invitation.vue') },
  {
    path: '/enhanced-team',
    name: 'EnhancedTeamManagement',
    component: () => import('./views/EnhancedTeamManagement.vue'),
    meta: { requiresAuth: true, ownerOnly: true },
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

// Add a global navigation guard to allow public access to /invite/:inviteCode, /login, /register
router.beforeEach(async (to, from, next) => {
  // Allow /login, /register, /invite, and /invite/:inviteCode as public
  const isLogin = to.path === '/login' || to.path.startsWith('/login?');
  const isRegister = to.path === '/register' || to.path.startsWith('/register?');
  const isInvite = to.path === '/invite' || to.path.startsWith('/invite/');
  const isPublic = isLogin || isRegister || isInvite;
  const user = JSON.parse(localStorage.getItem('user'));
  if (!isPublic && !user) {
    return next('/login');
  }
  if (to.meta.requiresAuth && !user) {
    return next('/login');
  }
  if (to.meta.ownerOnly && (!user || !user.locations || !user.locations.some(l => l.pivot && l.pivot.role === 'owner'))) {
    return next('/');
  }
  next();
});

export default router;
