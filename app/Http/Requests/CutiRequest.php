<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CutiRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tanggal_mulai' => 'required|date|after_or_equal:today',
            'tanggal_selesai'   => 'required|date|after_or_equal:tanggal_mulai',
            'alasan'     => 'required|string|min:10',
            'lampiran' => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ];
    }
}
