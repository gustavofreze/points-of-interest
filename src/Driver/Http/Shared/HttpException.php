<?php

namespace PointsOfInterest\Driver\Http\Shared;

use Throwable;

interface HttpException extends Throwable
{
    /**
     * Retrieve an array of errors associated with the HTTP exception.
     *
     * @return array An array containing details about the errors.
     */
    public function getErrors(): array;
}
