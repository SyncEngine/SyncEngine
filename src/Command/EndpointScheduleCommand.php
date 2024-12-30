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
use SyncEngine\Service\Execute;
use SyncEngine\Service\ExecuteContext;

/**
 * @return void
 */
#[AsCommand(
	name: 'syncengine:endpoint:schedule',
	description: 'Schedule automation endpoint',
)]
class EndpointScheduleCommand extends EndpointCommand
{
	private Execute $execute;

	public function __construct( Execute $execute, DefaultController $controller )
	{
		$this->execute = $execute;
		parent::__construct( $controller );
	}

	protected function configure(): void
	{
		$this->addArgument( 'endpoint', InputArgument::REQUIRED, 'The automation endpoint.' );
		$this->addOption( 'request', null, InputOption::VALUE_OPTIONAL, 'The request parameters (URL formatted).', null );
		$this->addOption( 'query', null, InputOption::VALUE_OPTIONAL, 'The query parameters (URL formatted).', null );
	}

	protected function execute( InputInterface $input, OutputInterface $output ): int
	{
		$endpoint = $input->getArgument( 'endpoint' );

		if ( ! $endpoint ) {
			return parent::execute( $input, $output );
		}

		/** @var AutomationModel $model */
		$model = AutomationModel::get( [ 'endpoint' => $endpoint ] );

		if ( ! $model ) {
			$output->writeln( '<error>Endpoint not found</error>: <info>' . $endpoint . '</info>' );

			return Command::INVALID;
		}

		if ( $model->isRunning() ) {
			$output->writeln( '<error>Endpoint already running</error>: <info>' . $endpoint . '</info>' );

			return Command::INVALID;
		}

		$context = new ExecuteContext( $this->execute, $model );

		$request = $input->getOption( 'request' );
		$query   = $input->getOption( 'query' );
		if ( $request || $query ) {
			$context->setRequest(
				new Request(
					( new DataFormatter() )->decodeFormat( 'url', $query ),
					( new DataFormatter() )->decodeFormat( 'url', $request )
				)
			);
		}

		$this->execute->schedule( $model, $context );

		$output->writeln( '<comment>Endpoint scheduled</comment>: <info>' . $endpoint . '</info>' );

		return Command::SUCCESS;
	}
}
