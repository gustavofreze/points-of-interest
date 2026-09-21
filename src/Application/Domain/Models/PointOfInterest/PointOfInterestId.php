<?php

declare(strict_types=1);

namespace PointsOfInterest\Application\Domain\Models\PointOfInterest;

use PointsOfInterest\Application\Domain\Models\Commons\AggregateIdentity;
use PointsOfInterest\Application\Domain\Models\Commons\UniqueIdentifier;
use PointsOfInterest\Application\Domain\Models\Commons\ValueObject;
use PointsOfInterest\Application\Domain\Models\Commons\ValueObjectBehavior;

final readonly class PointOfInterestId implements AggregateIdentity, ValueObject
{
    use ValueObjectBehavior;

    public function __construct(private string $value)
    {
    }

    public static function generate(): PointOfInterestId
    {
        return new PointOfInterestId(value: UniqueIdentifier::generate()->toString());
    }

    public function identityValue(): string
    {
        return $this->value;
    }
}
