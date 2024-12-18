<?php

declare(strict_types=1);

namespace PointsOfInterest\Starter;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use PDO;
use PointsOfInterest\Domain\Ports\Outbound\Points;
use PointsOfInterest\Driven\PointsOfInterest\Repository\Adapter as PointsAdapter;

use function DI\autowire;

final readonly class Dependencies
{
    public static function definitions(): array
    {
        return [
            Points::class     => autowire(PointsAdapter::class),
            Connection::class => function () {
                return DriverManager::getConnection(
                    [
                        'driver'        => 'pdo_mysql',
                        'host'          => Environment::get(variable: 'DATABASE_HOST')->toString(),
                        'user'          => Environment::get(variable: 'DATABASE_USER')->toString(),
                        'port'          => Environment::get(variable: 'DATABASE_PORT')->toInt(),
                        'dbname'        => Environment::get(variable: 'DATABASE_NAME')->toString(),
                        'password'      => Environment::get(variable: 'DATABASE_PASSWORD')->toString(),
                        'driverOptions' => [PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8']
                    ],
                    new Configuration()
                );
            }
        ];
    }
}
