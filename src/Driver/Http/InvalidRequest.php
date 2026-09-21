<?php

declare(strict_types=1);

namespace PointsOfInterest\Driver\Http;

use InvalidArgumentException;

final class InvalidRequest extends InvalidArgumentException
{
    public function __construct(public readonly array $messages)
    {
    }
}
