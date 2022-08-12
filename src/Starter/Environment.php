<?php

namespace PointsOfInterest\Starter;

use RuntimeException;

final class Environment
{
    public static function get(string $variable): string
    {
        $value = getenv($variable);
        $template = 'Environment variable <%s> is missing.';

        return is_string($value)
            ? $value
            : throw new RuntimeException(sprintf($template, $variable));
    }
}
