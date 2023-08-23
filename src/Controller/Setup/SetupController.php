<?php

namespace App\Controller\Setup;

use App\Entity\User;
use App\Service\Env;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Process\Process;

class SetupController extends AbstractController
{
	protected Env $env;

	public function __construct( Env $env )
	{
		$env->setType( 'local' );
		$this->env = $env;
	}

	public function isDatabaseInstalled( EntityManagerInterface $entityManager, ?Env $env = null ): bool
	{
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

	public function isInstalled( EntityManagerInterface $entityManager, ?Env $env = null ): bool
	{
		if ( ! $env ) {
			$env = $this->env;
		}

		if ( $this->isDatabaseInstalled( $entityManager, $env ) ) {
			try {
				$existingUsers = $entityManager->getRepository( User::class )->findAll();
				if ( $existingUsers ) {
					return true;
				}
			} catch ( TableNotFoundException $e ) {
			}
		}

		return false;
	}

	public function installDatabase()
	{
		$process = new Process( [ 'php bin/console', 'doctrine:migrations:diff', '-y' ] );
		$process->run();

		$process = new Process( [ 'php bin/console', 'doctrine:migrations:migrate', '-y' ] );
		$process->run();
	}
}
