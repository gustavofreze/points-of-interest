<?php

declare(strict_types=1);

namespace PointsOfInterest\Driven\PointOfInterest\Repository;

final readonly class Queries
{
    public const string INSERT = '
        INSERT INTO points_of_interest (id, name, x_coordinate, y_coordinate)
        VALUES (UUID_TO_BIN(:id), :name, :xCoordinate, :yCoordinate)
    ';
}
