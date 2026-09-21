# Handler shape C (compensating orchestration)

Use this when a write use case needs multi-aggregate coordination or a compensating fallback, the one shape where
branching and a fallback catch are allowed. The catch translates a port outcome into the aggregate's own transition, so
the aggregate is still triggered at most once per execution path, and the external interaction happens only because a
prior use case committed the transition that authorizes it (`php-hex-application-handlers` § Facts drive the flow).

The `organizationId` argument is the tenant scope, this project's tenant discriminator carried into the port, and
passing it is the isolation invariant: a load that ignores the scope can return another tenant's aggregate. In a
single-tenant service, or where the aggregate's table carries no tenant discriminator, the command has no scope field,
the port takes the id alone, and the gate is skipped rather than failed. The compensating catch is the point of this
shape and it is unaffected either way.

```php
<?php

declare(strict_types=1);

// Shape C. Compensating orchestration. The named exception to the thin default: a try/catch
// translating a port outcome into the aggregate's own transition, allowed in this shape only. The
// thin-handler, one-aggregate-trigger, and domain-service invariants are owned by the
// php-hex-application-handlers rule, read it.
public function handle(AcceptCharge $command): void
{
    $paymentId = PaymentId::from(value: $command->paymentId);
    $organizationId = OrganizationId::from(value: $command->organizationId);

    $payment = $this->payments->findById(id: $paymentId, organizationId: $organizationId);

    if (is_null($payment)) {
        throw new PaymentNotFound();
    }

    try {
        $payment->accept(charged: $this->chargeSubmitting->submit(payment: $payment));
    } catch (NoApplicableRoutingRule) {
        $payment->fail(reason: Reason::forNoRoutingRule());
    } catch (ProviderChainExhausted) {
        $payment->fail(reason: Reason::forProviderChainExhaustion());
    }

    $this->payments->save(payment: $payment);
}
```
