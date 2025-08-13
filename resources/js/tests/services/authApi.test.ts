import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import type { LoginCredentials, RegisterData, EmailVerificationData } from '../../types/auth';
import { authApi } from '../../services/authApi';
import { authApi } from '../../services/authApi';
import { authApi } from '../../services/authApi';
import { authApi } from '../../services/authApi';
import { authApi } from '../../services/authApi';
import { authApi } from '../../services/authApi';
import { authApi } from '../../services/authApi';
import { authApi } from '../../services/authApi';
import { authApi } from '../../services/authApi';

// Mock axios
const mockAxiosInstance = {
  post: vi.fn(),
  get: vi.fn(),
  interceptors: {
    request: { use: vi.fn() },
    response: { use: vi.fn() }
  }
};

vi.mock('axios', () => ({
  default: {
    create: vi.fn(() => mockAxiosInstance)
  }
}));

describe('Auth API Service', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  afterEach(() => {
    vi.resetAllMocks();
  });

  describe('login', () => {
    it('should successfully login with valid credentials', async () => {
      const credentials: LoginCredentials = {
        email: 'test@example.com',
        password: 'password123'
      };

      const mockResponse = {
        data: {
          user: {
            id: 1,
            name: 'Test User',
            email: 'test@example.com',
            email_verified_at: '2023-01-01T00:00:00.000Z'
          },
          token: 'test-token',
          message: 'Login successful'
        }
      };

      mockAxiosInstance.post.mockResolvedValue(mockResponse);

      const { authApi } = await import('../../services/authApi');
      const result = await authApi.login(credentials);

      expect(mockAxiosInstance.post).toHaveBeenCalledWith('/login', credentials);
      expect(result).toEqual(mockResponse.data);
    });

    it('should handle login error with validation errors', async () => {
      const credentials: LoginCredentials = {
        email: 'invalid-email',
        password: ''
      };

      const mockError = {
        response: {
          status: 422,
          data: {
            message: 'Validation failed',
            errors: {
              email: ['The email field must be a valid email address.'],
              password: ['The password field is required.']
            }
          }
        }
      };

      mockAxiosInstance.post.mockRejectedValue(mockError);

      const { authApi } = await import('../../services/authApi');
      await expect(authApi.login(credentials)).rejects.toEqual({
        message: 'Validation failed',
        status: 422,
        errors: {
          email: ['The email field must be a valid email address.'],
          password: ['The password field is required.']
        }
      });
    });

    it('should handle login error with invalid credentials', async () => {
      const credentials: LoginCredentials = {
        email: 'test@example.com',
        password: 'wrongpassword'
      };

      const mockError = {
        response: {
          status: 401,
          data: {
            message: 'Invalid credentials'
          }
        }
      };

      mockAxiosInstance.post.mockRejectedValue(mockError);

      const { authApi } = await import('../../services/authApi');
      await expect(authApi.login(credentials)).rejects.toEqual({
        message: 'Invalid credentials',
        status: 401,
        errors: {}
      });
    });
  });

  describe('register', () => {
    it('should successfully register with valid data', async () => {
      const registerData: RegisterData = {
        name: 'Test User',
        email: 'test@example.com',
        password: 'password123',
        password_confirmation: 'password123'
      };

      const mockResponse = {
        data: {
          user: {
            id: 1,
            name: 'Test User',
            email: 'test@example.com',
            email_verified_at: null
          },
          message: 'Registration successful. Please check your email for verification.'
        }
      };

      mockAxiosInstance.post.mockResolvedValue(mockResponse);

      const result = await authApi.register(registerData);

      expect(mockAxiosInstance.post).toHaveBeenCalledWith('/register', registerData);
      expect(result).toEqual(mockResponse.data);
    });

    it('should handle registration error with validation errors', async () => {
      const registerData: RegisterData = {
        name: '',
        email: 'invalid-email',
        password: '123',
        password_confirmation: '456'
      };

      const mockError = {
        response: {
          status: 422,
          data: {
            message: 'Validation failed',
            errors: {
              name: ['The name field is required.'],
              email: ['The email field must be a valid email address.'],
              password: ['The password field must be at least 8 characters.'],
              password_confirmation: ['The password confirmation does not match.']
            }
          }
        }
      };

      mockAxiosInstance.post.mockRejectedValue(mockError);

      await expect(authApi.register(registerData)).rejects.toEqual({
        message: 'Validation failed',
        status: 422,
        errors: {
          name: ['The name field is required.'],
          email: ['The email field must be a valid email address.'],
          password: ['The password field must be at least 8 characters.'],
          password_confirmation: ['The password confirmation does not match.']
        }
      });
    });
  });

  describe('verifyEmail', () => {
    it('should successfully verify email with code', async () => {
      const verificationData: EmailVerificationData = {
        code: '123456'
      };

      const mockResponse = {
        data: {
          user: {
            id: 1,
            name: 'Test User',
            email: 'test@example.com',
            email_verified_at: '2023-01-01T00:00:00.000Z'
          },
          message: 'Email verified successfully'
        }
      };

      mockAxiosInstance.post.mockResolvedValue(mockResponse);

      const result = await authApi.verifyEmail(verificationData);

      expect(mockAxiosInstance.post).toHaveBeenCalledWith('/email/verify', verificationData);
      expect(result).toEqual(mockResponse.data);
    });

    it('should successfully verify email with token', async () => {
      const verificationData: EmailVerificationData = {
        token: 'verification-token'
      };

      const mockResponse = {
        data: {
          user: {
            id: 1,
            name: 'Test User',
            email: 'test@example.com',
            email_verified_at: '2023-01-01T00:00:00.000Z'
          },
          message: 'Email verified successfully'
        }
      };

      mockAxiosInstance.post.mockResolvedValue(mockResponse);

      const result = await authApi.verifyEmail(verificationData);

      expect(mockAxiosInstance.post).toHaveBeenCalledWith('/email/verify', verificationData);
      expect(result).toEqual(mockResponse.data);
    });
  });

  describe('logout', () => {
    it('should successfully logout', async () => {
      const mockResponse = {
        data: {
          message: 'Logged out successfully'
        }
      };

      mockAxiosInstance.post.mockResolvedValue(mockResponse);

      const result = await authApi.logout();

      expect(mockAxiosInstance.post).toHaveBeenCalledWith('/logout');
      expect(result).toEqual(mockResponse.data);
    });
  });

  describe('getUser', () => {
    it('should successfully get user profile', async () => {
      const mockResponse = {
        data: {
          user: {
            id: 1,
            name: 'Test User',
            email: 'test@example.com',
            email_verified_at: '2023-01-01T00:00:00.000Z',
            avatar_url: 'https://www.gravatar.com/avatar/...',
            last_seen_at: '2023-01-01T00:00:00.000Z',
            is_online: true
          }
        }
      };

      mockAxiosInstance.get.mockResolvedValue(mockResponse);

      const result = await authApi.getUser();

      expect(mockAxiosInstance.get).toHaveBeenCalledWith('/user');
      expect(result).toEqual(mockResponse.data);
    });

    it('should handle unauthorized error when getting user', async () => {
      const mockError = {
        response: {
          status: 401,
          data: {
            message: 'Unauthenticated'
          }
        }
      };

      mockAxiosInstance.get.mockRejectedValue(mockError);

      await expect(authApi.getUser()).rejects.toEqual({
        message: 'Unauthenticated',
        status: 401,
        errors: {}
      });
    });
  });

  describe('resendVerificationEmail', () => {
    it('should successfully resend verification email', async () => {
      const mockResponse = {
        data: {
          message: 'Verification email sent successfully'
        }
      };

      mockAxiosInstance.post.mockResolvedValue(mockResponse);

      const result = await authApi.resendVerificationEmail();

      expect(mockAxiosInstance.post).toHaveBeenCalledWith('/email/resend');
      expect(result).toEqual(mockResponse.data);
    });
  });

  describe('forgotPassword', () => {
    it('should successfully send forgot password email', async () => {
      const email = 'test@example.com';
      const mockResponse = {
        data: {
          message: 'Password reset email sent successfully'
        }
      };

      mockAxiosInstance.post.mockResolvedValue(mockResponse);

      const result = await authApi.forgotPassword(email);

      expect(mockAxiosInstance.post).toHaveBeenCalledWith('/forgot-password', { email });
      expect(result).toEqual(mockResponse.data);
    });
  });
});