<?php

namespace App\Service;

use App\Controller\DefaultController;
use App\Message\AutomationLooper;
use App\Model\AutomationModel;
use App\Model\FlowModel;
use App\Model\StepModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;

class Execute
{
	public function __construct(
		private readonly MessengerManager $messengerManager, private readonly MessageBusInterface $messageBus,
	) {}

	public function schedule( AutomationModel $automation ): void
	{
		$this->messageBus->dispatch( new AutomationLooper( $automation->getId() ) );
	}

	public function execute( AutomationModel $automation, ExecutionContext $context, $data ): array
	{
		$flow = FlowModel::get( $automation->getFlow() );
		if ( ! $flow ) {
			return [
				'success' => false,
				'errors'  => 'Automation flow missing',
			];
		}

		$entityManager = DefaultController::getEntityManager();
		$automation->setRunning( true );
		$automation->persist( $entityManager, true );

		// Start new iteration. Will set to 1 if it's a new loop.
		$automation->nextIteration();

		$request = null;
		if ( $data instanceof Request ) {
			$request = $data->getContent();
			$data    = [];
		}

		if ( empty( $data ) ) {
			$sources = (array) $automation->getConfig( 'source' );

			if ( $request && in_array( 'request', $sources ) ) {
				$data = $request;

				$requestConfig = $automation->getConfig( 'request' );
				if ( ! empty( $requestConfig['format'] ) ) {
					$data = ( new Formatter() )->fromFormat( $requestConfig['format'], $data );
				}
				if ( ! empty( $requestConfig['param'] ) ) {
					$data = ( new TagParser( $data ) )->parseTag( $requestConfig['param'] );
				}

				if ( $data ) {
					$automation->setData( $data, 'request' );
				}
			}

			if ( empty( $data ) && in_array( 'tasks', $sources ) ) {
				$tasks = $automation->getConfig( 'source_tasks' );

				if ( $tasks ) {
					// Parse iteration data.
					$parser = new TagParser( [ 'context' => $context, 'iterator' => $automation->getIterator() ] );
					$tasks  = $parser->parseTagArray( $tasks );

					$data = $this->executeTask( $tasks[0], $context, $data );
				}
			}
		}

		if ( $data ) {
			$return = $this->executeFlow( $flow, $context, $data );

			if ( ! $automation->getIterator() || $automation->getLimit() !== count( $data ) ) {
				// Last iteration.
				$automation->endIterator();
			} else {
				$automation->persist( $entityManager, true );

				// Continue iteration.
				$this->schedule( $automation );

				$return = [ "added to queue!" ];
			}
		} else {
			// End iteration.
			$automation->endIterator();
			$context->addError( 'No data found' );
		}

		// Done!
		$automation->setRunning( false );
		$automation->persist( $entityManager, true );

		$this->messengerManager->handleQueue();

		$errors = $context->getErrors();
		if ( $errors ) {
			return [
				'success' => false,
				'errors'  => $errors,
			];
		}

		return [
			'success' => true,
			'data'    => $return ?? [],
		];
	}

	public function executeFlow( FlowModel $flow, ExecutionContext $context, $data ): array
	{
		$context->startFlow( $flow );

		foreach ( $flow->getSteps() as $step ) {
			$data = $this->executeStep( $step, $context, $data );
		}

		$context->endFlow();

		return $data;
	}

	public function executeStep( StepModel $step, ExecutionContext $context, $data ): array
	{
		$context->startStep( $step );

		$config = $step->getConfig();

		$tasks = $config['tasks'] ?? [];
		if ( $tasks ) {
			$conditionals = $config['conditionals'] ?? [];
			if ( empty( $conditionals ) || $step->validateConditionals( $conditionals, $data, $context ) ) {
				$data = $this->executeTasks( $tasks, $context, $data );
			}
		}

		$context->endStep();

		return $data;
	}

	public function executeTasks( array $tasksList, ExecutionContext $context, $data ): array
	{
		foreach ( $tasksList as $taskConfig ) {
			$data = $this->executeTask( $taskConfig, $context, $data );
		}

		return $data;
	}

	public function executeTask( array $config, ExecutionContext $context, $data ): array
	{
		if ( ! empty( $config['_disabled'] ) ) {
			return $data;
		}

		$task = $config['_class'] ?? '';
		if ( $task ) {
			$task = Tasks::getTask( $task );
			if ( $task ) {
				$context->startTask( $task );

				$parser = new TagParser( [ 'context' => $context, 'data' => $data ] );

				$data = $task->execute( $parser->parseTagArray( $config ), $context, $data );

				$context->endTask();
			}
		}

		return $data;
	}
}
