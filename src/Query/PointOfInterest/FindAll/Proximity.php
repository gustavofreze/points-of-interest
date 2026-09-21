<?php

declare(strict_types=1);

namespace PointsOfInterest\Query\PointOfInterest\FindAll;

use PointsOfInterest\Query\Shared\Http\InvalidRequest;

final readonly class Proximity
{
    private const string DISTANCE = 'distance';

    private const string X_COORDINATE = 'x_coordinate';

    private const string Y_COORDINATE = 'y_coordinate';

    private function __construct(public int $distance, public int $xCoordinate, public int $yCoordinate)
    {
    }

    public static function fromQueryParameters(array $parameters): ?Proximity
    {
        $names = [Proximity::DISTANCE, Proximity::X_COORDINATE, Proximity::Y_COORDINATE];
        $present = array_filter($names, static fn(string $name): bool => isset($parameters[$name]));

        if ($present === []) {
            return null;
        }

        if (count($present) !== count($names)) {
            throw new InvalidRequest(
                reason: 'A proximity search takes distance, x_coordinate, and y_coordinate together.'
            );
        }

        $nonNegative = static function (string $name) use ($parameters): int {
            $value = $parameters[$name];

            if (!is_string($value) || preg_match('/^\d+$/', $value) !== 1) {
                throw new InvalidRequest(reason: sprintf('The <%s> must be a non-negative integer.', $name));
            }

            return (int)$value;
        };

        return new Proximity(
            distance: $nonNegative(Proximity::DISTANCE),
            xCoordinate: $nonNegative(Proximity::X_COORDINATE),
            yCoordinate: $nonNegative(Proximity::Y_COORDINATE)
        );
    }

    public function minimumX(): int
    {
        return max(0, ($this->xCoordinate - $this->distance));
    }

    public function maximumX(): int
    {
        return ($this->xCoordinate + $this->distance);
    }

    public function minimumY(): int
    {
        return max(0, ($this->yCoordinate - $this->distance));
    }

    public function maximumY(): int
    {
        return ($this->yCoordinate + $this->distance);
    }

    public function radiusSquared(): int
    {
        return ($this->distance * $this->distance);
    }
}
