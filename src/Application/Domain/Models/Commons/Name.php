<?php

declare(strict_types=1);

namespace PointsOfInterest\Application\Domain\Models\Commons;

use PointsOfInterest\Application\Domain\Exceptions\EmptyName;
use PointsOfInterest\Application\Domain\Exceptions\NameTooLong;

final readonly class Name implements ValueObject
{
    use ValueObjectBehavior;

    private const int MAXIMUM_LENGTH = 255;

    private function __construct(public string $value)
    {
    }

    public static function from(string $value): Name
    {
        if ($value === '') {
            throw new EmptyName();
        }

        $length = mb_strlen($value);

        if ($length > Name::MAXIMUM_LENGTH) {
            throw new NameTooLong(current: $length, maximum: Name::MAXIMUM_LENGTH);
        }

        return new Name(value: $value);
    }
}
