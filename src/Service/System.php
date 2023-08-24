<?php

namespace App\Service;

use App\Controller\DefaultController;
use App\Entity\User;
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
		$env->setType( 'local' );
		$this->env = $env;
	}

	public function getEnv(): Env
	{
		return $this->env;
	}

	public function isRegistered( EntityManagerInterface $entityManager = null ): bool
	{
		if ( true !== $this->isInstalled() ) {
			return false;
		}

		if ( ! $entityManager ) {
			$entityManager = DefaultController::getEntityManager();
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

		if ( true !== $this->isDatabaseInstalled( $entityManager, $env ) ) {
			try {
				$existingUsers = $entityManager->getRepository( User::class )->findAll();
				return true; // No errors.
			} catch ( \Throwable $e ) {
				return $e; // Errors.
			}
		}

		return false;
	}

	public function isDatabaseInstalled( EntityManagerInterface $entityManager = null, ?Env $env = null ): bool
	{
		if ( ! $entityManager ) {
			$entityManager = DefaultController::getEntityManager();
		}
		if ( ! $env ) {
			$env = $this->env;
		}

		$hasDatabase = $env->get( 'DATABASE_URL' );
		if ( $hasDatabase ) {
			$entityManager->getConnection()->connect();

			if ( $entityManager->getConnection()->isConnected() ) {
				return true;
			}
		}

		return false;
	}

	public function install( EntityManagerInterface $entityManager = null, ?Env $env = null ): bool|\Throwable
	{
		if ( $this->isInstalled( $entityManager, $env ) ) {
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
			$process = new Process( [ 'php', 'bin/console', '--no-interaction', 'doctrine:migrations:diff' ] );
			$process->setWorkingDirectory( $this->projectDir );
			$process->disableOutput();
			$result = $process->run();

			$process = new Process( [ 'php', 'bin/console', '--no-interaction', 'doctrine:migrations:migrate' ] );
			$process->setWorkingDirectory( $this->projectDir );
			$process->disableOutput();
			$result = $process->run();

		} catch ( \Throwable $e ) {
			return $e;
		}

		return true;
	}
}
