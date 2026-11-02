<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

if (file_exists(dirname(__DIR__).'/config/bootstrap.php')) {
    require dirname(__DIR__).'/config/bootstrap.php';
} elseif (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}

function runTestCommand($cmd) {
    $output = [];
    $exitCode = 0;
    exec($cmd, $output, $exitCode);
    if ($exitCode !== 0) {
        throw new RuntimeException("Test setup failed: $cmd\nError: " . implode("\n", $output));
    }
    return $output;
}

try {
    runTestCommand('php bin/console --env=test cache:clear --no-warmup');
	runTestCommand('php bin/console --env=test doctrine:schema:drop --force');
	runTestCommand('php bin/console --env=test doctrine:schema:create');
} catch (RuntimeException $e) {
    echo "Error: {$e->getMessage()}\n";
    //exit(1);
}

$fs = new \Symfony\Component\Filesystem\Filesystem();

// Clear previous test traces.
$fs->remove( dirname(__DIR__).'/var/trace/test/' );
