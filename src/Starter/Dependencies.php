<?php

namespace PointsOfInterest\Starter;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use PointsOfInterest\Domain\Boundaries\Points;
use PointsOfInterest\Driven\PointsOfInterest\Repository\Adapter as PointsAdapter;

use function DI\autowire;

final class Dependencies
{
    public static function definitions(): array
    {
        return [
            Points::class     => autowire(PointsAdapter::class),
            Connection::class => DriverManager::getConnection(['url' => Environment::get('DB_URL')])
        ];
    }
}
