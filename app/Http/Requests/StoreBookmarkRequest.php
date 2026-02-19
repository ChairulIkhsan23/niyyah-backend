<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBookmarkRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'surah' => 'required|integer|min:1|max:114',
            'ayah' => 'nullable|integer|min:1|max:286',
            'page' => 'nullable|integer|min:1|max:604'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'surah.required' => 'Nomor surah wajib diisi',
            'surah.integer' => 'Nomor surah harus angka',
            'surah.min' => 'Nomor surah minimal 1',
            'surah.max' => 'Nomor surah maksimal 114',
            'ayah.integer' => 'Nomor ayat harus angka',
            'ayah.min' => 'Nomor ayat minimal 1',
            'ayah.max' => 'Nomor ayat maksimal 286',
            'page.integer' => 'Nomor halaman harus angka',
            'page.min' => 'Nomor halaman minimal 1',
            'page.max' => 'Nomor halaman maksimal 604'
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (!$this->ayah && !$this->page) {
                $validator->errors()->add(
                    'ayah',
                    'Minimal salah satu dari ayat atau halaman harus diisi'
                );
            }
        });
    }
}