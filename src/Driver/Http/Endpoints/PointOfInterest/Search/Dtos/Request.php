<?php

declare(strict_types=1);

namespace PointsOfInterest\Driver\Http\Endpoints\PointOfInterest\Search\Dtos;

use PointsOfInterest\Domain\Models\Distance;
use PointsOfInterest\Domain\Models\ReferencePoint;
use PointsOfInterest\Driver\Http\Endpoints\PointOfInterest\InvalidRequest;
use Respect\Validation\Exceptions\NestedValidationException;
use Respect\Validation\Validator;

final readonly class Request
{
    public function __construct(private array $parameters)
    {
        $this->validate();
    }

    public function noHasFilters(): bool
    {
        return empty($this->parameters);
    }

    public function toDistance(): Distance
    {
        return new Distance(value: (int)$this->parameters['distance']);
    }

    public function toReferencePoint(): ReferencePoint
    {
        return ReferencePoint::from(
            xCoordinate: (int)$this->parameters['x_coordinate'],
            yCoordinate: (int)$this->parameters['y_coordinate']
        );
    }

    private function validate(): void
    {
        if ($this->noHasFilters()) {
            return;
        }

        try {
            Validator::key('distance', Validator::number())
                ->key('x_coordinate', Validator::number())
                ->key('y_coordinate', Validator::number())
                ->assert($this->parameters);
        } catch (NestedValidationException $exception) {
            throw new InvalidRequest(errors: $exception->getMessages());
        }
    }
}
