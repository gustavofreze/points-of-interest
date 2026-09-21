# Typed identifier

Use this when adding an aggregate identifier as a thin wrapper around a Uuid for type safety in signatures.

```php
<?php

declare(strict_types=1);

// Typed identifier skeleton. A thin wrapper around a Uuid for type safety in signatures. It
// implements the Commons aggregate-identity contract (AggregateIdentity, which extends the
// building-blocks identity interface). Identity comparison and equality come from
// ValueObjectBehavior. The inner Uuid is built through the version-specific factory the Uuid wrapper
// exposes (fromV7), and the accessor is the identity contract method identityValue(), never
// toString(). The class adds no other behavior, because an identifier has no operations beyond
// identity.

final readonly class <Aggregate>Id implements AggregateIdentity, ValueObject
{
    use ValueObjectBehavior;

    private function __construct(private Uuid $value)
    {
    }

    public static function from(string $value): <Aggregate>Id
    {
        return new <Aggregate>Id(value: Uuid::fromV7(value: $value));
    }

    public static function generate(): <Aggregate>Id
    {
        return <Aggregate>Id::from(value: Uuid::generateV7()->toString());
    }

    public function identityValue(): string
    {
        return $this->value->toString();
    }
}
```
