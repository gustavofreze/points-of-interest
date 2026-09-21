# Gateway payloads

Use to translate the domain to provider fields on the request side and the decoded body back to the domain on the
response side, keeping json handling at the facade.

```php
<?php

declare(strict_types=1);

// Request payload at Providers/<Provider>/Payload/Request/<Resource>.php.
// Maps the domain to the provider fields in from(), exposes the body through toArray() (stripping nulls).
// Never json-encodes, never sets content headers (the facade does that).

final readonly class <Resource>   // Payload/Request/<Resource>.php
{
    private function __construct(
        private ?<type> $<uf>,
        private <type> $<field>
    ) {
    }

    public static function from(<Aggregate> $<aggregate>): <Resource>
    {
        return new <Resource>(
            <uf>: $<aggregate>-><state>->code(),   // domain State translated to the provider UF here
            <field>: $<aggregate>-><valueObject>->value()
        );
    }

    public function toArray(): array
    {
        return array_filter([
            '<providerField>' => $this-><field>,
            '<state>'         => $this-><uf>
        ], static fn(mixed $value): bool => !is_null($value));
    }
}

// Response payload at Providers/<Provider>/Payload/Response/<Resource>.php.
// Parses the decoded body in from() (defensively), maps it back to the domain in toDomain().
// When the domain result needs a value the body does not carry, toDomain takes it as a parameter.

final readonly class <Resource>   // Payload/Response/<Resource>.php
{
    private function __construct(private string $<field>)
    {
    }

    public static function from(array $body): <Resource>
    {
        return new <Resource>(<field>: $body['<providerField>']);
    }

    public function toDomain(): <Result>
    {
        return <Result>::from(<field>: $this-><field>);   // provider fields translated back to the domain
    }
}
```
