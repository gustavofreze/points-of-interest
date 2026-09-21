<?php

declare(strict_types=1);

namespace PointsOfInterest\Application\Domain\Models\Commons;

use Ramsey\Uuid\Uuid;

final readonly class UniqueIdentifier
{
    private function __construct(private string $value)
    {
    }

    public static function generate(): UniqueIdentifier
    {
        return new UniqueIdentifier(value: Uuid::uuid7()->toString());
    }

    public function toString(): string
    {
        return $this->value;
    }
}
