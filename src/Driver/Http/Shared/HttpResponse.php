<?php

namespace PointsOfInterest\Driver\Http\Shared;

interface HttpResponse
{
    /**
     * Converts the response to an array.
     *
     * @return array The response as an array.
     */
    public function toArray(): array;
}
