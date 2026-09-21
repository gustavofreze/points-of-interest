# Test Data Builder

Read this while constructing a test subject. None of it is a per-file invariant, so it is NOT path-injected. The
threshold invariant and the `build()`-placement invariant are defined here (the `>5`-parameter threshold and the
`build()`-chaining placement). This file holds the construction how-to, the builder skeleton, and the two worked usages.

Aggregate roots with large construction signatures cannot be built inline in every `@Given` without drowning the
scenario in noise. For those, a **Test Data Builder** under `tests/Support/Builders/` is the single sanctioned
construction helper. It is the construction analog of the single-SQL Fixtures boundary in `php-testing-integration`: one
builder per aggregate, consumed by every test of that aggregate.

## Builder versus inline construction

- **Inline via the production factory** when construction takes **5 parameters or fewer** (value objects, result types,
  small entities). For example `Charged::from(...)` with three parameters. No builder.
- **Builder** when the aggregate factory takes more than 5 parameters (for example `Payment::create(...)`). Inlining all
  of them in each test is prohibited noise.

A builder wrapping a factory of 5 parameters or fewer is surplus. Never write one.

## Command DTOs with large signatures

A `src/Application/Commands/<Command>` data carrier with more than 5 constructor parameters is built through a Test Data
Builder under `tests/Support/Builders/<Command>Builder.php`, on the same terms as the aggregate builder above
(`static new()` seeded with valid defaults, immutable `with<Field>()`, `build()` calling the real `new <Command>(...)`).
This is the only sanctioned way to construct a large command in a test. Inlining twenty-plus named arguments in every
`@Given`, or extracting a private factory method on the test class (prohibited by `php-code-style`, applied to test code
per `php-testing`), are both wrong. A command of 5 parameters or fewer is built inline. A command builder carries no
state shortcuts, because a command has no transitions.

## Builder shape

- Path `tests/Support/Builders/<Aggregate>Builder.php`, namespace `Test\Support\Builders`.
- `final` class, subject to **every** code-style rule (named arguments, member and parameter ordering by name length
  ascending, no abbreviations, no generic verbs, no private logic methods).
- `static new(): <Aggregate>Builder`. A builder seeded with valid defaults that build a coherent aggregate with no
  further configuration. The class names itself in the return type and in the `new` call, never `self` (`php-code-style`
  § Self-reference).
- `with<Field>(...)`. Chainable and **immutable**, each returning a new instance.
- `build(): <Aggregate>`. Calls the production named constructor.
- State shortcuts (`pending()`, `paid()`, ...). Static methods returning a pre-configured builder that reach
  post-creation states by applying the **real domain transitions** in `build()` (for example `paid()` runs `create` then
  `route` then `pay`), never through reflection. They carry **fixed internal defaults** for the transition inputs and
  expose **no** `with*` for those inputs.

The `<Aggregate>Builder` class skeleton is in § The PaymentBuilder skeleton below.

## Using the builder in a test

1. **Business name, never the helper's.** The variable is `$payment`, never `$builder`, even while it still holds the
   builder. The test describes the domain, not the scaffolding. The same holds for annotations. A `@Given`, `@And`,
   `@When`, or `@Then` never names the builder. Write `/** @Given a debit-card payment */`, never
   `/** @Given a payment builder for debit_card */`.
2. **`build()` chains onto the last link of the step where creation happens.** It is never a standalone
   `@When ... build()`, which narrates the helper rather than the domain. Creation lands on:
    - `@When` when **creation is the action under test** (invariant checks: normalization, validation, boundary
      acceptance or rejection).
    - `@Given` when creation is **setup** for a later domain action (the `@When` is then that action).
3. **Values that matter, one per `@And`.** When a value drives the assertion (a normalized field, an event count, an
   aggregate version, or a value a transition injects into the result), declare it in its own step calling the relevant
   `with*` or domain method with the real value. Values irrelevant to the test stay in the `new()` defaults. Do not
   spell them out.
4. **State shortcuts only when the path is irrelevant.** If the assertion checks only the direct effect of the `@When`
   (for example the resulting status) without depending on how the aggregate reached the prior state,
   `PaymentBuilder::paid()->build()` is fine. If the assertion depends on the intermediate transitions (event count,
   version, injected values), spell those transitions out as `@And` steps instead of hiding them in a shortcut.

## Cover actions and invariants, not accessors

A test exercises a **domain action**, either a factory that validates or a state transition. It never asserts that a
constructor stored what it was given. A test of the form "create with value X then the getter returns X" is surplus: it
exercises the accessor, not behavior. Remove it. Cover invariants by repeating the **same action with different values**
that drive each one: a valid CPF and a valid CNPJ and an invalid document that throws, an input that normalizes, a
boundary that is accepted or rejected.

## Grouping assertions

One logical concept per `@Then` block. When the verification spans distinct logical groups, separate them with `@And`
and a blank line. Never use a single block stacking every assertion.

## The PaymentBuilder skeleton

This skeleton is copied into a real test file, so resolve `<RootNamespace>` from the `autoload.psr-4` prefix in
`composer.json` before saving. No hook catches an unresolved token in a written PHP file: the use-statement check does
not match angle brackets, so the file passes the lint silently and then fails as a parse error.

```php
<?php

declare(strict_types=1);

namespace Test\Support\Builders;

use <RootNamespace>\Application\Domain\Models\Payment;

final readonly class PaymentBuilder
{
    private function __construct(
        private int $amount,
        private string $chargeId,
        private string $currency,
        private string $payerDocument,
        private string $organizationId
    ) {
    }

    public static function new(): PaymentBuilder
    {
        return new PaymentBuilder(
            amount: 15000,
            chargeId: '9d9d2a4f-ef5b-4cf9-b9c3-09699eb2cd0a',
            currency: 'BRL',
            payerDocument: '52998224725',
            organizationId: '8b8b1a3e-de4a-3be8-a8b2-08588da1bc09'
        );
    }

    public static function paid(): PaymentBuilder
    {
        // Static shortcut: build() applies the real transitions (create then route then pay),
        // never reflection. Fixed internal defaults for the transition inputs, no with* for them.
        return PaymentBuilder::new();
    }

    public function withPayerDocument(string $payerDocument): PaymentBuilder
    {
        return new PaymentBuilder(
            amount: $this->amount,
            chargeId: $this->chargeId,
            currency: $this->currency,
            payerDocument: $payerDocument,
            organizationId: $this->organizationId
        );
    }

    public function build(): Payment
    {
        return Payment::create(
            amount: $this->amount,
            chargeId: $this->chargeId,
            currency: $this->currency,
            payerDocument: $this->payerDocument,
            organizationId: $this->organizationId
        );
    }
}
```

## Usage 1, creation as the action under test

Creation as the action under test (an invariant), with `build()` on the `@When`.

```php
public function testCreatePaymentWithPunctuatedCpfThenStoresDigitsOnly(): void
{
    /** @When a payment is created carrying a punctuated CPF */
    $payment = PaymentBuilder::new()->withPayerDocument(payerDocument: '529.982.247-25')->build();

    /** @Then the payer document exposes only the digits */
    self::assertSame('52998224725', $payment->charge->payer->document->number());

    /** @And the document type is CPF */
    self::assertSame('cpf', $payment->charge->payer->document->type()->value);
}
```

## Usage 2, a domain action with creation as setup

A domain action with creation as setup, intermediate transitions explicit, and grouped assertions.

```php
public function testRefundWhenPaymentIsPaidThenRecordsPaymentRefunded(): void
{
    /** @Given a created payment */
    $payment = PaymentBuilder::new()->build();

    /** @And the payment is submitted to charge-201 on asaas */
    $payment->submit(charged: Charged::from(
        chargeId: ChargeId::from(value: 'charge-201'),
        provider: Provider::from(value: 'asaas'),
        pixBrcode: null
    ));

    /** @And the payment is paid */
    $payment->pay();

    /** @When the payment is refunded with a reason */
    $payment->refund(reason: 'Service not delivered');

    /** @Then three events are recorded */
    $records = $payment->peekEvents();
    self::assertSame(3, $records->count());

    /** @And the last event is a PaymentRefunded carrying the reason */
    $event = $records->last()->event;
    self::assertInstanceOf(PaymentRefunded::class, $event);
    self::assertSame('Service not delivered', $event->reason->toString());
}
```
