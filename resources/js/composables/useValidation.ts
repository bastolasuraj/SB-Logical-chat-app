import { ref, computed } from 'vue';

export interface ValidationRule {
  required?: boolean;
  email?: boolean;
  minLength?: number;
  confirmed?: string; // field name to confirm against
  message?: string;
}

export interface ValidationRules {
  [key: string]: ValidationRule[];
}

export function useValidation() {
  const errors = ref<Record<string, string>>({});

  const validateField = (value: string, rules: ValidationRule[], fieldName: string, allValues?: Record<string, string>): string | null => {
    for (const rule of rules) {
      if (rule.required && (!value || value.trim() === '')) {
        return rule.message || `${fieldName} is required`;
      }

      if (rule.email && value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
        return rule.message || 'Please enter a valid email address';
      }

      if (rule.minLength && value && value.length < rule.minLength) {
        return rule.message || `${fieldName} must be at least ${rule.minLength} characters`;
      }

      if (rule.confirmed && allValues && value !== allValues[rule.confirmed]) {
        return rule.message || `${fieldName} confirmation does not match`;
      }
    }
    return null;
  };

  const validate = (values: Record<string, string>, rules: ValidationRules): boolean => {
    const newErrors: Record<string, string> = {};
    let isValid = true;

    for (const [fieldName, fieldRules] of Object.entries(rules)) {
      const error = validateField(values[fieldName] || '', fieldRules, fieldName, values);
      if (error) {
        newErrors[fieldName] = error;
        isValid = false;
      }
    }

    errors.value = newErrors;
    return isValid;
  };

  const clearErrors = () => {
    errors.value = {};
  };

  const setServerErrors = (serverErrors: Record<string, string[]>) => {
    const newErrors: Record<string, string> = {};
    for (const [field, messages] of Object.entries(serverErrors)) {
      newErrors[field] = messages[0]; // Take first error message
    }
    errors.value = newErrors;
  };

  const hasErrors = computed(() => Object.keys(errors.value).length > 0);

  return {
    errors,
    validate,
    clearErrors,
    setServerErrors,
    hasErrors
  };
}