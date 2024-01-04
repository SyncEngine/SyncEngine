<?php

namespace SyncEngine\Service;

use SyncEngine\Controller\DefaultController;
use SyncEngine\Entity\User;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Process\Process;

class System
{
	protected Env $env;

	public function __construct(
		private readonly string $projectDir,
		Env $env
	) {
		$env->setEnvFile( 'local' );
		$this->env = $env;
	}

	public function getEnv(): Env
	{
		return $this->env;
	}

	public function isRegistered( EntityManagerInterface $entityManager = null ): bool
	{
		if ( ! $entityManager ) {
			$entityManager = DefaultController::getEntityManager();
		}

		$isInstalled = $this->isInstalled( $entityManager );
		if ( true !== $isInstalled ) {
			return $isInstalled;
		}

		// @todo filter by role.
		$existingUsers = $entityManager->getRepository( User::class )->findAll();
		if ( $existingUsers ) {
			return true;
		}
		return false;
	}

	public function isInstalled( EntityManagerInterface $entityManager = null, ?Env $env = null ): bool|\Throwable
	{
		if ( ! $entityManager ) {
			$entityManager = DefaultController::getEntityManager();
		}
		if ( ! $env ) {
			$env = $this->env;
		}

		// For now installation only requires a database.
		return $this->isDatabaseInstalled( $entityManager, $env );
	}

	public function isDatabaseInstalled( EntityManagerInterface $entityManager = null, ?Env $env = null ): bool|\Throwable
	{
		if ( ! $entityManager ) {
			$entityManager = DefaultController::getEntityManager();
		}
		if ( ! $env ) {
			$env = $this->env;
		}

		$hasDatabase = $this->isDatabaseConnected( $entityManager, $env );
		if ( $hasDatabase ) {
			try {
				$entityManager->getRepository( User::class )->findAll();
				return true;
			} catch ( \Throwable $e ) {
				return $e;
			}
		}

		return false;
	}

	public function isDatabaseConnected( EntityManagerInterface $entityManager = null, ?Env $env = null ): bool|\Throwable
	{
		if ( ! $entityManager ) {
			$entityManager = DefaultController::getEntityManager();
		}
		if ( ! $env ) {
			$env = $this->env;
		}

		$hasDatabase = $env->get( 'DATABASE_URL' );
		if ( $hasDatabase ) {
			try {
				$entityManager->getConnection()->connect();

				if ( $entityManager->getConnection()->isConnected() ) {
					return true;
				}
			} catch ( \Throwable $e ) {
				return $e;
			}
		}

		return false;
	}

	public function install( EntityManagerInterface $entityManager = null, ?Env $env = null ): bool|\Throwable
	{
		if ( true === $this->isInstalled( $entityManager, $env ) ) {
			return new \Exception( 'Already installed' );
		}

		$success = $this->installDatabase();

		if ( $success instanceof \Throwable ) {
			return $success;
		}

		return true;
	}

	public function installDatabase(): bool|\Throwable
	{
		try {
			$this->runDatabaseMigration();
		} catch ( \Throwable $e ) {
			return $e;
		}

		return true;
	}

	public function runDatabaseCreation(): void
	{
		$this->runProcess( [ 'php', 'bin/console', '--no-interaction', 'doctrine:migrations:drop', '--if-exists', '--force', ] );
		$this->runProcess( [ 'php', 'bin/console', '--no-interaction', 'doctrine:migrations:create' ] );
	}

	public function runDatabaseMigration(): void
	{
		$this->runProcess( [ 'php', 'bin/console', '--no-interaction', 'doctrine:migrations:diff' ] );
		$this->runProcess( [ 'php', 'bin/console', '--no-interaction', 'doctrine:migrations:migrate' ] );
	}

	public function runProcessSilent( array $command ): string
	{
		$process = new Process( $command );
		$process->setWorkingDirectory( $this->projectDir );
		$process->run();
		return $process->getOutput();
	}

	public function runProcess( array $command ): array
	{
		$process = new Process( $command );
		$process->setWorkingDirectory( $this->projectDir );
		$process->enableOutput();
		//$process->disableOutput(); // UnixPipes will open /dev/null event in basedir restriction.

		$process->start();

		$output = [];
		$errors = [];

		$process->wait( function ( $type, $buffer ) use ( &$output, &$errors ): void {
			if ( Process::ERR === $type ) {
				$errors[] = $buffer;
			} else {
				$output[] = $buffer;
			}
		} );

		if ( $errors ) {
			return $errors;
		}

		return $output;
	}
}
