<?php

namespace SyncEngine\Command\Tmp;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use SyncEngine\Controller\DefaultController;
use SyncEngine\Model\AutomationModel;
use SyncEngine\Model\ConnectionModel;
use SyncEngine\Model\FlowModel;
use SyncEngine\Model\RoutineModel;
use SyncEngine\Model\StorageModel;

/**
 * @return void
 */
#[AsCommand(
	name: 'syncengine:execute:conditions:update:config:operator:contains',
	description: 'Fix in/contains operator distinction since 2025-12-09, replaces all instances of "in" with "contains" to fix the compare direction',
)]
class FixConditionsOperatorContains extends Command
{
	private $map = [
		'in'            => 'contains',
		'not_in'        => 'not_contains',
		'in_strict'     => 'contains_strict',
		'not_in_strict' => 'not_contains_strict',
	];

	public function __construct( DefaultController $controller )
	{
		parent::__construct();
	}

	protected function execute( InputInterface $input, OutputInterface $output ): int
	{
		$types = [
			ConnectionModel::class,
			AutomationModel::class,
			FlowModel::class,
			RoutineModel::class,
			StorageModel::class,
		];

		foreach ( $types as $model ) {
			$entities = ConnectionModel::getAll();
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
		foreach ( $config as $k => $v ) {
			if ( 'operator' === $k && $v === $find ) {
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
