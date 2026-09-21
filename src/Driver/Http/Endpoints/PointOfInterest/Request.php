<?php

declare(strict_types=1);

namespace PointsOfInterest\Driver\Http\Endpoints\PointOfInterest;

use PointsOfInterest\Application\Commands\RegisterPointOfInterest;
use PointsOfInterest\Application\Domain\Models\Commons\Name;
use PointsOfInterest\Application\Domain\Models\PointOfInterest\Coordinates;
use PointsOfInterest\Application\Domain\Models\PointOfInterest\PointOfInterestId;
use PointsOfInterest\Driver\Http\InvalidRequest;
use Respect\Validation\Exceptions\ValidationException;
use Respect\Validation\ValidatorBuilder;

final readonly class Request
{
    public function __construct(private array $payload)
    {
        try {
            $point = ValidatorBuilder::arrayType()
                ->key('x_coordinate', ValidatorBuilder::intType())
                ->key('y_coordinate', ValidatorBuilder::intType());

            ValidatorBuilder::arrayType()
                ->key('name', ValidatorBuilder::stringType())
                ->key('point', $point)
                ->assert($this->payload);
        } catch (ValidationException $exception) {
            throw new InvalidRequest(messages: $exception->getMessages());
        }
    }

    public function toCommand(): RegisterPointOfInterest
    {
        $point = $this->payload['point'];

        return new RegisterPointOfInterest(
            id: PointOfInterestId::generate(),
            name: Name::from(value: (string)$this->payload['name']),
            coordinates: Coordinates::from(
                xCoordinate: (int)$point['x_coordinate'],
                yCoordinate: (int)$point['y_coordinate']
            )
        );
    }
}
