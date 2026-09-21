<?php

declare(strict_types=1);

namespace PointsOfInterest\Query\PointOfInterest\FindAll\Database;

final readonly class Queries
{
    public const string BASE = "
        SELECT BIN_TO_UUID(poi.id) AS id,
               poi.name            AS name,
               poi.x_coordinate    AS x_coordinate,
               poi.y_coordinate    AS y_coordinate,
               DATE_FORMAT(poi.created_at, '%Y-%m-%dT%H:%i:%s.%f+00:00') AS created_at
        FROM points_of_interest AS poi
    ";
}
