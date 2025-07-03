<?php

namespace SyncEngine\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use SyncEngine\Controller\DefaultController;
use SyncEngine\Model\AutomationModel;
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
		parent::__construct( $controller );

		$this->execute = $execute;
	}

	protected function configure(): void
	{
		parent::configure();

		$this->addOption( 'delay', null, InputOption::VALUE_OPTIONAL, 'When to schedule, provide a date string (Y-m-d H:i:s) or seconds delay.', null );
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

		if ( $model->isScheduled() ) {
			$output->writeln( '<error>Endpoint already scheduled</error>: <info>' . $endpoint . '</info>' );

			return Command::INVALID;
		}

		$context = new ExecuteContext( $this->execute, $model );

		$request = $this->getRequest( $input );
		if ( $request ) {
			$context->registerRequest( $request );
		}

		$stamps = [];
		$delay = $input->hasOption( 'delay' ) ? $input->getOption( 'delay' ) : null;

		if ( $delay ) {
			$stamps[] = is_numeric( $delay ) ? new DelayStamp( (int) $delay ) : DelayStamp::delayUntil( new \DateTimeImmutable( $delay ) );
		}

		$this->execute->schedule( $model, $context, $stamps );

		$output->writeln( '<comment>Endpoint scheduled</comment>: <info>' . $endpoint . '</info>' );

		return Command::SUCCESS;
	}
}
