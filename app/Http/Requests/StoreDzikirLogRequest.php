<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDzikirLogRequest extends FormRequest
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
            'type' => [
                'required',
                'string',
                Rule::in(['tasbih', 'tahmid', 'takbir', 'tahlil', 'istighfar'])
            ],
            'count' => 'required|integer|min:1|max:100000'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'type.required' => 'Jenis dzikir wajib diisi',
            'type.in' => 'Jenis dzikir tidak valid (pilihan: tasbih, tahmid, takbir, tahlil, istighfar)',
            'count.required' => 'Jumlah dzikir wajib diisi',
            'count.integer' => 'Jumlah dzikir harus angka',
            'count.min' => 'Jumlah dzikir minimal 1',
            'count.max' => 'Jumlah dzikir maksimal 100.000'
        ];
    }
}