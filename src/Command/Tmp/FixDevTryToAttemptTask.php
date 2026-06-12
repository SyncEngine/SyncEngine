<?php

namespace SyncEngine\Command\Tmp;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use SyncEngine\Controller\DefaultController;
use SyncEngine\Model\AutomationModel;
use SyncEngine\Model\RoutineModel;
use SyncEngine\Model\TaskModel;

/**
 * @return void
 */
#[AsCommand(
	name: 'syncengine:execute:dev:update:config-core-attempt-task',
	description: 'Convert TryCatch module class names to core Attempt task since 2025-11-28',
)]
class FixDevTryToAttemptTask extends Command
{
	private $map = [
		'SyncEngine/TryCatch:TryCatch' => 'Attempt',
	];

	public function __construct( DefaultController $controller )
	{
		parent::__construct();
	}

	protected function execute( InputInterface $input, OutputInterface $output ): int
	{
		$task = TaskModel::get( 'Attempt' );

		if ( ! $task ) {
			$output->writeln( '<error>Attempt task not found</error>' );
			return Command::FAILURE;
		}

		$types = [
			AutomationModel::class,
			RoutineModel::class,
		];

		foreach ( $types as $model ) {
			$entities = $model::getAll();
			foreach ( $entities as $entity ) {
				$config = $entity->getConfig();

				foreach ( $this->map as $find => $replace ) {
					$config = $this->findReplace( $config, $find, $replace );
				}

				$entity->setConfig( $config );
				$entity->update( true );
			}
		}

		return Command::SUCCESS;
	}

	private function findReplace( $config, $find, $replace )
	{
		if ( ! is_array( $config ) ) {
			if ( $config === $find ) {
				return $replace;
			}
			return $config;
		}

		foreach ( $config as $k => $v ) {
			if ( '_class' === $k && $v === $find ) {
				$config[ $k ] = $replace;
				continue;
			}

			if ( is_array( $v ) ) {
				$config[ $k ] = $this->findReplace( $v, $find, $replace );
			}
		}

		return $config;
	}
}
