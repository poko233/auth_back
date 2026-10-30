<?php

declare(strict_types=1);

namespace Lab\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Lab\Domain\Experiment\Experiment;

/**
 * Validación para iniciar un experimento.
 */
final class StartExperimentRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'mechanism' => ['required', Rule::in(Experiment::MECHANISMS)],
        ];
    }
}
