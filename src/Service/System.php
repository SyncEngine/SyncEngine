<?php

namespace SyncEngine\Service;

use Symfony\Component\Process\PhpExecutableFinder;
use SyncEngine\Controller\DefaultController;
use SyncEngine\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Process\Process;

class System
{
	protected Env $env;
	protected string $php;

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

	public function isRegistered( EntityManagerInterface $entityManager = null ): bool|\Throwable
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

		if ( !($success instanceof \Throwable )) {
			$success = $this->generateVault();
		}

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

	public function generateVault(): bool|\Throwable
	{
		try {
			$this->runVaultGeneration();
		} catch ( \Throwable $e ) {
			return $e;
		}

		return true;
	}

	public function runDatabaseCreation(): void
	{
		$this->runCommand( [ '--no-interaction', 'doctrine:migrations:drop', '--if-exists', '--force', ] );
		$this->runCommand( [ '--no-interaction', 'doctrine:migrations:create' ] );
	}

	public function runDatabaseMigration(): void
	{
		$this->runCommand( [ '--no-interaction', 'doctrine:migrations:diff' ] );
		$this->runCommand( [ '--no-interaction', 'doctrine:migrations:migrate' ] );
	}

	public function runVaultGeneration(): void
	{
		$this->runCommand( [ '--no-interaction', 'secrets:generate-keys' ] );
	}

	public function runCommand( array $command, $silent = true ): ?string
	{
		return $this->runProcess( $this->getCommandProcess( $command, $silent ), $silent );
	}

	public function runProcess( Process|array $process, $silent = true ): ?string
	{
		if ( ! $process instanceof Process ) {
			$process = $this->getProcess( $process, $silent );
		}

		$process->run();

		if ( $silent ) {
			return null;
		}
		return $process->getOutput();
	}

	public function runProcessDebug( Process $process ): array
	{
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

	public function getCommandProcess( array $command, bool $silent = false ): Process
	{
		array_unshift( $command, $this->getPhpExecutable(), 'bin/console' );

		return $this->getProcess( $command, $silent );
	}

	public function getProcess( array $command, bool $silent = false ): Process
	{
		$process = new Process( $command );

		if ( $silent ) {
			$process = $this->getProcessRaw( $process, $silent );
		}

		$process->setWorkingDirectory( $this->projectDir );

		return $process;
	}

	public function getProcessRaw( Process|string $command, $silent = false ): Process
	{
		if ( $command instanceof Process ) {
			$command = $command->getCommandLine();
		}

		if ( $silent ) {
			if ( $this->isWindows() ) {
				$command = 'start /b ' . $command;
			} else {
				$command .= '&';
			}
		}

		$process = Process::fromShellCommandline( $command );
		$process->setWorkingDirectory( $this->projectDir );

		return $process;
	}

	public function getPhpExecutable(): string
	{
		if ( ! empty( $this->php ) ) {
			return $this->php;
		}

		$this->php = ( new PhpExecutableFinder() )->find();

		return $this->php;
	}
	/**
	 * Check operating system
	 * Check operating system.
	 *
	 * @return boolean true if it's Windows OS
	 * @return boolean `true` if it's Windows OS.
	 */
	protected function isWindows(): bool
	{
		return str_starts_with( strtoupper( PHP_OS ), 'WIN' );
	}
}
