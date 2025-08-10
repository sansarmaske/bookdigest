<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BookAutocompleteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => [
                'required',
                'string',
                'min:3',
                'max:255',
                'regex:/^[\p{L}\p{N}\s\-\.\,\!\?\:\;]+$/u' // Allow letters, numbers, spaces, and common punctuation
            ]
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Book title is required.',
            'title.string' => 'Book title must be a string.',
            'title.min' => 'Book title must be at least 3 characters long.',
            'title.max' => 'Book title must not exceed 255 characters.',
            'title.regex' => 'Book title contains invalid characters.'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'title' => 'book title'
        ];
    }
}
