<?php

namespace SyncEngine\Command\Tmp;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use SyncEngine\Controller\DefaultController;
use SyncEngine\Model\AutomationModel;
use SyncEngine\Model\ConnectionModel;
use SyncEngine\Model\RoutineModel;
use SyncEngine\Webservice\Soap;

/**
 * @return void
 */
#[AsCommand(
	name: 'syncengine:execute:dev:update:config-webservice-soap-request',
	description: 'Fix SOAP request config since 2026-07-28',
)]
class FixWebserviceSoapRequest extends Command
{
	// @phpstan-ignore-next-line property.onlyWritten (Required to trigger DI container initialization)
	public function __construct( DefaultController $controller )
	{
		parent::__construct();
	}

	protected function execute( InputInterface $input, OutputInterface $output ): int
	{
		$types = [
			AutomationModel::class,
			RoutineModel::class,
		];

		foreach ( $types as $model ) {
			$entities = $model::getAll();

			foreach ( $entities as $entity ) {
				$config = $entity->getConfig();

				$config = $this->recursiveUpdate( $config );

				$entity->setConfig( $config );
				$entity->update( true );
			}
		}

		return Command::SUCCESS;
	}

	private function recursiveUpdate( $config )
	{
		if ( ! is_array( $config ) ) {
			return $config;
		}

		foreach ( $config as $k => $v ) {
			if ( 'Soap' === ( $config['_class'] ?? '' ) ) {
				return $this->updateConfig( $config );
			}

			if ( is_array( $v ) ) {
				$config[ $k ] = $this->recursiveUpdate( $v );
			}
		}

		return $config;
	}

	private function updateConfig( array $config ): array
	{
		if ( ! isset( $config['request'] ) ) {
			$config['request'] = [];
		}

		if ( isset( $config['call_data'] ) ) {
			$config['request']['body'] = $config['call_data'];
			unset( $config['call_data'] );
		}

		if ( isset( $config['headers'] ) ) {
			$config['request']['headers'] = $config['headers'];
			unset( $config['headers'] );
		}

		if ( isset( $config['soap_initiate'] ) ) {
			$config['request']['soap_operation'] = $config['soap_initiate'];
			unset( $config['soap_operation'] );
		}

		return $config;
	}
}
