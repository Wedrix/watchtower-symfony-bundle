<?php

declare(strict_types=1);

namespace Wedrix\WatchtowerBundle\Tests\Integration;

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\Mapping\Driver\SymfonyFileLocator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Wedrix\Watchtower\Console;
use Wedrix\Watchtower\Executor;
use Wedrix\Watchtower\Resolver\Node;
use Wedrix\WatchtowerBundle\Command\AddPluginCommand;
use Wedrix\WatchtowerBundle\Command\GenerateCacheCommand;
use Wedrix\WatchtowerBundle\Command\GenerateSchemaCommand;
use Wedrix\WatchtowerBundle\Controller\WatchtowerController;
use Wedrix\WatchtowerBundle\DependencyInjection\WedrixWatchtowerExtension;
use Wedrix\WatchtowerBundle\Tests\Fixtures\Entity\Author;
use Wedrix\WatchtowerBundle\Tests\Fixtures\Entity\Book;

/**
 * Older supported core versions retain process-wide schema/cache state.
 * Each fixture needs a fresh process, including a cold optimized schema load.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class WatchtowerIntegrationTest extends TestCase
{
    private string $directory;
    private EntityManagerInterface $entityManager;
    private \stdClass $probe;

    protected function setUp(): void
    {
        $this->directory = \sys_get_temp_dir().'/watchtower_bundle_'.\bin2hex(\random_bytes(8));
        foreach (['', '/scalars', '/cache', '/plugins'] as $suffix) {
            \mkdir($this->directory.$suffix);
        }

        $configuration = new Configuration();
        $configuration->setMetadataDriverImpl(new XmlDriver(new SymfonyFileLocator([
            __DIR__.'/../Fixtures/mappings' => 'Wedrix\\WatchtowerBundle\\Tests\\Fixtures\\Entity',
        ], '.dcm.xml')));
        $configuration->setProxyDir($this->directory.'/proxies');
        $configuration->setProxyNamespace('WatchtowerBundleTests\\Proxy');
        $configuration->setAutoGenerateProxyClasses(true);
        if (\PHP_VERSION_ID >= 80400 && \method_exists($configuration, 'enableNativeLazyObjects')) {
            $configuration->enableNativeLazyObjects(true);
        }
        $connection = ['driver' => 'pdo_sqlite', 'memory' => true];
        $this->entityManager = \method_exists(EntityManager::class, 'create')
            ? EntityManager::create($connection, $configuration)
            : new EntityManager(DriverManager::getConnection($connection), $configuration);
        (new SchemaTool($this->entityManager))->createSchema(
            $this->entityManager->getMetadataFactory()->getAllMetadata()
        );

        foreach (['Ada' => 'First', 'Grace' => 'Second'] as $name => $title) {
            $author = new Author($name);
            $this->entityManager->persist($author);
            $this->entityManager->persist(new Book($title, $author));
        }
        $this->entityManager->flush();

        \file_put_contents($this->directory.'/schema.graphql', <<<'GRAPHQL'
type Query { authors: [Author!]!, inspect(value: String!): String }
type Author { id: ID!, name: String!, books: [Book!]! }
type Book { id: ID!, title: String!, author: Author! }
GRAPHQL
        );
        $this->probe = new \stdClass();
        $this->probe->callback = static fn (Node $node): string => $node->args()['value'];
    }

    protected function tearDown(): void
    {
        $this->entityManager->close();
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $file) {
            $file->isDir() ? \rmdir($file->getPathname()) : \unlink($file->getPathname());
        }
        \rmdir($this->directory);
    }

    /** @dataProvider executionModes */
    public function testCompiledServicesExecuteRequestsWithContextAndFreshBatches(bool $optimize): void
    {
        $container = $this->container(false);
        if ($optimize) {
            $tester = new CommandTester($container->get(GenerateCacheCommand::class));
            self::assertSame(Command::SUCCESS, $tester->execute([]));
            $container = $this->container(true);
        }
        self::assertInstanceOf(Executor::class, $container->get(Executor::class));
        self::assertInstanceOf(Console::class, $container->get(Console::class));
        $controller = $container->get(WatchtowerController::class);

        $seen = [];
        $this->probe->callback = function (Node $node) use (&$seen): string {
            $context = $node->context();
            self::assertSame($this->probe, $context['probe']);
            $seen[] = $context;
            $context['response']->headers->set('X-Watchtower-Context', $node->args()['value']);
            return $node->args()['value'];
        };

        $request = $this->request('query Selected($value: String!) {
            inspect(value: $value) authors { id name books { id title } }
        } query Unused { __typename }', ['value' => 'first'], 'Selected');
        $response = $controller($request);
        $result = \json_decode($response->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        self::assertArrayNotHasKey('errors', $result);
        self::assertSame('first', $result['data']['inspect']);
        self::assertSame('first', $response->headers->get('X-Watchtower-Context'));
        self::assertSame('application/json; charset=utf-8', $response->headers->get('Content-Type'));
        self::assertSame($request, $seen[0]['request']);
        self::assertSame($response, $seen[0]['response']);
        $authors = \array_column($result['data']['authors'], null, 'name');
        self::assertSame('First', $authors['Ada']['books'][0]['title']);
        self::assertSame('Second', $authors['Grace']['books'][0]['title']);

        $this->entityManager->createQuery('UPDATE '.Book::class.' b SET b.title = :new WHERE b.title = :old')
            ->setParameter('new', 'Updated')->setParameter('old', 'First')->execute();
        $nextRequest = $this->request('{ inspect(value: "second") authors { name books { title } } }');
        $nextResponse = $controller($nextRequest);
        $next = \json_decode($nextResponse->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        self::assertArrayNotHasKey('errors', $next);
        $authors = \array_column($next['data']['authors'], null, 'name');
        self::assertSame('Updated', $authors['Ada']['books'][0]['title']);
        self::assertSame('Second', $authors['Grace']['books'][0]['title']);
        self::assertSame('second', $next['data']['inspect']);
        self::assertSame($nextRequest, $seen[1]['request']);
        self::assertSame($nextResponse, $seen[1]['response']);
        self::assertNotSame($response, $nextResponse);
    }

    public static function executionModes(): array
    {
        return [[false], [true]];
    }

    public function testControllerRecoversAfterGraphqlValidationAndPluginErrors(): void
    {
        $controller = $this->container(false)->get(WatchtowerController::class);
        $invalid = $controller($this->request('{ missingField }'));
        self::assertArrayHasKey('errors', \json_decode($invalid->getContent(), true));

        $this->probe->callback = static function (): string {
            throw new \RuntimeException('Private plugin failure');
        };
        $failed = \json_decode($controller($this->request('{ inspect(value: "bad") }'))->getContent(), true);
        self::assertSame('Internal server error', $failed['errors'][0]['message']);
        self::assertArrayNotHasKey('extensions', $failed['errors'][0]);

        $this->probe->callback = static fn (Node $node): string => $node->args()['value'];
        $recovered = \json_decode($controller($this->request('{ inspect(value: "ok") authors { name books { title } } }'))->getContent(), true);
        self::assertArrayNotHasKey('errors', $recovered);
        self::assertSame('ok', $recovered['data']['inspect']);
        self::assertCount(2, $recovered['data']['authors']);
    }

    public function testNestedControllerExecutionKeepsOuterBatchesAndRequestContext(): void
    {
        if (!\function_exists('Wedrix\\Watchtower\\Resolver\\ExecutionContext')) {
            self::markTestSkipped('Nested execution isolation is provided by Watchtower 12.3+.');
        }
        $controller = $this->container(false)->get(WatchtowerController::class);
        $seen = [];
        $innerRequest = $this->request('{ inspect(value: "inner") authors { id books { id title } } }');
        $this->probe->callback = function (Node $node) use ($controller, $innerRequest, &$seen): string {
            $value = $node->args()['value'];
            $seen[$value] = $node->context();
            $seen[$value]['response']->headers->set('X-Watchtower-Context', $value);
            if ($value === 'outer') {
                $inner = \json_decode($controller($innerRequest)->getContent(), true);
                self::assertArrayNotHasKey('errors', $inner);
                self::assertSame('inner', $inner['data']['inspect']);
                self::assertCount(2, $inner['data']['authors']);
            }
            return $value;
        };
        $outerRequest = $this->request('{ authors { name books { title } } inspect(value: "outer") }');
        $outerResponse = $controller($outerRequest);
        $outer = \json_decode($outerResponse->getContent(), true);
        self::assertArrayNotHasKey('errors', $outer);
        self::assertSame('outer', $outer['data']['inspect']);
        $authors = \array_column($outer['data']['authors'], null, 'name');
        self::assertSame('First', $authors['Ada']['books'][0]['title']);
        self::assertSame('Second', $authors['Grace']['books'][0]['title']);
        self::assertSame($outerRequest, $seen['outer']['request']);
        self::assertSame($innerRequest, $seen['inner']['request']);
        self::assertSame('outer', $outerResponse->headers->get('X-Watchtower-Context'));
        self::assertNotSame($seen['inner']['response'], $outerResponse);
    }

    public function testConsoleCommandsGenerateSchemaCacheAndAuthorizationPlugins(): void
    {
        \unlink($this->directory.'/schema.graphql');
        // Generate into a writable plugin directory, separate from the static resolver fixture.
        $container = $this->container(false, $this->directory.'/plugins');
        foreach ([GenerateSchemaCommand::class, GenerateCacheCommand::class] as $command) {
            self::assertSame(Command::SUCCESS, (new CommandTester($container->get($command)))->execute([]));
        }
        self::assertFileExists($this->directory.'/cache/schema.graphql');
        $application = new Application();
        $application->add($container->get(AddPluginCommand::class));
        $tester = new CommandTester($application->find('watchtower:plugins:add'));
        foreach ([['authorizor', 'Author', 'yes'], ['authorizor', 'Author', 'no'], ['root_authorizor']] as $inputs) {
            $tester->setInputs($inputs);
            self::assertSame(Command::SUCCESS, $tester->execute([]));
        }
        $type = \method_exists(Console::class, 'addResultAuthorizorPlugin') ? 'result_authorizor' : 'authorizor';
        $console = $container->get(Console::class);
        $plugins = \iterator_to_array($console->plugins());
        self::assertCount(3, $plugins);
        foreach ($plugins as $plugin) {
            self::assertSame($type, $plugin->type());
            $source = \file_get_contents($console->plugins()->filePath($plugin));
            self::assertStringContainsString(
                'namespace Wedrix\\Watchtower\\'.($type === 'result_authorizor' ? 'ResultAuthorizorPlugin' : 'AuthorizorPlugin').';',
                $source
            );
            // Exercise the generated callback through the controller, not just its file name.
            \file_put_contents($console->plugins()->filePath($plugin), \str_replace(
                "{\n}",
                "{\n    (\$node->context()['probe']->authorize)(\$result, \$node, __FUNCTION__);\n}",
                $source
            ));
        }
        $called = [];
        $this->probe->authorize = static function ($result, Node $node, string $callback) use (&$called): void {
            $called[] = \substr($callback, \strrpos($callback, '\\') + 1);
        };
        $controller = $container->get(WatchtowerController::class);
        $query = '{ authors { id name } author(id: "1") { id name } }';
        $allowed = \json_decode($controller($this->request($query))->getContent(), true);
        self::assertArrayNotHasKey('errors', $allowed);
        self::assertCount(2, $allowed['data']['authors']);
        foreach (['authorize_result', 'authorize_authors_result', 'authorize_author_result'] as $name) {
            self::assertContains($name, $called);
            $this->probe->authorize = static function ($result, Node $node, string $callback) use ($name): void {
                if (\substr($callback, \strrpos($callback, '\\') + 1) === $name) {
                    throw new \RuntimeException('Denied by '.$name);
                }
            };
            $denied = \json_decode($controller($this->request($query))->getContent(), true);
            self::assertArrayHasKey('errors', $denied);
            self::assertNull($denied['data']['authors'] ?? null);
        }
    }

    private function container(bool $optimize, ?string $pluginsDirectory = null): ContainerBuilder
    {
        $container = new ContainerBuilder();
        // FrameworkBundle normally provides this alias for AbstractController::setContainer().
        $container->setAlias(\Psr\Container\ContainerInterface::class, 'service_container');
        $container->register('doctrine.orm.entity_manager', EntityManagerInterface::class)->setSynthetic(true)->setPublic(true);
        $container->register('probe', \stdClass::class)->setSynthetic(true)->setPublic(true);
        (new WedrixWatchtowerExtension())->load([[
            'endpoint' => '/graphql.json',
            'schema_file' => $this->directory.'/schema.graphql',
            'plugins_directory' => $pluginsDirectory ?? __DIR__.'/../Fixtures/plugins',
            'scalar_type_definitions_directory' => $this->directory.'/scalars',
            'cache_directory' => $this->directory.'/cache',
            'optimize' => $optimize,
            'debug' => false,
            'context' => ['probe' => 'probe'],
        ]], $container);
        foreach ([Executor::class, Console::class, WatchtowerController::class, GenerateSchemaCommand::class,
            GenerateCacheCommand::class, AddPluginCommand::class] as $service) {
            $container->getDefinition($service)->setPublic(true);
        }
        $container->compile();
        $container->set('doctrine.orm.entity_manager', $this->entityManager);
        $container->set('probe', $this->probe);
        return $container;
    }

    private function request(string $query, ?array $variables = null, ?string $operationName = null): Request
    {
        return Request::create('/graphql.json', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'],
            \json_encode(\compact('query', 'variables', 'operationName'), \JSON_THROW_ON_ERROR));
    }
}
