WedrixWatchtowerBundle integrates [Watchtower](https://github.com/Wedrix/watchtower) with Symfony so you can expose a GraphQL API from Doctrine entities with minimal setup.

# Requirements

* PHP `8.0+`
* Symfony `5.4+`
* `wedrix/watchtower` `^10.0`

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

CI runs a dependency matrix across supported PHP, Symfony, and Doctrine ORM combinations (including lowest and latest lanes for key baselines).
