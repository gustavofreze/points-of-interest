# Data provider shape

Use this shape when a single behavior runs over a table of cases, with sentence-case dataset keys and parameter-named
columns.

```php
<?php

declare(strict_types=1);

/**
 * Data-provider shape. Two mandatory naming levels:
 *  - dataset key: a sentence-case description respecting proper nouns, never integer-indexed.
 *  - argument keys: the exact parameter names of the test method, never positional.
 * The @Given states the input shape in prose, with no expression below it (PHPUnit binds first).
 */

#[DataProvider('mappingProvider')]
public function testCreateWhenTheDomainFailsThenMapsToHttp(
    string $code,
    int $status,
    Throwable $failure,
    string $message
): void {
    /** @Given a create endpoint whose persistence fails with a domain error */
    # ...
}

public static function mappingProvider(): array
{
    return [
        'Unsupported payment method'  => [
            'code'    => 'UNSUPPORTED_PAYMENT_METHOD',
            'status'  => Code::UNPROCESSABLE_ENTITY->value,
            'failure' => new UnsupportedPaymentMethod(value: 'boleto'),
            'message' => 'The payment method <boleto> is not supported.'
        ],
        'Unsupported Brazilian state' => [
            'code'    => 'UNSUPPORTED_BRAZILIAN_STATE',
            'status'  => Code::UNPROCESSABLE_ENTITY->value,
            'failure' => new UnsupportedBrazilianState(value: 'ZZ'),
            'message' => 'The Brazilian state <ZZ> is not supported.'
        ]
    ];
}
```
