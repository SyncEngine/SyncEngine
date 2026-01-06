<?php

namespace SyncEngine\Command\Tmp;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use SyncEngine\Controller\DefaultController;
use SyncEngine\Model\AutomationModel;
use SyncEngine\Model\CodecModel;
use SyncEngine\Model\ConnectionModel;
use SyncEngine\Model\FlowModel;
use SyncEngine\Model\RoutineModel;
use SyncEngine\Model\StorageModel;

/**
 * @return void
 */
#[AsCommand(
	name: 'syncengine:execute:format:model:config',
	description: 'Fix Format Model config location since 2026-01-06',
)]
class FixFormatModelConfigCommand extends Command
{
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

				$config = $this->refactor( $config );

				$entity->setConfig( $config );
				$entity->update( true );
			}
		}

		return Command::SUCCESS;
	}

	private function refactor( $config )
	{
		if ( ! is_array( $config ) ) {
			return $config;
		}

		if ( isset( $config['format'] ) && is_string( $config['format'] ) ) {
			$format = $config['format'];

			$codec = CodecModel::get( ucfirst( $format ) );
			if ( $codec ) {
				$formatConfig = [
					'_class' => $codec->getClassLocator(),
				];

				$fields = $codec->getFields();
				$prefix = $format . '_';

				// Find format-specific (e.g. csv_*, json_*) config keys and move them into the format config array.
				foreach ( $config as $key => $value ) {
					if ( str_starts_with( $key, $prefix ) ) {
						$formatConfig[ $key ] = $value;

						// Try to clean redundant config.
						$context = substr( $key, strlen( $prefix ) );
						if ( isset( $fields[ $context ] ) ) {
							unset( $config[ $key ] );
						}
					}
				}
				$config[ 'format' ] = $formatConfig;
			}
		}

		foreach ( $config as $k => $v ) {
			if ( 'format' === $k ) {
				continue;
			}
			if ( is_array( $v ) ) {
				$config[ $k ] = $this->refactor( $v );
			}
		}

		return $config;
	}
}
