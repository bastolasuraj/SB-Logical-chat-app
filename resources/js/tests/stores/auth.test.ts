import { describe, it, expect, beforeEach, vi } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';
import { useAuthStore } from '../../stores/auth';
import type { User, LoginCredentials, RegisterData } from '../../types/auth';

// Mock the authApi
vi.mock('../../services/authApi', () => ({
  authApi: {
    login: vi.fn(),
    register: vi.fn(),
    verifyEmail: vi.fn(),
    resendVerificationEmail: vi.fn(),
    logout: vi.fn(),
    getUser: vi.fn(),
  }
}));

// Mock localStorage
const localStorageMock = {
  getItem: vi.fn(),
  setItem: vi.fn(),
  removeItem: vi.fn(),
  clear: vi.fn(),
};
vi.stubGlobal('localStorage', localStorageMock);

describe('Auth Store', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
    localStorageMock.getItem.mockReturnValue(null);
  });

  it('should initialize with default state', () => {
    const authStore = useAuthStore();
    
    expect(authStore.user).toBeNull();
    expect(authStore.token).toBeNull();
    expect(authStore.isLoading).toBe(false);
    expect(authStore.error).toBeNull();
    expect(authStore.isAuthenticated).toBe(false);
  });

  it('should initialize with token from localStorage', () => {
    localStorageMock.getItem.mockReturnValue('test-token');
    
    const authStore = useAuthStore();
    
    expect(authStore.token).toBe('test-token');
  });

  it('should compute isAuthenticated correctly', () => {
    const authStore = useAuthStore();
    
    expect(authStore.isAuthenticated).toBe(false);
    
    authStore.user = { id: 1, name: 'Test User', email: 'test@example.com' } as User;
    authStore.token = 'test-token';
    
    expect(authStore.isAuthenticated).toBe(true);
  });

  it('should compute isEmailVerified correctly', () => {
    const authStore = useAuthStore();
    
    // When user is null, should be false
    expect(authStore.isEmailVerified).toBe(false);
    
    // When user has no email_verified_at, should be false
    authStore.user = { 
      id: 1, 
      name: 'Test User', 
      email: 'test@example.com',
      email_verified_at: null
    } as User;
    
    expect(authStore.isEmailVerified).toBe(false);
    
    // When user has email_verified_at, should be true
    authStore.user = { 
      id: 1, 
      name: 'Test User', 
      email: 'test@example.com',
      email_verified_at: '2023-01-01T00:00:00.000Z'
    } as User;
    
    expect(authStore.isEmailVerified).toBe(true);
  });

  it('should handle successful login', async () => {
    const { authApi } = await import('../../services/authApi');
    const mockResponse = {
      user: { id: 1, name: 'Test User', email: 'test@example.com' } as User,
      token: 'test-token'
    };
    
    vi.mocked(authApi.login).mockResolvedValue(mockResponse);
    
    const authStore = useAuthStore();
    const credentials: LoginCredentials = { email: 'test@example.com', password: 'password' };
    
    await authStore.login(credentials);
    
    expect(authStore.user).toEqual(mockResponse.user);
    expect(authStore.token).toBe(mockResponse.token);
    expect(localStorageMock.setItem).toHaveBeenCalledWith('auth_token', 'test-token');
    expect(authStore.error).toBeNull();
  });

  it('should handle login error', async () => {
    const { authApi } = await import('../../services/authApi');
    const mockError = { message: 'Invalid credentials', status: 401 };
    
    vi.mocked(authApi.login).mockRejectedValue(mockError);
    
    const authStore = useAuthStore();
    const credentials: LoginCredentials = { email: 'test@example.com', password: 'wrong' };
    
    await expect(authStore.login(credentials)).rejects.toEqual(mockError);
    expect(authStore.error).toBe('Invalid credentials');
    expect(authStore.user).toBeNull();
    expect(authStore.token).toBeNull();
  });

  it('should handle successful registration', async () => {
    const { authApi } = await import('../../services/authApi');
    const mockResponse = {
      user: { id: 1, name: 'Test User', email: 'test@example.com' } as User,
      token: 'test-token'
    };
    
    vi.mocked(authApi.register).mockResolvedValue(mockResponse);
    
    const authStore = useAuthStore();
    const data: RegisterData = { 
      name: 'Test User',
      email: 'test@example.com', 
      password: 'password',
      password_confirmation: 'password'
    };
    
    await authStore.register(data);
    
    expect(authStore.user).toEqual(mockResponse.user);
    expect(authStore.token).toBe(mockResponse.token);
    expect(localStorageMock.setItem).toHaveBeenCalledWith('auth_token', 'test-token');
  });

  it('should handle logout', async () => {
    const { authApi } = await import('../../services/authApi');
    vi.mocked(authApi.logout).mockResolvedValue({ message: 'Logged out' });
    
    const authStore = useAuthStore();
    authStore.user = { id: 1, name: 'Test User', email: 'test@example.com' } as User;
    authStore.token = 'test-token';
    
    await authStore.logout();
    
    expect(authStore.user).toBeNull();
    expect(authStore.token).toBeNull();
    expect(localStorageMock.removeItem).toHaveBeenCalledWith('auth_token');
  });

  it('should clear error', () => {
    const authStore = useAuthStore();
    authStore.error = 'Some error';
    
    authStore.clearError();
    
    expect(authStore.error).toBeNull();
  });
});