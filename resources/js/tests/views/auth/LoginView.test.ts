import { describe, it, expect, beforeEach, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { createRouter, createWebHistory } from 'vue-router';
import LoginView from '../../../views/auth/LoginView.vue';

// Mock the auth store
vi.mock('../../../stores/auth', () => ({
  useAuthStore: () => ({
    login: vi.fn(),
    clearError: vi.fn(),
    isEmailVerified: true,
    error: null
  })
}));

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/login', component: LoginView },
    { path: '/register', component: { template: '<div>Register</div>' } },
    { path: '/chat', component: { template: '<div>Chat</div>' } }
  ]
});

describe('LoginView', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
  });

  it('should render login form', () => {
    const wrapper = mount(LoginView, {
      global: {
        plugins: [router]
      }
    });

    expect(wrapper.find('h2').text()).toBe('Sign in to your account');
    expect(wrapper.find('input[type="email"]').exists()).toBe(true);
    expect(wrapper.find('input[type="password"]').exists()).toBe(true);
    expect(wrapper.find('button[type="submit"]').exists()).toBe(true);
  });

  it('should show validation errors for empty fields', async () => {
    const wrapper = mount(LoginView, {
      global: {
        plugins: [router]
      }
    });

    // Submit form without filling fields
    await wrapper.find('form').trigger('submit.prevent');

    // Wait for validation to run
    await wrapper.vm.$nextTick();

    expect(wrapper.text()).toContain('Email is required');
    expect(wrapper.text()).toContain('Password is required');
  });

  it('should show validation error for invalid email', async () => {
    const wrapper = mount(LoginView, {
      global: {
        plugins: [router]
      }
    });

    // Fill invalid email
    await wrapper.find('input[type="email"]').setValue('invalid-email');
    await wrapper.find('input[type="password"]').setValue('password123');
    
    await wrapper.find('form').trigger('submit.prevent');
    await wrapper.vm.$nextTick();

    expect(wrapper.text()).toContain('Please enter a valid email address');
  });

  it('should have link to register page', () => {
    const wrapper = mount(LoginView, {
      global: {
        plugins: [router]
      }
    });

    const registerLink = wrapper.find('a[href="/register"]');
    expect(registerLink.exists()).toBe(true);
    expect(registerLink.text()).toBe('create a new account');
  });

  it('should have link to forgot password page', () => {
    const wrapper = mount(LoginView, {
      global: {
        plugins: [router]
      }
    });

    const forgotLink = wrapper.find('a[href="/forgot-password"]');
    expect(forgotLink.exists()).toBe(true);
    expect(forgotLink.text()).toBe('Forgot your password?');
  });
});