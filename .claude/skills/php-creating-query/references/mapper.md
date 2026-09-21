# Read-model mapper

Use when coercing a raw database row into the typed read model, the only place on the read side that reads a row by key.

```php
<?php

declare(strict_types=1);

// The row to read-model Data Mapper. The raw associative array from DBAL is the Mapper's private
// input and never travels further. It coerces every column ((int), (bool), (string)) and builds
// the typed read model. It is the only place in the read side that reads a raw row by key. The
// class is named <Resource>Mapper, never Record (the row is the input, not the role). It lives in
// Query/<Context>/Shared/Database/Mapper/.

// ---------------------------------------------------------------------------------------------
// src/Query/<Context>/Shared/Database/Mapper/<Resource>Mapper.php

final readonly class PaymentMapper
{
    private function __construct(private array $record, private bool $revealsBrcode)
    {
    }

    public static function from(array $record, bool $revealsBrcode): PaymentMapper
    {
        return new PaymentMapper(record: $record, revealsBrcode: $revealsBrcode);
    }

    public function toPayment(): Payment
    {
        $providerCode = ($this->record['provider_code'] ?? null);
        $paymentProvider = is_null($providerCode) ? null : Reference::from(
            id: $this->record['provider_id'],
            code: $providerCode,
            name: $this->record['provider_name']
        );

        return Payment::from(
            id: $this->record['id'],
            status: (string)$this->record['status'],
            country: $this->record['charge_country'],
            orderId: $this->record['order_id'],
            chargeId: ($this->record['charge_id'] ?? null),
            createdAt: Instant::fromString(value: $this->record['created_at'])->toIso8601(),
            maskedPayer: PayerMask::fromJsonOrNull(payerJson: $this->record['payer']),
            orderAmount: Money::from(
                value: (int)$this->record['order_amount_value'],
                currency: $this->record['order_amount_currency']
            ),
            chargeAmount: Money::from(
                value: (int)$this->record['charge_amount_value'],
                currency: $this->record['charge_amount_currency']
            ),
            orderContext: $this->record['order_context'],
            paymentMethod: PaymentMethodMask::masked(
                paymentMethod: json_decode((string)$this->record['payment_method_details'], true),
                revealsBrcode: $this->revealsBrcode
            ),
            organizationId: $this->record['organization_id'],
            paymentProvider: $paymentProvider,
            orderDescription: (string)$this->record['order_description'],
            statementDescriptor: ($this->record['statement_descriptor'] ?? null)
        );
    }
}
```
