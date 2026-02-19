<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRamadhanDayRequest extends FormRequest
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
            'date' => [
                'required',
                'date',
                'before_or_equal:today',
                Rule::unique('ramadhan_days')->where(function ($query) {
                    return $query->where('user_id', $this->user()->id);
                })
            ],
            'fasting' => 'sometimes|boolean',
            'subuh' => 'sometimes|boolean',
            'dzuhur' => 'sometimes|boolean',
            'ashar' => 'sometimes|boolean',
            'maghrib' => 'sometimes|boolean',
            'isya' => 'sometimes|boolean',
            'tarawih' => 'sometimes|boolean',
            'quran_pages' => 'sometimes|integer|min:0',
            'dzikir_total' => 'sometimes|integer|min:0'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'date.required' => 'Tanggal wajib diisi',
            'date.date' => 'Format tanggal tidak valid',
            'date.unique' => 'Data untuk tanggal ini sudah ada',
            'date.before_or_equal' => 'Tidak bisa mengisi data untuk tanggal mendatang',
            'fasting.boolean' => 'Format puasa tidak valid',
            'subuh.boolean' => 'Format shalat subuh tidak valid',
            'dzuhur.boolean' => 'Format shalat dzuhur tidak valid',
            'ashar.boolean' => 'Format shalat ashar tidak valid',
            'maghrib.boolean' => 'Format shalat maghrib tidak valid',
            'isya.boolean' => 'Format shalat isya tidak valid',
            'tarawih.boolean' => 'Format shalat tarawih tidak valid',
            'quran_pages.integer' => 'Jumlah halaman Quran harus angka',
            'quran_pages.min' => 'Jumlah halaman Quran minimal 0',
            'dzikir_total.integer' => 'Total dzikir harus angka',
            'dzikir_total.min' => 'Total dzikir minimal 0'
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'fasting' => $this->boolean('fasting'),
            'subuh' => $this->boolean('subuh'),
            'dzuhur' => $this->boolean('dzuhur'),
            'ashar' => $this->boolean('ashar'),
            'maghrib' => $this->boolean('maghrib'),
            'isya' => $this->boolean('isya'),
            'tarawih' => $this->boolean('tarawih'),
        ]);
    }
}