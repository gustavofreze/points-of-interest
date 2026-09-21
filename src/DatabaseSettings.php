<?php

declare(strict_types=1);

namespace PointsOfInterest;

use TinyBlocks\EnvironmentVariable\EnvironmentVariable;

final readonly class DatabaseSettings
{
    private function __construct(
        public string $host,
        public string $name,
        public int $port,
        public string $user,
        public string $password
    ) {
    }

    public static function fromEnvironment(): DatabaseSettings
    {
        return new DatabaseSettings(
            host: EnvironmentVariable::from(name: 'DATABASE_HOST')->toString(),
            name: EnvironmentVariable::from(name: 'DATABASE_NAME')->toString(),
            port: EnvironmentVariable::from(name: 'DATABASE_PORT')->toInteger(),
            user: EnvironmentVariable::from(name: 'DATABASE_USER')->toString(),
            password: EnvironmentVariable::from(name: 'DATABASE_PASSWORD')->toString()
        );
    }
}
