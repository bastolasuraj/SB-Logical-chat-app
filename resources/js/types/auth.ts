// Authentication related TypeScript interfaces

export interface User {
  id: number;
  name: string;
  email: string;
  email_verified_at: string | null;
  avatar?: string;
  last_seen_at: string;
  created_at: string;
  updated_at: string;
}

export interface LoginCredentials {
  email: string;
  password: string;
}

export interface RegisterData {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
}

export interface EmailVerificationData {
  code?: string;
  token?: string;
}

export interface AuthResponse {
  user: User;
  token?: string;
  message?: string;
}

export interface ValidationError {
  message: string;
  errors: Record<string, string[]>;
}

export interface ApiError {
  message: string;
  status: number;
}