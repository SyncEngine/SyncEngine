<?php

namespace SyncEngine\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use SyncEngine\Controller\DefaultController;
use SyncEngine\Model\AutomationModel;

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
}
