# Read model

Use when shaping the strongly-typed read model and its generic sub-DTOs, with `toArray()` as the single owner of the
JSON wire envelope.

## Contents

- `Payment`: the context read model owning the wire envelope.
- `Money`: the generic value and currency sub-DTO.
- `Reference`: the generic id, code, and name triple.

```php
<?php

declare(strict_types=1);

// The read model and its generic sub-DTOs. The read model is strongly typed: scalars plus typed
// sub-DTOs for fixed-shape nested data, never a loose array for a known shape. A bare array is
// allowed ONLY for genuinely dynamic or polymorphic output (the masked payer, the payment-method
// variant keyed by type). toArray() is the single owner of the JSON wire envelope and delegates
// to each sub-DTO's toArray(). toArray() whitelists the wire shape: it names every field the
// response emits, so a column added to the query or a property added to the read model stays
// invisible to the client until toArray() lists it. This is a whitelist by construction, never a
// blacklist that leaks a newly added sensitive field the moment someone forgets to exclude it.
// The context read model lives in Query/<Context>/Shared/ReadModel/.
// The generic Money and Reference DTOs live in Query/Shared/ReadModel/.

// ---------------------------------------------------------------------------------------------
// src/Query/<Context>/Shared/ReadModel/<Resource>.php

final readonly class Payment
{
    private function __construct(
        public string $id,
        public string $status,
        public string $country,
        public string $orderId,
        public ?string $chargeId,
        public string $createdAt,
        public ?array $maskedPayer,
        public Money $orderAmount,
        public Money $chargeAmount,
        public string $orderContext,
        public array $paymentMethod,
        public string $organizationId,
        public ?Reference $paymentProvider,
        public string $orderDescription,
        public ?string $statementDescriptor
    ) {
    }

    public static function from(
        string $id,
        string $status,
        string $country,
        string $orderId,
        ?string $chargeId,
        string $createdAt,
        ?array $maskedPayer,
        Money $orderAmount,
        Money $chargeAmount,
        string $orderContext,
        array $paymentMethod,
        string $organizationId,
        ?Reference $paymentProvider,
        string $orderDescription,
        ?string $statementDescriptor
    ): Payment {
        return new Payment(
            id: $id,
            status: $status,
            country: $country,
            orderId: $orderId,
            chargeId: $chargeId,
            createdAt: $createdAt,
            maskedPayer: $maskedPayer,
            orderAmount: $orderAmount,
            chargeAmount: $chargeAmount,
            orderContext: $orderContext,
            paymentMethod: $paymentMethod,
            organizationId: $organizationId,
            paymentProvider: $paymentProvider,
            orderDescription: $orderDescription,
            statementDescriptor: $statementDescriptor
        );
    }

    public function toArray(): array
    {
        return [
            'id'           => $this->id,
            'order'        => [
                'id'          => $this->orderId,
                'amount'      => $this->orderAmount->toArray(),
                'context'     => $this->orderContext,
                'description' => $this->orderDescription
            ],
            'charge'       => [
                'id'                   => $this->chargeId,
                'payer'                => $this->maskedPayer,
                'amount'               => $this->chargeAmount->toArray(),
                'country'              => $this->country,
                'payment_method'       => $this->paymentMethod,
                'payment_provider'     => $this->paymentProvider?->toArray(),
                'statement_descriptor' => $this->statementDescriptor
            ],
            'status'       => $this->status,
            'created_at'   => $this->createdAt,
            'organization' => [
                'id' => $this->organizationId
            ]
        ];
    }
}

// ---------------------------------------------------------------------------------------------
// src/Query/Shared/ReadModel/Money.php  (generic {value, currency} read-model DTO)

final readonly class Money
{
    private function __construct(public int $value, public string $currency)
    {
    }

    public static function from(int $value, string $currency): Money
    {
        return new Money(value: $value, currency: $currency);
    }

    public function toArray(): array
    {
        return [
            'value'    => $this->value,
            'currency' => $this->currency
        ];
    }
}

// ---------------------------------------------------------------------------------------------
// src/Query/Shared/ReadModel/Reference.php  (generic {id, code, name} read-model triple)

final readonly class Reference
{
    private function __construct(public string $id, public string $code, public string $name)
    {
    }

    public static function from(string $id, string $code, string $name): Reference
    {
        return new Reference(id: $id, code: $code, name: $name);
    }

    public function toArray(): array
    {
        return [
            'id'   => $this->id,
            'code' => $this->code,
            'name' => $this->name
        ];
    }
}
```
