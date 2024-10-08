<?php

declare(strict_types=1);

namespace PointsOfInterest\Driven\PointsOfInterest\Repository\Mocks;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\API\ExceptionConverter;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use SensitiveParameter;

final class DriverMock implements Driver
{
    public function connect(#[SensitiveParameter] array $params)
    {
        // TODO: Implement connect() method.
    }

    public function getDatabasePlatform()
    {
        // TODO: Implement getDatabasePlatform() method.
    }

    public function getSchemaManager(Connection $conn, AbstractPlatform $platform)
    {
        // TODO: Implement getSchemaManager() method.
    }

    public function getExceptionConverter(): ExceptionConverter
    {
        // TODO: Implement getExceptionConverter() method.
    }
}
