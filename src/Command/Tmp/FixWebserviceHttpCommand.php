<?php

namespace SyncEngine\Command\Tmp;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use SyncEngine\Controller\DefaultController;
use SyncEngine\Model\ConnectionModel;

/**
 * @return void
 */
#[AsCommand(
	name: 'syncengine:execute:webservice:update:config:http',
	description: 'Fix HTTP class names since 2024-10-02',
)]
class FixWebserviceHttpCommand extends Command
{
	private $map = [
		'Http'        => 'HttpMultistep',
		'NoAuth'      => 'Http',
		'Basic'       => 'HttpBasic',
		'BearerToken' => 'HttpBearerToken',
	];

	public function __construct( DefaultController $controller )
	{
		parent::__construct();
	}

	protected function execute( InputInterface $input, OutputInterface $output ): int
	{
		$connections = ConnectionModel::getAll();

		foreach ( $connections as $connection ) {
			$config = $connection->getConfig();

			foreach ( $this->map as $find => $replace ) {
				$config = $this->findReplace( $config, $find, $replace );
			}

			$connection->setConfig( $config );
			$connection->update( true );
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
