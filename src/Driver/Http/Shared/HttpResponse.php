<?php

namespace PointsOfInterest\Driver\Http\Shared;

use TinyBlocks\Serializer\Serializer;

interface HttpResponse extends Serializer
{
    public function toArray(): array;
}
