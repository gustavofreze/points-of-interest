<?php

namespace PointsOfInterest\Starter;

use InvalidArgumentException;

final readonly class Environment
{
    private function __construct(private string $value)
    {
    }

    public static function get(string $variable): Environment
    {
        $value = getenv($variable);
        $template = 'Environment variable <%s> is missing.';

        if (empty($value)) {
            throw new InvalidArgumentException(message: sprintf($template, $variable));
        }

        return new Environment(value: $value);
    }

    public function toInt(): int
    {
        return (int)$this->value;
    }

    public function toString(): string
    {
        return $this->value;
    }
}
