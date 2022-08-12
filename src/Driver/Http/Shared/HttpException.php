<?php

namespace PointsOfInterest\Driver\Http\Shared;

use Throwable;

interface HttpException extends Throwable
{
    public function getErrors(): array;
}
