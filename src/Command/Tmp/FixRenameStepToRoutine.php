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
#[AsCommand( name: 'syncengine:execute:dev:update:rename-routine', description: 'Fix full rename of Steps to Routines since 2025-06-11', )]
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

		if ( ! $this->renameTable( 'step', 'routine', $output ) ) {
			return Command::FAILURE;
		}

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

	protected function renameTable( $from, $to, ?OutputInterface $output = null ): bool
	{
		$conn          = $this->controller->getEntityManager()->getConnection();
		$schemaManager = $conn->createSchemaManager();
		$platform      = $conn->getDatabasePlatform();

		$toExists = $schemaManager->tablesExist( [ $to ] );

		if ( $toExists ) {
			$count = (int) $conn->fetchOne( "SELECT COUNT(*) FROM $to" );

			if ( $count > 0 ) {
				$output?->writeln( "<error>Table '$to' already exists and is not empty ($count rows). Skipping rename — manual intervention required.</error>" );
				return false;
			}

			// Target table exists but is empty: migrate data from source then truncate source.
			$output?->writeln( "<comment>Table '$to' already exists but is empty. Migrating data from '$from'...</comment>" );

			$columns = implode( ', ', array_map(
				fn( $col ) => $col->getName(),
				$schemaManager->listTableColumns( $from )
			) );

			$conn->executeStatement( "INSERT INTO $to ($columns) SELECT $columns FROM $from" );

			$migrated = (int) $conn->fetchOne( "SELECT COUNT(*) FROM $to" );

			$schemaManager->dropTable( $from );
			$output?->writeln( "Migrated $migrated rows from '$from' into '$to' and dropped '$from'." );

			return true;
		}

		$sql = match ( true ) {
			$platform instanceof \Doctrine\DBAL\Platforms\MySQLPlatform       => "RENAME TABLE $from TO $to",
			$platform instanceof \Doctrine\DBAL\Platforms\PostgreSQLPlatform  => "ALTER TABLE $from RENAME TO $to",
			$platform instanceof \Doctrine\DBAL\Platforms\SqlitePlatform      => "ALTER TABLE $from RENAME TO $to",
			default => throw new \RuntimeException( "Unsupported DB platform" ),
		};

		$conn->executeStatement( $sql );

		$output?->writeln( "Renamed table '$from' to '$to'." );

		return true;
	}
}
