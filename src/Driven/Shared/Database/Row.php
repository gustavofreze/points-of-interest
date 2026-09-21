<?php

declare(strict_types=1);

namespace PointsOfInterest\Driven\Shared\Database;

use Closure;

final readonly class Row
{
    private function __construct(private mixed $value)
    {
    }

    public static function from(?array $values): Row
    {
        return new Row(value: $values);
    }

    public function map(Closure $transform): Row
    {
        return is_null($this->value)
            ? new Row(value: null)
            : new Row(value: $transform($this->value));
    }

    public function getOrNull(): mixed
    {
        return $this->value;
    }
}
