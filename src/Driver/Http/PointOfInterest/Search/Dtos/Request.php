<?php

namespace PointsOfInterest\Driver\Http\PointOfInterest\Search\Dtos;

use PointsOfInterest\Domain\Models\Distance;
use PointsOfInterest\Domain\Models\ReferencePoint;
use PointsOfInterest\Driver\Http\Shared\Exceptions\InvalidRequest;
use Respect\Validation\Exceptions\ValidationException;
use Respect\Validation\Validator;

final class Request
{
    public function __construct(private readonly array $request)
    {
        $this->validate();
    }

    public function noHasFilters(): bool
    {
        return empty($this->request);
    }

    public function toDistance(): Distance
    {
        return new Distance(value: $this->request['distance']);
    }

    public function toReferencePoint(): ReferencePoint
    {
        return ReferencePoint::from(
            xCoordinate: $this->request['x_coordinate'],
            yCoordinate: $this->request['y_coordinate']
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
                ->assert($this->request);
        } catch (ValidationException $exception) {
            /** @noinspection PhpPossiblePolymorphicInvocationInspection */
            throw new InvalidRequest(errors: $exception->getMessages());
        }
    }
}
