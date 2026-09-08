<?php

namespace SyncEngine\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use SyncEngine\Controller\DefaultController;
use SyncEngine\Model\AutomationModel;
use SyncEngine\Service\Trace\TraceManager;

/**
 * @return void
 */
#[AsCommand(
	name: 'syncengine:automation:traces:prune',
	description: 'Remove automation traces by configured retention period.',
)]
class AutomationTracesPruneCommand extends Command
{
	public function __construct( DefaultController $controller ) {
		parent::__construct();
	}
	protected function configure(): void
	{
		$this->addArgument( 'automation', InputArgument::OPTIONAL, 'The automation ID or Ref.' );
	}

	protected function execute( InputInterface $input, OutputInterface $output ): int
	{
		$automation = $input->getArgument( 'automation' );

		if ( ! $automation ) {
			$automations = AutomationModel::getAll( [] );
		} else {
			$automations = [ AutomationModel::get( $automation ) ];
		}

		$result = true;

		try {
			foreach ( $automations as $automation ) {
				( new TraceManager() )->pruneTraces( $automation, true );
				$output->writeln( '<comment>Traces pruned for automation</comment>: <info>' . $automation->getName() . '</info> (ID: <info>' . $automation->getId() . '</info> | Ref: <info>' . $automation->getRef() . '</info>)' );
			}
		} catch ( \Throwable $e ) {
			$result = $e;
		}

		if ( $result instanceof \Throwable ) {
			$output->writeln( '<error>' . $result->getMessage() . '</error>' );
			return Command::FAILURE;
		}

		return Command::SUCCESS;
	}
}
