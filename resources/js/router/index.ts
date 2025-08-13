import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import LoginView from '../views/auth/LoginView.vue';
import RegisterView from '../views/auth/RegisterView.vue';
import EmailVerificationView from '../views/auth/EmailVerificationView.vue';

const routes = [
  {
    path: '/',
    redirect: '/login'
  },
  {
    path: '/login',
    name: 'login',
    component: LoginView,
    meta: { requiresGuest: true }
  },
  {
    path: '/register',
    name: 'register',
    component: RegisterView,
    meta: { requiresGuest: true }
  },
  {
    path: '/verify-email',
    name: 'verify-email',
    component: EmailVerificationView,
    meta: { requiresAuth: false }
  },
  {
    path: '/chat',
    name: 'chat',
    component: () => import('../views/ChatView.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/forgot-password',
    name: 'forgot-password',
    component: () => import('../views/auth/ForgotPasswordView.vue'),
    meta: { requiresGuest: true }
  }
];

const router = createRouter({
  history: createWebHistory(),
  routes
});

// Navigation guards
router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore();
  
  // Initialize auth state if not already done
  if (!authStore.user && authStore.token) {
    try {
      await authStore.initializeAuth();
    } catch (error) {
      // Auth initialization failed, continue with navigation
      console.error('Auth initialization failed:', error);
    }
  }

  const requiresAuth = to.meta.requiresAuth;
  const requiresGuest = to.meta.requiresGuest;
  const isAuthenticated = authStore.isAuthenticated;
  const isEmailVerified = authStore.isEmailVerified;

  if (requiresAuth && !isAuthenticated) {
    // Route requires authentication but user is not authenticated
    next('/login');
  } else if (requiresAuth && isAuthenticated && !isEmailVerified && to.name !== 'verify-email') {
    // User is authenticated but email is not verified
    next('/verify-email');
  } else if (requiresGuest && isAuthenticated) {
    // Route requires guest but user is authenticated
    if (isEmailVerified) {
      next('/chat');
    } else {
      next('/verify-email');
    }
  } else {
    // All checks passed, proceed with navigation
    next();
  }
});

export default router;