<?php

declare(strict_types=1);

namespace PointsOfInterest\Starter;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use PDO;
use PointsOfInterest\Domain\Ports\Outbound\Points;
use PointsOfInterest\Driven\PointsOfInterest\Repository\Adapter as PointsAdapter;
use TinyBlocks\EnvironmentVariable\EnvironmentVariable;

use function DI\autowire;

final readonly class Dependencies
{
    public static function definitions(): array
    {
        return [
            Points::class     => autowire(PointsAdapter::class),
            Connection::class => static fn(): Connection => DriverManager::getConnection([
                'driver'        => 'pdo_mysql',
                'host'          => EnvironmentVariable::from(name: 'DATABASE_HOST')->toString(),
                'user'          => EnvironmentVariable::from(name: 'DATABASE_USER')->toString(),
                'port'          => EnvironmentVariable::from(name: 'DATABASE_PORT')->toInteger(),
                'dbname'        => EnvironmentVariable::from(name: 'DATABASE_NAME')->toString(),
                'password'      => EnvironmentVariable::from(name: 'DATABASE_PASSWORD')->toString(),
                'driverOptions' => [PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8']
            ], new Configuration())
        ];
    }
}
