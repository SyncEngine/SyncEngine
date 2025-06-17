<?php

namespace SyncEngine\Command\Tmp;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use SyncEngine\Command\Trait\UpdateConfigCommandTrait;
use SyncEngine\Controller\DefaultController;
use SyncEngine\Model\AutomationModel;
use SyncEngine\Model\ConnectionModel;
use SyncEngine\Model\FlowModel;
use SyncEngine\Model\RoutineModel;
use SyncEngine\Model\StorageModel;

/**
 * @return void
 */
#[AsCommand( name: 'syncengine:execute:rename:routine', description: 'Fix full rename of Steps to Routines since 2025-06-11', )]
class FixRenameStepToRoutine extends Command
{
	use UpdateConfigCommandTrait;

	public function __construct( private DefaultController $controller )
	{
		parent::__construct();
	}

	protected function execute( InputInterface $input, OutputInterface $output ): int
	{
		$modelTypes = [
			ConnectionModel::class,
			AutomationModel::class,
			FlowModel::class,
			RoutineModel::class,
			StorageModel::class,
		];

		$this->renameTable( 'step', 'routine', $output );

		foreach ( $modelTypes as $modelName ) {

			$models = $modelName::getAll();

			foreach ( $models as $model ) {
				$config = $model->getConfig();

				$config = $this->findReplaceRecursive(
					$config,
					'step',
					'routine',
					function ( $key ) {
						return $key === 'action';
					}
				);
				$config = $this->findReplaceKeyRecursive( $config, 'step', 'routine' );

				$model->setConfig( $config );
				$model->update( true );
			}
		}

		$output?->writeln( "Renamed 'step' to 'routine' in entity configs." );

		return Command::SUCCESS;
	}

	protected function renameTable( $from, $to, OutputInterface $output = null )
	{
		$conn     = $this->controller->getEntityManager()->getConnection();
		$platform = $conn->getDatabasePlatform();

		$sql = match ( true ) {
			$platform instanceof \Doctrine\DBAL\Platforms\MySQLPlatform => "RENAME TABLE $from TO $to",
			$platform instanceof \Doctrine\DBAL\Platforms\PostgreSQLPlatform => "ALTER TABLE $from RENAME TO $to",
			$platform instanceof \Doctrine\DBAL\Platforms\SqlitePlatform => "ALTER TABLE $from RENAME TO $to",
			default => throw new \RuntimeException( "Unsupported DB platform" ),
		};

		$conn->executeStatement( $sql );

		$output?->writeln( "Renamed table '$from' to '$to'." );
	}
}
