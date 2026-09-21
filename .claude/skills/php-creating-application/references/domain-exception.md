# Domain exception shapes

Use this when choosing where a failure class lives and how it is shaped, across the four cases (prohibited native throw,
dedicated domain class, application not-found, and input-rejection carrying the raw value).

```php
<?php

declare(strict_types=1);

// Domain exception shapes. Four cases, each labeled below.

// PROHIBITED. Throwing a native exception directly with a string message.
if ($amountInCents < 1) {
    throw new DomainException('Amount must be positive.');
}

// CORRECT. A dedicated class named after the invariant, empty body when no context is carried.
# src/Application/Domain/Exceptions/InvalidPaymentStatusTransition.php
final class InvalidPaymentStatusTransition extends DomainException
{
}

// NOT A DOMAIN EXCEPTION. A not-found lives under src/Application/Exceptions/ with an empty body
// thrown with no arguments. The placement decision tree (which directory a failure class lives in)
// and the parent-class rule are owned by the php-hex-application-domain rule, read it.
# src/Application/Exceptions/<Aggregate>NotFound.php
final class <Aggregate>NotFound extends RuntimeException
{
}

# at the callsite
throw new <Aggregate>NotFound();

// CORRECT. An input-rejection exception carrying the raw offending value as a primitive, because the
// input failed promotion to a value object or enum and no value object exists yet to carry it.
# src/Application/Domain/Exceptions/UnsupportedProvider.php
final class UnsupportedProvider extends DomainException
{
    public function __construct(public readonly string $value)
    {
    }
}

# at the callsite, where the raw input could not be promoted to the Provider enum
throw new UnsupportedProvider(value: $provider);
```
