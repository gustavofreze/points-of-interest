# Strategic design

Strategic DDD is about the boundaries between models, not the classes inside one. Tactical patterns make one model good.
Strategic patterns decide where that model applies, where another begins, and how the two communicate without corrupting
each other. This file is the deep reference behind the table in `SKILL.md`. The ENFORCED specifics for this repository
are routed in `SKILL.md` § In this service.

## Ubiquitous Language

The ubiquitous language is the shared vocabulary of the team and the domain experts, used in conversation, in the model,
in the code, and in the tests, with no translation layer between them. When the experts say "place an order", the method
is `place`, not `submit` or `save`. When a term is ambiguous, the team settles it once and uses the settled term
everywhere.

The language is bounded. The same word can mean different things in different contexts, and that is expected. The fix is
not a single global glossary, it is a clear boundary (see Bounded Context) inside which one meaning holds.

A drifting language is a warning. When the code calls something by a name the experts never use, the model is probably
wrong. Rename toward the business, or change the model until the name fits.

## Bounded Context

A bounded context is the boundary inside which a particular model and its language apply consistently. A "customer" in a
sales context and a "customer" in a support context are different models with different rules, and the boundary is what
lets each stay coherent. Inside the boundary every term has one meaning. Across the boundary, terms are translated.

A bounded context is not automatically a module or a service. The boundary is conceptual first. It often aligns with a
deployable unit, and in this repository it does (one bounded context per service), but the alignment is a decision, not
a definition. Draw the context by the model and the language, then choose the deployment.

The signs you have crossed a context boundary: the same noun means two things, one team's change keeps breaking another
team's assumptions, or a single model is accreting conditionals to serve two audiences. Each is a cue to split the
context.

## Context Map

A context map names the relationships between bounded contexts and makes the integration risks explicit. It is drawn
between teams as much as between models, because the relationship is organizational. The canonical relationship
patterns:

| Relationship          | What it means                                              | When it fits                                            |
|-----------------------|------------------------------------------------------------|---------------------------------------------------------|
| Partnership           | Two teams succeed or fail together, coordinated planning   | Mutual dependency, aligned goals                        |
| Shared Kernel         | A small model shared by explicit joint ownership           | A genuine overlap both teams must agree on              |
| Customer-Supplier     | Downstream needs are a planned input to the upstream       | Upstream can and will accommodate downstream            |
| Conformist            | Downstream adopts the upstream model as-is, no translation | Upstream will not adapt and its model is good enough    |
| Anti-Corruption Layer | Downstream translates the upstream model into its own      | Upstream model is foreign or messy and must be kept out |
| Open Host Service     | Upstream publishes a defined protocol for many consumers   | Many downstreams integrate with one context             |
| Published Language    | A well-documented shared interchange format                | Integration needs a common, stable contract             |
| Separate Ways         | No integration at all, duplicate instead                   | Integration costs more than duplication                 |

The map is a living artifact. Revisit it when teams reorganize or a new integration appears.

## Anti-Corruption Layer

An anti-corruption layer (ACL) is a translation boundary that converts a foreign model into this context's own model, so
the foreign concepts never leak inward. It protects a clean model from an upstream model that is messy, legacy, or
simply shaped by a different language.

It is a real translation, not a rename. The ACL maps foreign types, vocabulary, and error shapes onto this context's
value objects, aggregates, and domain exceptions. In a ports-and-adapters service the ACL lives in the adapter at the
edge: the adapter receives the foreign payload and hands the domain only its own types. The domain stays unaware that an
external system exists.

The cost is a layer to build and maintain. The payoff is that an upstream change is absorbed in one place, the adapter,
instead of rippling through the model.

## Shared Kernel

A shared kernel is a small part of the model that two contexts share by explicit, joint agreement. Both teams own it,
and neither changes it unilaterally, because a change affects both. The classic content is a set of common value objects
or identifiers (Money, a currency code, an identity type).

Keep it small and stable. The shared kernel trades autonomy for consistency, so every element in it is a coupling point
between two teams. It is justified only when the overlap is real and the cost of duplicating it (and keeping the
duplicates aligned) is higher than the cost of shared ownership. When in doubt, prefer separate models with a
translation at the boundary over a growing shared kernel.
