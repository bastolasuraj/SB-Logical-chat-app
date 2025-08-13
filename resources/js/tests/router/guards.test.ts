import { describe, it, expect, beforeEach, vi } from 'vitest';
import { createRouter, createWebHistory } from 'vue-router';
import { setActivePinia, createPinia } from 'pinia';
import { useAuthStore } from '../../stores/auth';
import type { User } from '../../types/auth';

// Mock components
const MockLoginView = { template: '<div>Login</div>' };
const MockRegisterView = { template: '<div>Register</div>' };
const MockEmailVerificationView = { template: '<div>Email Verification</div>' };
const MockChatView = { template: '<div>Chat</div>' };
const MockForgotPasswordView = { template: '<div>Forgot Password</div>' };

// Create router with the same configuration as the main router
const createTestRouter = () => {
  const routes = [
    {
      path: '/',
      redirect: '/login'
    },
    {
      path: '/login',
      name: 'login',
      component: MockLoginView,
      meta: { requiresGuest: true }
    },
    {
      path: '/register',
      name: 'register',
      component: MockRegisterView,
      meta: { requiresGuest: true }
    },
    {
      path: '/verify-email',
      name: 'verify-email',
      component: MockEmailVerificationView,
      meta: { requiresAuth: false }
    },
    {
      path: '/chat',
      name: 'chat',
      component: MockChatView,
      meta: { requiresAuth: true }
    },
    {
      path: '/forgot-password',
      name: 'forgot-password',
      component: MockForgotPasswordView,
      meta: { requiresGuest: true }
    }
  ];

  const router = createRouter({
    history: createWebHistory(),
    routes
  });

  // Add the same navigation guard as the main router
  router.beforeEach(async (to, from, next) => {
    const authStore = useAuthStore();
    
    const requiresAuth = to.meta.requiresAuth;
    const requiresGuest = to.meta.requiresGuest;
    const isAuthenticated = authStore.isAuthenticated;
    const isEmailVerified = authStore.isEmailVerified;

    if (requiresAuth && !isAuthenticated) {
      next('/login');
    } else if (requiresAuth && isAuthenticated && !isEmailVerified && to.name !== 'verify-email') {
      next('/verify-email');
    } else if (requiresGuest && isAuthenticated) {
      if (isEmailVerified) {
        next('/chat');
      } else {
        next('/verify-email');
      }
    } else {
      next();
    }
  });

  return router;
};

describe('Router Guards', () => {
  let router: ReturnType<typeof createTestRouter>;
  let authStore: ReturnType<typeof useAuthStore>;

  beforeEach(() => {
    setActivePinia(createPinia());
    router = createTestRouter();
    authStore = useAuthStore();
  });

  it('should redirect unauthenticated users to login when accessing protected routes', async () => {
    // User is not authenticated
    authStore.user = null;
    authStore.token = null;

    await router.push('/chat');
    
    expect(router.currentRoute.value.path).toBe('/login');
  });

  it('should redirect authenticated users to chat when accessing guest routes', async () => {
    // User is authenticated and email verified
    authStore.user = {
      id: 1,
      name: 'Test User',
      email: 'test@example.com',
      email_verified_at: '2023-01-01T00:00:00.000Z'
    } as User;
    authStore.token = 'test-token';

    await router.push('/login');
    
    expect(router.currentRoute.value.path).toBe('/chat');
  });

  it('should redirect authenticated but unverified users to email verification', async () => {
    // User is authenticated but email not verified
    authStore.user = {
      id: 1,
      name: 'Test User',
      email: 'test@example.com',
      email_verified_at: null
    } as User;
    authStore.token = 'test-token';

    await router.push('/chat');
    
    expect(router.currentRoute.value.path).toBe('/verify-email');
  });

  it('should redirect authenticated but unverified users from guest routes to email verification', async () => {
    // User is authenticated but email not verified
    authStore.user = {
      id: 1,
      name: 'Test User',
      email: 'test@example.com',
      email_verified_at: null
    } as User;
    authStore.token = 'test-token';

    await router.push('/login');
    
    expect(router.currentRoute.value.path).toBe('/verify-email');
  });

  it('should allow access to email verification page for authenticated users', async () => {
    // User is authenticated but email not verified
    authStore.user = {
      id: 1,
      name: 'Test User',
      email: 'test@example.com',
      email_verified_at: null
    } as User;
    authStore.token = 'test-token';

    await router.push('/verify-email');
    
    expect(router.currentRoute.value.path).toBe('/verify-email');
  });

  it('should allow access to chat for authenticated and verified users', async () => {
    // User is authenticated and email verified
    authStore.user = {
      id: 1,
      name: 'Test User',
      email: 'test@example.com',
      email_verified_at: '2023-01-01T00:00:00.000Z'
    } as User;
    authStore.token = 'test-token';

    await router.push('/chat');
    
    expect(router.currentRoute.value.path).toBe('/chat');
  });

  it('should redirect root path to login for unauthenticated users', async () => {
    // User is not authenticated
    authStore.user = null;
    authStore.token = null;

    await router.push('/');
    
    expect(router.currentRoute.value.path).toBe('/login');
  });
});