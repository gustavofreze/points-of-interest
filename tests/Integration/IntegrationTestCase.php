<?php

declare(strict_types=1);

namespace Test\Integration;

use DI\Container;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use PointsOfInterest\Dependencies;
use Psr\Container\ContainerInterface;

abstract class IntegrationTestCase extends TestCase
{
    private static Fixtures $fixtures;
    private static ContainerInterface $container;

    public static function setUpBeforeClass(): void
    {
        self::$container = new Container(Dependencies::definitions());

        /** @var Connection $connection */
        $connection = self::$container->get(Connection::class);

        self::$fixtures = Fixtures::from(connection: $connection);
        self::$fixtures->purgeAll();
    }

    protected function tearDown(): void
    {
        self::$fixtures->purgeAll();
    }

    public function get(string $class): mixed
    {
        return self::$container->get($class);
    }

    public function fixtures(): Fixtures
    {
        return self::$fixtures;
    }
}
