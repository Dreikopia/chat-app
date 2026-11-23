<?php

namespace App\Http\Requests\Settings;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AvatarUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'avatar.required' => 'Choose a JPG, PNG, or WebP image up to 2 MB.',
            'avatar.image' => 'Choose a JPG, PNG, or WebP image up to 2 MB.',
            'avatar.mimes' => 'Choose a JPG, PNG, or WebP image up to 2 MB.',
            'avatar.max' => 'Choose a JPG, PNG, or WebP image up to 2 MB.',
        ];
    }
}
