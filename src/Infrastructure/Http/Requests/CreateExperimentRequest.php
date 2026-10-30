<?php

declare(strict_types=1);

namespace Lab\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Lab\Domain\Experiment\Experiment;

final class CreateExperimentRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'mechanism' => ['required', Rule::in(Experiment::MECHANISMS)],
            'test_user_id' => ['required', 'integer', 'min:1', 'exists:users,id'],
            'resources' => ['required', 'array', 'min:1'],
            'resources.*' => ['required', 'string', 'distinct', Rule::in(Experiment::RESOURCES)],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del experimento es obligatorio',
            'name.max' => 'El nombre no puede superar los 255 caracteres',
            'mechanism.required' => 'Debe seleccionar un mecanismo',
            'mechanism.in' => 'Debe seleccionar un mecanismo válido',
            'test_user_id.required' => 'Debe seleccionar un usuario de prueba',
            'test_user_id.exists' => 'El usuario de prueba no existe',
            'resources.required' => 'Debe seleccionar un recurso',
            'resources.min' => 'Debe seleccionar un recurso',
            'resources.*.in' => 'El recurso seleccionado no está permitido',
        ];
    }
}
