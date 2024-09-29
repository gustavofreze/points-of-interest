<?php

namespace PointsOfInterest\Driver\Http\PointOfInterest\Register\Dtos;

use PointsOfInterest\Domain\Models\Coordinate;
use PointsOfInterest\Domain\Models\Name;
use PointsOfInterest\Domain\Models\PointOfInterest;
use PointsOfInterest\Driver\Http\Shared\Exceptions\InvalidRequest;
use Respect\Validation\Exceptions\ValidationException;
use Respect\Validation\Validator;

final readonly class Request
{
    public function __construct(private array $request)
    {
        $this->validate();
    }

    public function toPointOfInterest(): PointOfInterest
    {
        return new PointOfInterest(
            name: new Name(value: $this->request['name']),
            xCoordinate: new Coordinate(value: $this->request['point']['x_coordinate']),
            yCoordinate: new Coordinate(value: $this->request['point']['y_coordinate'])
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
                ->assert($this->request);
        } catch (ValidationException $exception) {
            /** @noinspection PhpPossiblePolymorphicInvocationInspection */
            throw new InvalidRequest(errors: $exception->getMessages());
        }
    }
}
