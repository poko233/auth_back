<?php

declare(strict_types=1);

namespace Lab\Domain\Credential\ApiKey;

/**
 * Puerto de generación de keys planas (alta entropía, única exibición).
 */
interface ApiKeyGenerator
{
    public function generate(): string;
}
