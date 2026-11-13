<?php

declare(strict_types=1);

use DG\BypassFinals;
use TinyBlocks\DockerContainer\FlywayDockerContainer;
use TinyBlocks\DockerContainer\MySQLDockerContainer;

require_once __DIR__ . '/../vendor/autoload.php';

BypassFinals::enable();

$network = (string)(getenv('TEST_NETWORK') ?: 'points-of-interest-test_default');
$template = 'points-of-interest-adm-test-%s';
$databaseHost = sprintf($template, gethostname());

putenv(sprintf('DATABASE_HOST=%s', $databaseHost));

FlywayDockerContainer::from(image: 'flyway/flyway:13.12')
    ->withSource(
        container: MySQLDockerContainer::from(image: 'mysql:8.4')
            ->withName(name: $databaseHost)
            ->withNetwork(name: $network)
            ->withDatabase(database: 'points_of_interest_adm_test')
            ->withRootPassword(rootPassword: 'root')
            ->withEphemeralStorage()
    )
    ->withNetwork(name: $network)
    ->withMigrations(pathOnHost: sprintf('%s/../database/migrations', __DIR__))
    ->withConnectRetries(retries: 60)
    ->withMigrationNamingValidation()
    ->onlyWhenEnabled(variable: 'RUN_MIGRATIONS')
    ->cleanAndMigrate();
