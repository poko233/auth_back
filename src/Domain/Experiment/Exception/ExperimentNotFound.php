<?php

declare(strict_types=1);

namespace Lab\Domain\Experiment\Exception;

use RuntimeException;

/**
 * Excepción de dominio: el experimento no existe.
 */
final class ExperimentNotFound extends RuntimeException
{
}
