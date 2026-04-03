<?php

namespace SyncEngine\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;
use SyncEngine\Controller\DefaultController;
use SyncEngine\Entity\User;
use SyncEngine\Kernel;

if ( ! defined( 'STDIN' ) ) {
	define( 'STDIN', fopen( "php://stdin", "r" ) );
}

class System
{
	protected Env $env;
	protected string $php;

	public function __construct(
		private readonly string $projectDir,
		private Kernel $kernel,
		Env $env
	) {
		$env->setEnvFile( 'local' );
		$this->env = $env;
	}

	public function getEnv(): Env
	{
		return $this->env;
	}

	public function isRegistered( ?EntityManagerInterface $entityManager = null ): bool|\Throwable
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

	public function isInstalled( ?EntityManagerInterface $entityManager = null, ?Env $env = null ): bool
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

	public function isDatabaseInstalled( ?EntityManagerInterface $entityManager = null, ?Env $env = null ): bool
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
				return false;
			}
		}

		return false;
	}

	public function isDatabaseConnected( ?EntityManagerInterface $entityManager = null, ?Env $env = null ): bool|\Throwable
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

	public function install( ?EntityManagerInterface $entityManager = null, ?Env $env = null ): bool|\Throwable
	{
		if ( true === $this->isInstalled( $entityManager, $env ) ) {
			return new \Exception( 'Already installed' );
		}

		$success = false;

		if ( $this->isDatabaseConnected() && ! $this->isDatabaseInstalled() ) {
			$success = $this->createDatabase();
			if ( true === $success ) {
				$success = $this->installDatabase();
			}
		}

		if ( $success instanceof \Throwable ) {
			return $success;
		}

		return $success;
	}

	public function createDatabase(): bool|array|\Throwable
	{
		try {
			return $this->runDatabaseCreation();
		} catch ( \Throwable $e ) {
			return $e;
		}
	}

	public function repairDatabase(): bool|array|\Throwable
	{
		try {
			return $this->runDatabaseRepair();
		} catch ( \Throwable $e ) {
			return $e;
		}
	}

	public function installDatabase(): bool|array|\Throwable
	{
		try {
			return $this->runDatabaseMigration();
		} catch ( \Throwable $e ) {
			return $e;
		}
	}

	public function runDatabaseCreation(): bool|array
	{
		$this->runCommand( 'doctrine:schema:drop', options: [ '--force' ] ); // '--if-exists' option does not exist,
		return $this->runCommand( 'doctrine:schema:create' );
	}

	public function runDatabaseRepair(): bool|array
	{
		return $this->runCommand( 'doctrine:schema:update', options: [ '--force', '--complete' ] );
	}

	public function runDatabaseMigration(): bool|array
	{
		$this->runCommand( 'doctrine:migrations:diff' );
		return $this->runCommand( 'doctrine:migrations:migrate' );
	}

	/**
	 * @throws ExceptionInterface
	 *
	 * @param  array   $arguments  Ordered arguments
	 *                             https://symfony.com/doc/current/console/input.html#using-command-arguments
	 * @param  array   $options    Option flags
	 *                             https://symfony.com/doc/current/console/input.html#using-command-options
	 * @param  bool    $silent     Return output or only boolean.
	 * @param  string  $command
	 */
	public function runCommand( string $command, array $arguments = [], array $options = [], bool $silent = true ): bool|array
	{
		$parsedOptions = [];
		foreach ( $options as $name => $value ) {
			if ( is_numeric( $name ) ) {
				$name = $value;
				$value = true;
			}
			$parsedOptions[ '--' . ltrim( $name, '-' ) ] = $value;
		}

		$command    = $this->getCommand( $command );
		$definition = $command->getDefinition();
		$input      = new ArrayInput( [ ...$arguments, ...$parsedOptions ], $definition );
		$output     = new BufferedOutput();

		$input->setInteractive( false );

		$success = $command->run( $input, $output );

		if ( $silent ) {
			return Command::SUCCESS === $success;
		}

		return [
			'success' => Command::SUCCESS === $success,
			'status'  => Process::$exitCodes[ $success ] ?? $success,
			'output'  => $output->fetch(),
		];
	}

	public function runCommandProcess( array $command, bool $silent = true ): bool|array
	{
		return $this->runProcess( $this->getCommandProcess( $command, $silent ), $silent );
	}

	public function runProcess( Process|array $process, $silent = true ): bool|array
	{
		if ( ! $process instanceof Process ) {
			$process = $this->getProcess( $process, $silent );
		}

		$success = $process->run();

		if ( $silent ) {
			return 0 === $success;
		}

		return [
			'success' => 0 === $success,
			'status'  => Process::$exitCodes[ $success ] ?? '',
			'output'  => $process->getOutput(),
		];
	}

	public function runProcessDebug( Process $process ): array
	{
		//$process->disableOutput(); // UnixPipes will open /dev/null event in basedir restriction.

		$process->start();

		$output = [];
		$errors = [];

		$success = $process->wait( function ( $type, $buffer ) use ( &$output, &$errors ): void {
			if ( Process::ERR === $type ) {
				$errors[] = $buffer;
			} else {
				$output[] = $buffer;
			}
		} );

		$return = [
			'success' => 0 === $success,
			'status'  => Process::$exitCodes[ $success ] ?? '',
			'output'  => $process->getOutput(),
		];

		if ( $errors ) {
			$return['errors'] = $errors;
		}

		return $return;
	}

	public function getCommand( string $command ): Command
	{
		return ( new Application( $this->kernel ) )->get( $command );
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
				$command .= ' &';
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

		if ( ! $this->php ) {
			throw new \ErrorException( 'PHP executable could not be found or is not within allowed paths' );
		}

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
