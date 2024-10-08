<?php

declare(strict_types=1);

namespace PointsOfInterest\Driver\Http\Endpoints\PointOfInterest;

use RuntimeException;

final class InvalidRequest extends RuntimeException
{
    public function __construct(private readonly array $errors)
    {
        parent::__construct();
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
