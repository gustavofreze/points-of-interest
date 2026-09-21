# ExceptionMapping shape

Use this shape for the `<Stack>ExceptionMapping` at the `Driver/Http` root (and its `Query/Shared/Http` sibling). The
placement invariants (every domain and application exception has an arm, the shielded-guard carve-out, the 422 versus
409 versus 404 decision test, the error envelope) are owned by `php-hex-driver-http` (§ Exception to HTTP mapping). This
file carries the literal code shape, mirroring the live `src/Driver/Http/DriverExceptionMapping.php`.

- A `final readonly class` implementing `TinyBlocks\Http\ErrorHandler\ExceptionMapping`, with the single `mappings()`
  method returning an `ExceptionMappingTable`.
- `->when(exceptionClass: X::class)->mapsTo(code:, status:, message:)` when the code, status, and message are fixed.
- `->when(...)->resolvesWith(resolver: static function (X $error): MappedError {...})` when the message interpolates the
  value the exception carries, with the format string assigned to a `$template` variable first.
- `status:` always comes from the `TinyBlocks\Http\Code` enum, never a bare integer. The `code:` argument of `mapsTo()`
  or `resolvesWith()` carries the code value, which follows the `SCREAMING_SNAKE_CASE` derivation owned by
  `php-hex-driver-http` (§ Error response structure).

```php
final readonly class DriverExceptionMapping implements ExceptionMapping
{
    public function mappings(): ExceptionMappingTable
    {
        return ExceptionMappingTable::create()
            ->when(exceptionClass: PaymentNotFound::class)
            ->mapsTo(
                code: 'PAYMENT_NOT_FOUND',
                status: Code::NOT_FOUND->value,
                message: 'The payment was not found.'
            )
            ->when(exceptionClass: PaymentProviderNotFound::class)
            ->resolvesWith(resolver: static function (PaymentProviderNotFound $error): MappedError {
                $template = 'The payment provider <%s> was not found.';

                return new MappedError(
                    code: 'PAYMENT_PROVIDER_NOT_FOUND',
                    status: Code::UNPROCESSABLE_ENTITY->value,
                    message: sprintf($template, $error->value)
                );
            });
    }
}
```
