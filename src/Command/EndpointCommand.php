<?php

namespace SyncEngine\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\Request;
use SyncEngine\Controller\DefaultController;
use SyncEngine\Model\AutomationModel;
use SyncEngine\Service\DataFormatter;

/**
 * @return void
 */
#[AsCommand(
	name: 'syncengine:endpoint',
	description: 'List available automation endpoints',
)]
class EndpointCommand extends Command
{

	public function __construct( DefaultController $controller )
	{
		parent::__construct();
	}

	protected function configure(): void
	{
		$this->addArgument( 'endpoint', InputArgument::OPTIONAL, 'The automation endpoint.' );
		$this->addOption( 'request', null, InputOption::VALUE_OPTIONAL, 'The request parameters (URL formatted).', null );
		$this->addOption( 'query', null, InputOption::VALUE_OPTIONAL, 'The query parameters (URL formatted).', null );
	}

	protected function execute( InputInterface $input, OutputInterface $output ): int
	{
		$automations = AutomationModel::getAll( [] );

		$output->writeln( '<comment>Available endpoints:</comment>' );
		foreach ( $automations as $automation ) {
			// @todo Access validation when implemented.
			$output->writeln( '  <info>' . $automation->getEndpoint() . '</info>' );
		}

		return Command::SUCCESS;
	}

	protected function getRequest( InputInterface $input ): ?Request
	{
		$request = $input->hasOption( 'request' ) ? $input->getOption( 'request' ) : null;
		$query   = $input->hasOption( 'query' ) ? $input->getOption( 'query' ) : null;
		if ( $request || $query ) {
			return new Request(
				( new DataFormatter() )->decodeFormat( 'url', $query ?: '' ) ?: [],
				( new DataFormatter() )->decodeFormat( 'url', $request ?: '' ) ?: []
			);
		}

		return null;
	}
}
