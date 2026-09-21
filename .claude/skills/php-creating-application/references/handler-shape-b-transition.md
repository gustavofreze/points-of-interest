# Handler shape B (single aggregate transition)

Use this when wiring a state-transition handler that loads the aggregate, throws not-found on a null result, and applies
the transition through its intent-named reason factory.

The `organizationId` argument is the tenant scope, this project's tenant discriminator carried into the port, and
passing it is the isolation invariant: a load that ignores the scope can return another tenant's aggregate. In a
single-tenant service, or where the aggregate's table carries no tenant discriminator, the command has no scope field,
the port takes the id alone, and the gate is skipped rather than failed. Everything else in the shape (load, not-found
guard, transition, persist, void) is unchanged either way.

```php
<?php

declare(strict_types=1);

// Shape B. Single aggregate, state transition. Load the aggregate via an outbound port, throw
// <Aggregate>NotFound when it is null (the one throwing guard Shape B allows), call the transition
// method, persist, return void. The transition receives a domain value object built through its
// intent-named for<Origin><Action> factory, sourced from the command primitive fields (promoting a
// primitive to its enum at the boundary when the factory takes one), never from a single catch-all
// field.
public function handle(<Operation><Aggregate> $command): void
{
    $aggregateId = <Aggregate>Id::from(value: $command-><aggregate>Id);
    $organizationId = OrganizationId::from(value: $command->organizationId);

    $aggregate = $this-><collection>->findById(id: $aggregateId, organizationId: $organizationId);

    if (is_null($aggregate)) {
        throw new <Aggregate>NotFound();
    }

    $code = is_null($command->code) ? null : <Code>::fromCode(code: $command->code);

    $reason = <Reason>::for<Origin><Action>(
        code: $code,
        additionalInformation: $command->additionalInformation
    );

    $aggregate-><operation>(reason: $reason);

    $this-><collection>->save(aggregate: $aggregate);
}
```
