<?php

declare(strict_types=1);

namespace Lab\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Lab\Domain\Experiment\ExperimentStatus;

final class FinishExperimentRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['status' => ['sometimes', 'required', Rule::in([ExperimentStatus::COMPLETED->value, ExperimentStatus::FAILED->value])]];
    }
}
