<?php

declare(strict_types=1);

namespace PointsOfInterest\Driver\Http\Endpoints\PointOfInterest\Register\Dtos;

use PointsOfInterest\Domain\Models\Coordinate;
use PointsOfInterest\Domain\Models\Name;
use PointsOfInterest\Domain\Models\PointOfInterest;
use PointsOfInterest\Driver\Http\Endpoints\PointOfInterest\InvalidRequest;
use Respect\Validation\Exceptions\NestedValidationException;
use Respect\Validation\Validator;

final readonly class Request
{
    public function __construct(private array $payload)
    {
        $this->validate();
    }

    public function toPointOfInterest(): PointOfInterest
    {
        return new PointOfInterest(
            name: new Name(value: (string)$this->payload['name']),
            xCoordinate: new Coordinate(value: (int)$this->payload['point']['x_coordinate']),
            yCoordinate: new Coordinate(value: (int)$this->payload['point']['y_coordinate'])
        );
    }

    private function validate(): void
    {
        try {
            Validator::key('name', Validator::stringType())
                ->key(
                    'point',
                    Validator::key('x_coordinate', Validator::intType())
                        ->key('y_coordinate', Validator::intType())
                )
                ->assert($this->payload);
        } catch (NestedValidationException $exception) {
            throw new InvalidRequest(errors: $exception->getMessages());
        }
    }
}
