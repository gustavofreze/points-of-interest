# Settings dynamic key

Use this shape for the `<Name>Settings` variant that also needs a method, allowed only because the env-var key is built
from a parameter at call time. The Settings invariants (`php-architecture` § Settings) still hold: the lookup uses
`EnvironmentVariable::from`, so a template missing for a configured notification type fails loudly instead of running on
stale or absent configuration.

```php
<?php

declare(strict_types=1);

// App-wide <Name>Settings variant with a dynamic env-var key lookup. fromEnvironment() reads the static
// values. A method (here templateFor) is allowed only because the env-var key is built from a parameter
// at call time. Static configuration stays on promoted properties. See php-architecture (section
// Settings).

final readonly class NotificationSettings
{
    private function __construct(public int $maxRetries)
    {
    }

    public static function fromEnvironment(): NotificationSettings
    {
        return new NotificationSettings(
            maxRetries: EnvironmentVariable::from(name: 'NOTIFICATION_MAX_RETRIES')->toInteger()
        );
    }

    public function templateFor(string $notificationType): string
    {
        $template = 'NOTIFICATION_TEMPLATE_%s';

        return EnvironmentVariable::from(name: sprintf($template, strtoupper($notificationType)))->toString();
    }
}
```
