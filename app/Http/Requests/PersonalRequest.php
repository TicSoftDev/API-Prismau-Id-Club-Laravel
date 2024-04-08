<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PersonalRequest extends FormRequest
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
        $usuarioId = $this->id;

        return [
            'Documento' => [
                'required',
                Rule::unique('users', 'Documento')->ignore($usuarioId),
            ],
            'Correo' => [
                'required',
                'email',
                Rule::unique('personal', 'Correo')->ignore($usuarioId, 'user_id'),
                Rule::unique('personal', 'Documento')->ignore($usuarioId, 'user_id'),
            ],
            'Correo' => [
                'required',
                'email',
                Rule::unique('empleados', 'Correo')->ignore($usuarioId, 'user_id'),
                Rule::unique('empleados', 'Documento')->ignore($usuarioId, 'user_id'),
            ],
        ];
    }
}
