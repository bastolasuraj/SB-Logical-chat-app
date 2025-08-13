import { describe, it, expect } from 'vitest';
import { useValidation } from '../../composables/useValidation';

describe('useValidation', () => {
  it('should validate required fields', () => {
    const { validate } = useValidation();
    
    const values = { email: '', password: 'test123' };
    const rules = {
      email: [{ required: true }],
      password: [{ required: true }]
    };
    
    const isValid = validate(values, rules);
    
    expect(isValid).toBe(false);
  });

  it('should validate email format', () => {
    const { validate, errors } = useValidation();
    
    const values = { email: 'invalid-email' };
    const rules = {
      email: [{ email: true }]
    };
    
    const isValid = validate(values, rules);
    
    expect(isValid).toBe(false);
    expect(errors.value.email).toBe('Please enter a valid email address');
  });

  it('should validate minimum length', () => {
    const { validate, errors } = useValidation();
    
    const values = { password: '123' };
    const rules = {
      password: [{ minLength: 8 }]
    };
    
    const isValid = validate(values, rules);
    
    expect(isValid).toBe(false);
    expect(errors.value.password).toBe('password must be at least 8 characters');
  });

  it('should validate password confirmation', () => {
    const { validate, errors } = useValidation();
    
    const values = { password: 'password123', password_confirmation: 'different' };
    const rules = {
      password_confirmation: [{ confirmed: 'password' }]
    };
    
    const isValid = validate(values, rules);
    
    expect(isValid).toBe(false);
    expect(errors.value.password_confirmation).toBe('password_confirmation confirmation does not match');
  });

  it('should pass validation with valid data', () => {
    const { validate, errors } = useValidation();
    
    const values = { 
      email: 'test@example.com', 
      password: 'password123',
      password_confirmation: 'password123'
    };
    const rules = {
      email: [{ required: true }, { email: true }],
      password: [{ required: true }, { minLength: 8 }],
      password_confirmation: [{ confirmed: 'password' }]
    };
    
    const isValid = validate(values, rules);
    
    expect(isValid).toBe(true);
    expect(Object.keys(errors.value)).toHaveLength(0);
  });

  it('should clear errors', () => {
    const { validate, clearErrors, errors } = useValidation();
    
    // First create some errors
    const values = { email: '' };
    const rules = { email: [{ required: true }] };
    validate(values, rules);
    
    expect(Object.keys(errors.value)).toHaveLength(1);
    
    clearErrors();
    
    expect(Object.keys(errors.value)).toHaveLength(0);
  });

  it('should set server errors', () => {
    const { setServerErrors, errors } = useValidation();
    
    const serverErrors = {
      email: ['Email is already taken'],
      password: ['Password is too weak', 'Password must contain numbers']
    };
    
    setServerErrors(serverErrors);
    
    expect(errors.value.email).toBe('Email is already taken');
    expect(errors.value.password).toBe('Password is too weak');
  });
});