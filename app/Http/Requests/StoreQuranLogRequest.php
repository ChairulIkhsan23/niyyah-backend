<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuranLogRequest extends FormRequest
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
            'pages' => 'required|integer|min:1|max:604',
            'minutes' => 'required|integer|min:1|max:1440'
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
            'pages.required' => 'Jumlah halaman wajib diisi',
            'pages.integer' => 'Jumlah halaman harus angka',
            'pages.min' => 'Jumlah halaman minimal 1',
            'pages.max' => 'Jumlah halaman maksimal 604',
            'minutes.required' => 'Durasi membaca wajib diisi',
            'minutes.integer' => 'Durasi membaca harus angka',
            'minutes.min' => 'Durasi membaca minimal 1 menit',
            'minutes.max' => 'Durasi membaca maksimal 1440 menit (24 jam)'
        ];
    }
}