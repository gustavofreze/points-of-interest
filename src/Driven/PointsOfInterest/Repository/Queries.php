<?php

declare(strict_types=1);

namespace PointsOfInterest\Driven\PointsOfInterest\Repository;

final class Queries
{
    public const string INSERT = '
        INSERT INTO points_interest (name, x_coordinate, y_coordinate)
        VALUES (?, ?, ?);';

    public const string FIND = '
        SELECT name         AS name,
               x_coordinate AS xCoordinate,
               y_coordinate AS yCoordinate
        FROM points_interest
        WHERE name = ?
          AND x_coordinate = ?
          AND y_coordinate = ?;';

    public const string FIND_ALL = '
        SELECT name         AS name,
               x_coordinate AS xCoordinate,
               y_coordinate AS yCoordinate
        FROM points_interest;';
}
