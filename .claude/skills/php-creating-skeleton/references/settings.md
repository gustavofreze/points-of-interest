# Settings

Use this shape for an app-wide `<Name>Settings` value object whose configuration is fully static, read once from the
environment (byte-for-byte `DatabaseSettings`, apart from the `<RootNamespace>` placeholder on the namespace line). Two
invariants from `php-architecture` § Settings hold on every Settings class: every read uses `EnvironmentVariable::from`
(never `fromOrDefault`, never a code-side fallback, a missing variable fails at startup), and the class declares no
exception and throws nothing of its own (validation beyond presence lives where the value is consumed).

The root namespace below is the PSR-4 prefix read from `composer.json` `autoload.psr-4`, never a literal typed from
memory.

```php
<?php

declare(strict_types=1);

namespace <RootNamespace>;

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
```
