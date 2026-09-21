<?php

declare(strict_types=1);

namespace PointsOfInterest;

use TinyBlocks\EnvironmentVariable\EnvironmentVariable;

final readonly class AppSettings
{
    private function __construct(public bool $debug, public string $source, public string $appName)
    {
    }

    public static function fromEnvironment(): AppSettings
    {
        return new AppSettings(
            debug: EnvironmentVariable::from(name: 'DEBUG')->toBoolean(),
            source: EnvironmentVariable::from(name: 'SOURCE')->toString(),
            appName: EnvironmentVariable::from(name: 'APP_NAME')->toString()
        );
    }
}
