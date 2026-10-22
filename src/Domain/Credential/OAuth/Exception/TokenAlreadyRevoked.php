<?php

declare(strict_types=1);

namespace Lab\Domain\Credential\OAuth\Exception;

use RuntimeException;

final class TokenAlreadyRevoked extends RuntimeException
{
}
