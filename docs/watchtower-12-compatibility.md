# Watchtower 12.3 compatibility review

Reviewed on November 2, 2026. Bundle baseline: `v3.0.1` / `main` at
`7b19a577fabaa60b6a7e5e6d3623cf1179a93618`. Core target: `v12.3.0` at
`c178a6482d8414d151f59565cb0db0c40e813017`.

## Decision

The bundle can support core 12.3 after adapting its authorization generator calls
and preserving PHP 8.0-compatible controller context merging.
A constraint-only change is insufficient: two Console methods used by the bundle
were removed in core 12.2. Application plugins also require migration; this is not
a drop-in upgrade for an existing core 10 application.

The proposed constraint is `^10.0 || ^11.0 || ^12.3`. It retains the complete existing
core 10 range and includes core 11, whose integration surface is compatible in the
executed checks. Core 12 starts at 12.3 to make the execution-context and batching
fixes the baseline; earlier 12.x releases are deliberately outside this proposal.
PHP, Symfony, and DoctrineBundle constraints are preserved. Core 10.1 onward adds
its own Doctrine ORM floor of `^2.20 || ^3.3`; the bundle must not impose this floor
on applications that remain on core 10.0.

Publish this widening in a **new major bundle release**, with the migration notes.
Publishing it in a 3.x update could let an ordinary Composer update select core 12
for an existing application whose plugins still target core 10. No tag, release,
commit, push, or remote configuration change is part of this patch.

## Integration surface comparison

| Surface used by the bundle | Source comparison with core 12.3 | Patch / validation |
| --- | --- | --- |
| `Executor()` factory | Same six positional arguments: entity manager, schema file, plugins, scalars, cache, optimize | No production change; compile the extension and instantiate the real factory |
| `Console()` factory | Same six positional arguments: entity manager, schema directory, schema basename, plugins, scalars, cache | No production change; execute real schema and cache commands |
| `Executor::executeQuery()` | Same six parameter names/types and `ExecutionResult` return type, including the names used by the controller | Real controller queries cover variables, operation selection, JSON responses, and error recovery |
| `Node::context()` | Still returns the application context array | Use PHP 8.0-compatible `array_merge` in the controller; assert configured Symfony service identity and current Request/Response identity across successive and nested calls |
| Authorization generators | `addAuthorizorPlugin()` and `addRootAuthorizorPlugin()` replaced by `addResultAuthorizorPlugin()` and `addRootResultAuthorizorPlugin()` in 12.2 | Detect the installed API with `method_exists`; retain the existing interactive choices and argument order |
| Other Console calls | `generateSchema`, `updateSchema`, `generateCache`, `plugins`, `scalarTypeDefinitions`, `addScalarTypeDefinition`, `addConstraintPlugin`, `addRootConstraintPlugin`, `addFilterPlugin`, `addOrderingPlugin`, `addSelectorPlugin`, `addResolverPlugin`, `addMutationPlugin`, and `addSubscriptionPlugin` remain present with compatible signatures | Source verified; representative schema/cache/plugin paths executed, not every interactive branch |
| Plugin/scalar listing | `PluginInfo::type/name`, `Plugins::filePath/getIterator`, and scalar definition listing APIs remain compatible | Source verified; generated authorization plugin enumeration exercised |

Core 12.3's Executor owns a fresh `ExecutionContext` for each call and clears it in
`finally`. The bundle must keep passing Symfony's service context as `contextValue`;
that array is separate from the core's internal execution state. No new Symfony
service or controller argument is required. The nested-controller regression checks
that an inner execution preserves the outer query's associations and request context.

The original controller unpacked configured string context keys with `...`, a
[PHP 8.1 feature](https://www.php.net/manual/en/migration81.new-features.php#migration81.new-features.core.unpacking-string-keys).
The integration fixture exposes that existing PHP 8.0 incompatibility.
The patch uses `array_merge` to preserve key precedence and the declared PHP 8.0 floor.
Actual execution on PHP 8.0 remains a CI requirement.

## Application-facing breaks

* **Core 11:** Watchtower error classes and its base throwable were renamed from
  `*Error` / `BaseError` to `*Exception` / `BaseException`. Update application imports
  and catches. The bundle does not reference the removed classes.
* **Core 12:** Query builder aliases changed from `rootEntityAlias` /
  `parentEntityAlias` to `rootAlias` / `parentAlias`; `reconciledAlias` became private.
  Use the public join/select/parameter helpers. `_cursor` is now reserved, and generated
  schemas include cursor support. These affect application mappings, schemas, and
  plugins; the bundle does not call these methods itself.
* **Core 12.2:** `AuthorizorPlugin` / `RootAuthorizorPlugin` became
  `ResultAuthorizorPlugin` / `RootResultAuthorizorPlugin`, with plugin type
  `result_authorizor`, directory `result_authorizors/`, and namespace
  `Wedrix\Watchtower\ResultAuthorizorPlugin`. Legacy `authorizors/` files are not used
  by the new resolver. Move files, change namespaces, and update direct factory calls
  before enabling the new core. Result authorization now also applies single-result
  authorizors to each row in collections. Review policy assumptions and test denials.
  New node authorization and projection APIs are not added to this patch's interactive
  menu; they are available through the core Console API.
* **Core 12.3:** `Resolver()` now needs an `ExecutionContext`; the old standalone
  `Resolver\Node()` function has been removed in favor of the new node construction
  flow. `NodeBuffer()` / `ResultBuffer()` now require a `Node`, which associates the
  access with its execution. Custom resolver factories, buffer callers, and test doubles
  may need changes. Ordinary bundle usage goes through the Executor and needs no adapter.
* **Caches:** regenerate after the upgrade and restart persistent workers. Core 12.3
  validates SDL during cache generation and writes cache artifacts atomically. Generated
  plugin/scalar paths and old in-process functions must not survive the migration.

The patch does not migrate application files or detect legacy authorization directories
at startup. Passing bundle tests cannot certify an application's authorization policy.

## Coverage before and after

The baseline has eight tests / 24 assertions: bundle extension identity, route metadata,
service-definition argument inspection, and four mocked plugin-command branches.
There are no controller tests, real factory instantiation, Doctrine-backed execution,
optimized execution, or authorization enforcement checks in that baseline. Existing CI
varies PHP/Symfony/Doctrine, but the `^10.0` constraint prevents it from testing core 11/12.

The patch adds single/collection authorization command expectations plus a real
Doctrine/SQLite integration fixture. It checks compiled services; cold optimized and
ordinary execution; variables and operation names; request/response and service context;
two parents with nested collections; changed database values and selections across calls;
validation and plugin failure recovery; nested execution on core 12.3; and real schema,
cache, and authorization generation. Generated root, collection, and single-result
authorization callbacks are invoked and tested for denials.

Integration tests run in separate processes because older supported cores retain
process-wide schema/cache state. Nested execution isolation is explicitly skipped on
core 10/11; this patch does not backport core 12.3's behavior.

CI retains the eight existing core 10 lanes with an explicit `^10.0` selection, adds
two core 11 lanes and four core 12 lanes, and installs `pdo_sqlite`. The core 12 floor
has an exact `12.3.0` lane; the other lanes track `^12.3`.

## Validation and limits

Local runtime: PHP 8.4.7 with SQLite. Packages were resolved and installed through
Composer from published packages, rather than substituted with local core source.
All seven final combinations resolved and passed. Symfony below means the selected
FrameworkBundle version; Composer also resolves its component dependencies.

| Core | Symfony FrameworkBundle | Doctrine ORM | Bundle test result |
| --- | --- | --- | --- |
| 10.0.0 | 5.4.54 | 2.20.14 | 14 passed, 97 assertions; nested execution test skipped |
| 10.2.0 | 6.4.49 | 3.7.6 | 14 passed, 97 assertions; nested execution test skipped |
| 11.0.0 | 5.4.54 | 2.20.14 | 14 passed, 97 assertions; nested execution test skipped |
| 11.1.0 | 6.4.49 | 3.7.6 | 14 passed, 97 assertions; nested execution test skipped |
| 12.3.0 | 5.4.54 | 2.20.14 | 15 passed, 108 assertions |
| 12.3.0 | 6.4.49 | 3.7.6 | 15 passed, 108 assertions |
| 12.3.0 | 7.4.22 | 3.7.6 | 15 passed, 108 assertions |

These bundle runs used graphql-php 15.39.0 and PHPUnit 9.6.38. The unpatched
`AddPluginCommand`, loaded through a separate test bootstrap against core 12.3.0,
fails all three authorization regression cases with undefined-method errors.
The patched command passes those cases in the matrix above.

`composer validate --strict`, PHP syntax checks, workflow YAML parsing, and patch
whitespace/application checks pass. The optional PHPStan 1.12.34 run is **not clean**:
it reports five Request/Response class/type lookup errors in the controller
with the latest graph, which includes HttpFoundation 8.1.10. The new command has no
reported errors. Static analysis for that dependency graph needs a separate tooling
investigation; runtime integration passing does not establish a clean static result.

Additional verification in the actual `watchtower-bundle` checkout passed with
core 12.3.0, FrameworkBundle 6.4.3, Doctrine ORM 3.6.2, graphql-php 15.30.2,
and PHPUnit 9.6.34: **15 tests / 108 assertions**. This also exercises the core's
minimum graphql-php version. Composer strict validation, all PHP syntax checks,
and parsing the 14-lane workflow pass in that checkout.

PHPStan was refreshed from 1.10.58 to 1.12.34 within the existing `^1.10`
development constraint because the older installation cannot run on PHP 8.4.
On this retained dependency graph it reports one existing configuration-builder
typing error (`NodeDefinition::children()` in `Configuration.php`); that source
file is unchanged by this compatibility patch. Static analysis is not claimed clean.

The retained local dependency graph has 14 Composer security advisories across
`symfony/cache`, `symfony/http-foundation`, `symfony/routing`, `symfony/yaml`, and
`webonyx/graphql-php`. Those package versions were unchanged by the targeted install.
They require a separate dependency refresh; this patch does not claim a clean audit.
The local ignored lock file and installed dependencies were refreshed only for core
12.3.0, the PHP 8.3 polyfill, and PHPStan. No lock file is included in the patch.

The independently run upstream core 12.3 suite passed: **107 tests / 1,655 assertions**,
using Doctrine ORM 3.6.7, graphql-php 15.34.1, and PHPUnit 9.6.35. This adds confidence
in the core behavior but does not replace bundle integration or application tests.

Reproduce a selected bundle lane from a clean checkout:

```bash
composer update --no-interaction --prefer-dist --with-all-dependencies \
  --with 'wedrix/watchtower:12.3.0' \
  --with 'symfony/framework-bundle:7.4.*' \
  --with 'symfony/yaml:7.4.*' \
  --with 'doctrine/orm:^3.3'
composer validate --strict
composer test
```

Full CI still needs to run on PHP 8.0, 8.1, 8.2, and 8.3 and with its lowest-dependency
sets. Local Homebrew paths named PHP 8.2/8.3 both resolve to PHP 8.4.7, so no claim of
executing those PHP versions is made. SQLite checks do not cover production databases,
application-specific plugins/mappings, full HTTP kernel routing, concurrency in persistent
workers, mutations/subscriptions through the bundle, or every newly added core feature.
Run application tests and allowed/denied request checks before deployment.

## Primary sources

* [Bundle baseline](https://github.com/Wedrix/watchtower-symfony-bundle/tree/7b19a577fabaa60b6a7e5e6d3623cf1179a93618), including its source, tests, Composer manifest, and CI workflow.
* [Core 12.3 release](https://github.com/Wedrix/watchtower/releases/tag/v12.3.0) and [source](https://github.com/Wedrix/watchtower/tree/c178a6482d8414d151f59565cb0db0c40e813017).
* [Core 10.2 to 12.3 comparison](https://github.com/Wedrix/watchtower/compare/v10.2.0...v12.3.0).
* [Core 11 migration notes](https://github.com/Wedrix/watchtower/releases/tag/v11.0.0).
* [Core 12 migration notes](https://github.com/Wedrix/watchtower/releases/tag/v12.0.0).
* [Authorization split](https://github.com/Wedrix/watchtower/compare/v12.1.0...v12.2.0).
