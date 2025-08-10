<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255|min:1',
            'author' => 'required|string|max:255|min:1',
            'description' => 'nullable|string|max:2000',
            'publication_year' => 'nullable|integer|min:1000|max:'.(date('Y') + 1),
            'genre' => 'nullable|string|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Book title is required.',
            'title.min' => 'Book title must be at least 1 character.',
            'title.max' => 'Book title must not exceed 255 characters.',
            'author.required' => 'Author name is required.',
            'author.min' => 'Author name must be at least 1 character.',
            'author.max' => 'Author name must not exceed 255 characters.',
            'description.max' => 'Description must not exceed 2000 characters.',
            'publication_year.integer' => 'Publication year must be a valid number.',
            'publication_year.min' => 'Publication year must be after year 1000.',
            'publication_year.max' => 'Publication year cannot be in the future.',
            'genre.max' => 'Genre must not exceed 100 characters.',
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'title' => trim($this->title),
            'author' => trim($this->author),
            'description' => $this->description ? trim($this->description) : null,
            'genre' => $this->genre ? trim($this->genre) : null,
        ]);
    }
}
