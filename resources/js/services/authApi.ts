import axios from 'axios';
import type { 
  LoginCredentials, 
  RegisterData, 
  EmailVerificationData, 
  AuthResponse, 
  User 
} from '../types/auth';

// Create axios instance with base configuration
const api = axios.create({
  baseURL: '/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

// Request interceptor to add auth token
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('auth_token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Response interceptor to handle errors
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      // Token expired or invalid
      localStorage.removeItem('auth_token');
      window.location.href = '/login';
    }
    return Promise.reject(error);
  }
);

export const authApi = {
  async login(credentials: LoginCredentials): Promise<AuthResponse> {
    try {
      const response = await api.post('/login', credentials);
      return response.data;
    } catch (error: any) {
      throw {
        message: error.response?.data?.message || 'Login failed',
        status: error.response?.status || 500,
        errors: error.response?.data?.errors || {}
      };
    }
  },

  async register(data: RegisterData): Promise<AuthResponse> {
    try {
      const response = await api.post('/register', data);
      return response.data;
    } catch (error: any) {
      throw {
        message: error.response?.data?.message || 'Registration failed',
        status: error.response?.status || 500,
        errors: error.response?.data?.errors || {}
      };
    }
  },

  async verifyEmail(data: EmailVerificationData): Promise<AuthResponse> {
    try {
      const response = await api.post('/email/verify', data);
      return response.data;
    } catch (error: any) {
      throw {
        message: error.response?.data?.message || 'Email verification failed',
        status: error.response?.status || 500,
        errors: error.response?.data?.errors || {}
      };
    }
  },

  async resendVerificationEmail(): Promise<{ message: string }> {
    try {
      const response = await api.post('/email/resend');
      return response.data;
    } catch (error: any) {
      throw {
        message: error.response?.data?.message || 'Failed to resend verification email',
        status: error.response?.status || 500,
        errors: error.response?.data?.errors || {}
      };
    }
  },

  async logout(): Promise<{ message: string }> {
    try {
      const response = await api.post('/logout');
      return response.data;
    } catch (error: any) {
      throw {
        message: error.response?.data?.message || 'Logout failed',
        status: error.response?.status || 500,
        errors: error.response?.data?.errors || {}
      };
    }
  },

  async getUser(): Promise<{ user: User }> {
    try {
      const response = await api.get('/user');
      return response.data;
    } catch (error: any) {
      throw {
        message: error.response?.data?.message || 'Failed to fetch user',
        status: error.response?.status || 500,
        errors: error.response?.data?.errors || {}
      };
    }
  },

  async forgotPassword(email: string): Promise<{ message: string }> {
    try {
      const response = await api.post('/forgot-password', { email });
      return response.data;
    } catch (error: any) {
      throw {
        message: error.response?.data?.message || 'Failed to send password reset email',
        status: error.response?.status || 500,
        errors: error.response?.data?.errors || {}
      };
    }
  }
};