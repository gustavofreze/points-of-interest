# Layout canonical sources

The conventions in the `skeleton-layout` rule are anchored in the external standards below. When a question arises that
the rule does not answer, defer to the linked source. The first table holds whatever the stack is. The second one holds
only while the project keeps the binding named in its row.

## Stack-agnostic conventions

| Convention                                     | Source                                                                                            | Applies to                                                            |
|:-----------------------------------------------|:--------------------------------------------------------------------------------------------------|:----------------------------------------------------------------------|
| php-pds/skeleton                               | <https://github.com/php-pds/skeleton>                                                             | `src/`, `tests/`, `docs/`, `public/` at the repository root.          |
| The Twelve-Factor App, Factor III (Config)     | <https://12factor.net/config>                                                                     | Config from environment variables. No `config/<env>/` committed.      |
| Symfony `.env` strategy                        | <https://symfony.com/doc/current/configuration.html#configuration-based-on-environment-variables> | `.env` (gitignored, defaults), `.env.local` (gitignored, secrets).    |
| Docker official documentation                  | <https://docs.docker.com>                                                                         | `Dockerfile`, `docker-compose.yml` at the root. `.env` auto-load.     |
| Dockerfile-per-environment (suffix convention) | <https://github.com/dunglas/symfony-docker> and similar boilerplates                              | `Dockerfile.dev`, `Dockerfile.prod`.                                  |
| Container assets under `docker/<vendor>/`      | Symfony Docker, modern PHP boilerplates                                                           | `docker/php/`, one per software vendor the stack actually uses.       |
| Private keys never committed                   | OWASP, universal industry baseline                                                                | `.env.local` for secrets. `docker/<vendor>/certificates/` gitignored. |

## Stack bindings

Every row here is substitutable. The placement it fixes survives a swap, the product named does not. Replace the source
with the one the project's own binding documents, and drop the row outright when the project has no such binding (no
broker emulation without `<messaging-transport>`, no init directory without a database container of its own).

| Binding                            | Source for the binding in place                        | Applies to                                                                  |
|:-----------------------------------|:-------------------------------------------------------|:----------------------------------------------------------------------------|
| Official `<database-engine>` image | <https://hub.docker.com/_/mysql>                       | `docker-entrypoint-initdb.d/` maps to `docker/<database-engine>/initdb.d/`. |
| Migration runner (Flyway)          | <https://documentation.red-gate.com/fd>                | `database/migrations/`, `docker/flyway/migrate.sh`.                         |
| Broker emulation (LocalStack)      | <https://docs.localstack.cloud/references/init-hooks/> | `docker/localstack/init-ready.d/` (literal directory name).                 |
| Web sidecar (nginx)                | Symfony Docker, modern PHP boilerplates                | `docker/nginx/`, the web service in `docker-compose.yml`.                   |
