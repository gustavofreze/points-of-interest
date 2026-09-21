<?php

declare(strict_types=1);

use DG\BypassFinals;
use TinyBlocks\DockerContainer\EnvironmentFlag;
use TinyBlocks\DockerContainer\FlywayDockerContainer;
use TinyBlocks\DockerContainer\MySQL\MySQLContainerStarted;
use TinyBlocks\DockerContainer\MySQLDockerContainer;

require_once __DIR__ . '/../vendor/autoload.php';

BypassFinals::enable();

$network = (string)(getenv('TEST_NETWORK') ?: 'points-of-interest-test_default');

MySQLDockerContainer::from(name: 'points-of-interest-adm-test', image: 'mysql:8.4')
    ->withNetwork(name: $network)
    ->withDatabase(database: 'points_of_interest_adm_test')
    ->withRootPassword(rootPassword: 'root')
    ->runWhen(
        gate: EnvironmentFlag::enabled(name: 'RUN_MIGRATIONS'),
        then: static function (MySQLContainerStarted $mySQLStarted) use ($network): void {
            $template = '%s/../database/migrations';
            $migrations = sprintf($template, __DIR__);

            FlywayDockerContainer::from(name: 'points-of-interest-flyway-test', image: 'flyway/flyway:13.7')
                ->withSource(password: 'root', username: 'root', container: $mySQLStarted)
                ->withNetwork(name: $network)
                ->withMigrations(pathOnHost: $migrations)
                ->withCleanDisabled(disabled: false)
                ->withConnectRetries(retries: 60)
                ->withValidateMigrationNaming(enabled: true)
                ->cleanAndMigrate();
        }
    );
