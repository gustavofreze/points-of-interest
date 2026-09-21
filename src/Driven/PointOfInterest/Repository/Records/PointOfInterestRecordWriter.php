<?php

declare(strict_types=1);

namespace PointsOfInterest\Driven\PointOfInterest\Repository\Records;

use PointsOfInterest\Application\Domain\Models\PointOfInterest\PointOfInterest;
use PointsOfInterest\Application\Exceptions\PointOfInterestAlreadyExists;
use PointsOfInterest\Driven\PointOfInterest\Repository\Queries;
use PointsOfInterest\Driven\Shared\Database\DatabaseConstraint;
use PointsOfInterest\Driven\Shared\Database\DatabaseFailure;
use PointsOfInterest\Driven\Shared\Database\RelationalConnection;

final readonly class PointOfInterestRecordWriter
{
    public function __construct(private RelationalConnection $connection)
    {
    }

    public function insert(PointOfInterest $pointOfInterest): void
    {
        try {
            $this->connection->execute(sql: Queries::INSERT, bindings: [
                'id'          => $pointOfInterest->id->identityValue(),
                'name'        => $pointOfInterest->name->value,
                'xCoordinate' => $pointOfInterest->coordinates->xCoordinate->value,
                'yCoordinate' => $pointOfInterest->coordinates->yCoordinate->value
            ]);
        } catch (DatabaseFailure $failure) {
            throw match (true) {
                $failure->hasViolated(constraint: DatabaseConstraint::UNIQUE) => new PointOfInterestAlreadyExists(),
                default                                                       => $failure
            };
        }
    }
}
