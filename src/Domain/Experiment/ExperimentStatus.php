<?php

declare(strict_types=1);

namespace Lab\Domain\Experiment;

/**
 * Estados de un experimento.
 */
enum ExperimentStatus: string
{
    case PENDING = 'PENDING';
    case RUNNING = 'RUNNING';
    case COMPLETED = 'COMPLETED';
    case FAILED = 'FAILED';
}
