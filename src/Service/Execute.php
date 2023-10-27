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

	public function fetch( AutomationModel $automation, ExecutionContext $context, $data = null ): array
	{
		$request = null;
		if ( $data instanceof Request ) {
			$request = $data->getContent();
			$data    = [];
		} elseif ( ! empty( $data ) ) {
			return $data;
		}

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

		if ( empty( $data ) && in_array( 'retrieve', $sources ) ) {
			try {
				$tasks = $automation->getConfig( 'retrieve' );

				if ( $tasks ) {
					// Parse iteration data.
					if ( $automation->hasIterator() ) {
						$parser = new TagParser( [ 'iterator' => $automation->getIterator() ], false );
						$tasks  = $parser->parseTagArray( $tasks );
					}

					$data = $this->executeTask( $tasks[0], $context, $data );
				}
			} catch ( \Throwable $e ) {
				$data = [];
				$context->addError( $e );
			}
		}

		if ( 'local' === $automation->getConfig( 'batch_method' ) ) {
			$data = array_slice( $data, $automation->getOffset(), $automation->getLimit() );
		}

		return $data;
	}

	public function execute( AutomationModel $automation, ExecutionContext $context, $data = null ): array
	{
		$entityManager = DefaultController::getEntityManager();
		$automation->setRunning( true );
		$automation->persist( $entityManager, true );

		// Start new iteration. Will set to 1 if it's a new loop.
		$automation->nextIteration();

		$data = $this->fetch( $automation, $context, $data );

		if ( $data ) {
			$return = $data;

			$actions = $automation->getActions();
			if ( $actions ) {
				try {
					$return = $this->executeTasks( $actions, $context, $data );
				} catch ( \Throwable $e ) {
					$data = [];
					$context->addError( $e );
				}
			}

			if ( ! $automation->hasIterator() || $automation->getLimit() !== count( $data ) ) {
				// Last iteration.
				$automation->endIterator();
			} else {
				// Store before schedule so the iterator is up-to-date.
				$automation->persist( $entityManager, true );

				// Continue iteration.
				$this->schedule( $automation );

				// @todo Log instead of return?
				$return = 'Added to queue!';
			}
		} else {
			// End iteration.
			$automation->endIterator();
			$context->addError( 'No data found' );
		}

		$this->messengerManager->handleQueue();

		if ( ! $automation->getIteration() ) {
			$automation->setRunning( false );
		}

		// Persist any changes.
		$automation->persist( $entityManager, true );

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

			if ( ! empty( $conditionals ) ) {

				$conditionalResource = array_merge(
					[ 'context' => $context, 'data' => $data ],
					$step->getTagsResource( $config )
				);

				$parser = new TagParser( $conditionalResource );

				$conditionals = $parser->parseTagArray( $conditionals );
			}

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

				$tagsResource = array_merge(
					[ 'context' => $context, 'data' => $data ],
					$task->getTagsResource( $config )
				);

				$parser = new TagParser( $tagsResource );

				$data = $task->execute( $parser->parseTagArray( $config ), $context, $data );

				$context->endTask();
			}
		}

		return $data;
	}
}
