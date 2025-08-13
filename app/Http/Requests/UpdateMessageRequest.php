<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMessageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization is handled in the controller
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'content' => [
                'required',
                'string',
                'min:1',
                'max:10000',
                function ($attribute, $value, $fail) {
                    // Custom validation for empty content after trimming
                    if (empty(trim($value))) {
                        $fail('The message content cannot be empty or contain only whitespace.');
                    }
                },
            ],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'content.required' => 'Message content is required.',
            'content.string' => 'Message content must be a string.',
            'content.min' => 'Message content must be at least 1 character long.',
            'content.max' => 'Message content cannot exceed 10,000 characters.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'content' => 'message content',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Normalize content
        if ($this->has('content')) {
            $this->merge([
                'content' => $this->normalizeContent($this->input('content'))
            ]);
        }
    }

    /**
     * Normalize content before validation.
     */
    private function normalizeContent(string $content): string
    {
        // Remove null bytes and other control characters except newlines and tabs
        $content = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $content);
        
        // Normalize line endings
        $content = str_replace(["\r\n", "\r"], "\n", $content);
        
        // Limit consecutive newlines to maximum of 3
        $content = preg_replace('/\n{4,}/', "\n\n\n", $content);
        
        return $content;
    }
}