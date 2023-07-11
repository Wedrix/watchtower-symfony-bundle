The WedrixWatchtowerBundle allows you to easily serve a GraphQL API for a Symfony application in as little as three easy steps. It is based on [Watchtower](https://github.com/Wedrix/watchtower), a wrapper around graphql-php, that provides an enhanced fallback resolver capable of auto-resolving GraphQL queries using the Doctrine schema.

# Requirements

This bundle is only compatible with Symfony versions >=6.1.

# Installation

Install the bundle in three easy steps:

1. Enable recipes defined in the contrib repository:

        composer config extra.symfony.allow-contrib true

2. Install the bundle:

        composer require wedrix/watchtower-bundle

3. Generate the GrahQL schema:

        php bin/console watchtower:schema:generate

**That's it!** Your GraphQL API is now available at `whatever-your-domain-is/graphql.json` by default.  

**Note:** When accessing the API over a web browser you may need to enable CORS if it is not already enabled for your application. To do so, kindly view the [NelmioCorsBundle documentation](https://symfony.com/bundles/NelmioCorsBundle/current/index.html) for the installation and setup guide. You may also view [the demo application's source code](https://github.com/Wedrix/watchtower-symfony-demo-application/blob/main/config/packages/nelmio_cors.yaml) for a quick-and-dirty example configuration after installing the bundle.

# Features

For a complete list of features kindly view the [Watchtower documentation](https://github.com/Wedrix/watchtower#features).

# Config Options

The various configuration options are available in the `config/packages/wedrix_watchtower_bundle.yaml` file:

* `endpoint` - configures the enpoint for accessing the GraphQL API (/graphql.json by default).
* `schema_file` - configures the file used as the source of the GraphQL schema (auto-generated using the `php bin/console watchtower:schema:generate` command). You may point it to a pre-existing schema file if your project already has one.
* `plugins_directory` - configures the directory containing your various plugins: filters, selectors, resolvers, etc. Kindly view the [Watchtower documentation](https://github.com/Wedrix/watchtower#plugins) for more info on plugins.
* `scalar_type_definitions_directory` - configures the directory containing your various scalar type definition files. Kindly view the [Watchtower documentation](https://github.com/Wedrix/watchtower#scalar-type-definitions) for more info on scalar type definitions.
* `schema_cache_directory` - the directory for storing caches of the schema.
* `cache_schema` - whether to cache the schema. This improves the performance but may be annoying in development since it necessitates clearing the cache whenever you make changes to the schema.
* `debug` - whether to send debug information to the client. Most of the time, this should only be enabled in development environments but the configuration is exposed for those who want to take their chances debugging production environemnts.
* `context` - this allows you access any service in the container in plugins using the Node::context() method. The service key can be any name of your choosing but the value should be the service id. For example: `entity_manager: 'doctrine.orm.entity_manager'`.
