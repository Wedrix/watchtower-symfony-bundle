WedrixWatchtowerBundle integrates [Watchtower](https://github.com/Wedrix/watchtower) with Symfony so you can expose a GraphQL API from Doctrine entities with minimal setup.

# Requirements

* PHP `8.0+`
* Symfony `5.4`, `6.4`, or `7.x`
* `wedrix/watchtower` `^10.0 || ^11.0 || ^12.3`

Watchtower `10.1+`, `11.x`, and `12.3+` require Doctrine ORM `^2.20 || ^3.3`.
Composer resolves the PHP and Doctrine requirements of your selected core version.
The bundle keeps its existing PHP, Symfony, and DoctrineBundle constraints.

# Upgrading Watchtower

Core upgrades across major versions require an application/plugin review. Keep an
explicit `wedrix/watchtower:^10.0` requirement in your application to stay on core 10.
After adapting your plugins, opt into core 12.3 with:

```bash
composer require 'wedrix/watchtower:^12.3' --with-all-dependencies
```

Before deploying the upgrade:

* Core 11 renamed Watchtower `*Error` classes to `*Exception` and `BaseError` to
  `BaseException`. Update application imports and catch clauses.
* Core 12 replaced `QueryBuilder::rootEntityAlias()` / `parentEntityAlias()` with
  `rootAlias()` / `parentAlias()`. Replace public `reconciledAlias()` calls with
  `joinAlias()`, `leftJoinAlias()`, `selectAlias()`, or `parameterName()` as appropriate.
  Doctrine fields and associations named `_cursor` are reserved; review generated
  GraphQL schemas for the new cursor fields/scalar.
* Core 12.2 renamed result authorization plugins. Move existing `authorizors/` files
  to `result_authorizors/`, change their namespace from
  `Wedrix\Watchtower\AuthorizorPlugin` to `Wedrix\Watchtower\ResultAuthorizorPlugin`,
  and replace direct `AuthorizorPlugin()` / `RootAuthorizorPlugin()` calls with
  `ResultAuthorizorPlugin()` / `RootResultAuthorizorPlugin()`. Legacy directories are
  not discovered by core 12.3: verify both allowed and denied requests after migration.
  Single-result authorizors now also apply to individual rows in collection results.
* Core 12.3 requires a resolver `Node` when calling `NodeBuffer($node)` or
  `ResultBuffer($node)`. Custom low-level `Resolver()` construction now requires an
  execution context. Normal bundle controller calls create and clear that context
  automatically; Symfony's configured `context` service map remains unchanged.
* Regenerate Watchtower caches with `php bin/console watchtower:cache:generate`
  after changing core versions, schemas, plugins, or scalar definitions. Restart
  persistent workers so they load the new plugin functions and caches.

The bundle's `authorizor` and `root_authorizor` generator choices use the legacy
Console methods on core 10/11 and the result authorization methods on core 12.3+.
This preserves command inputs; it does not migrate existing plugin files. The new
node authorization and projection generators remain available through the core
Console API and are not added to the bundle's interactive choices in this patch.

See [the compatibility review](docs/watchtower-12-compatibility.md) for the API
comparison, executed checks, and validation still required before release.

# Installation

1. Enable Symfony contrib recipes (once per project):

        composer config extra.symfony.allow-contrib true

2. Install the bundle:

        composer require wedrix/watchtower-bundle

3. Generate your initial schema:

        php bin/console watchtower:schema:generate

Your GraphQL endpoint is served through the configured `endpoint` path (commonly `/graphql.json`).

When calling the endpoint from browsers, make sure CORS is configured if needed (for example with [NelmioCorsBundle](https://symfony.com/bundles/NelmioCorsBundle/current/index.html)).

# Quick Start

Run a query:

```bash
curl -X POST 'http://localhost:8000/graphql.json' \
  -H 'Content-Type: application/json' \
  -d '{"query":"{ __typename }"}'
```

The route accepts `POST` requests only.

# Configuration

Configure the bundle in `config/packages/wedrix_watchtower_bundle.yaml`:

```yaml
wedrix_watchtower_bundle:
    endpoint: '/graphql.json'
    schema_file: '%kernel.project_dir%/resources/graphql/schema.graphql'
    plugins_directory: '%kernel.project_dir%/resources/graphql/plugins'
    scalar_type_definitions_directory: '%kernel.project_dir%/resources/graphql/scalar_type_definitions'
    cache_directory: '%kernel.cache_dir%/watchtower'
    optimize: false
    debug: '%kernel.debug%'
    context:
        entity_manager: 'doctrine.orm.entity_manager'
```

Options:

* `endpoint`: GraphQL endpoint path.
* `schema_file`: GraphQL schema file path.
* `plugins_directory`: directory containing Watchtower plugins.
* `scalar_type_definitions_directory`: directory containing scalar type definition files.
* `cache_directory`: directory used for Watchtower cache artifacts.
* `optimize`: enables cache-first execution mode; run `watchtower:cache:generate` after schema/plugin/scalar changes.
* `debug`: includes GraphQL debug details in responses.
* `context`: map of context keys to Symfony service IDs; resolved services are exposed to plugins via `Node::context()`.

At runtime, plugins also receive these built-in context entries:

* `request`: current `Symfony\Component\HttpFoundation\Request`
* `response`: current `Symfony\Component\HttpFoundation\Response`

# Console Commands

* `watchtower:schema:generate`: generate a new schema file from Doctrine metadata.
* `watchtower:schema:update`: currently invalidates schema cache; it does not rewrite the schema file yet.
* `watchtower:cache:generate`: generate cache files used by optimize mode.
* `watchtower:plugins:add`: generate plugin boilerplate interactively.
* `watchtower:plugins:list`: list configured plugins.
* `watchtower:scalar-type-definitions:add`: generate scalar type definition boilerplate.
* `watchtower:scalar-type-definitions:list`: list configured scalar type definitions.

`watchtower:plugins:add` supports:

* `constraint`
* `root_constraint`
* `filter`
* `ordering`
* `selector`
* `resolver`
* `authorizor`
* `root_authorizor`
* `mutation`
* `subscription`

For plugin conventions and feature details, see the upstream Watchtower docs:

* Plugins: https://github.com/Wedrix/watchtower#plugins
* Scalar types: https://github.com/Wedrix/watchtower#scalar-type-definitions
* Full feature docs: https://github.com/Wedrix/watchtower#features

# Development

Run tests:

```bash
composer test
```

Tests require `pdo_sqlite`. Integration tests compile the bundle services and use
real Watchtower factories, Doctrine XML mappings, and an in-memory SQLite database.
They cover controller context, variables and operation names, nested associations,
successive requests, error recovery, cold optimized execution, and schema/cache/plugin
commands, including authorization enforcement. Nested controller execution isolation
is tested on core 12.3+ and explicitly skipped on older core versions.

CI selects core 10 explicitly in the existing lanes and adds core 11 and core 12.3
lanes across supported PHP, Symfony, and Doctrine ORM combinations. One lane pins
12.3.0 exactly; other core 12 lanes track `^12.3`.
