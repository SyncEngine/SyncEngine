<?php

namespace App\Service;

use App\Component\ExecutionContext;
use App\Controller\DefaultController;
use App\Message\AutomationLooper;
use App\Model\AutomationModel;
use App\Model\FlowModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;

class AutomationService
{
	public function __construct( private MessageBusInterface $bus ) {}

	public function execute( AutomationModel $automation, ExecutionContext $context, $data ): array
	{
		$flow = FlowModel::get( $automation->getFlow() );
		if ( ! $flow ) {
			return [ "Relation automation flow" => "Missing" ];
		}

		$automation->setRunning( true );
		$entityManager = DefaultController::getEntityManager();
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

					$data = TaskService::execute( $tasks[0], $context, $data );
				}
			}
		}

		if ( $data ) {
			// Run!
			$return = FlowService::execute( $flow, $context, $data );

			if ( ! $automation->getIterator() || $automation->getLimit() !== count( $data ) ) {
				// Last iteration.
				$automation->endIterator();
			} else {
				$automation->persist( $entityManager, true );

				// Continue iteration.
				$this->bus->dispatch( new AutomationLooper( $automation->getId() ) );
				$return = [ "added to queue!" ];
			}
		} else {
			// End iteration.
			$automation->endIterator();
			// No data found.
			$return = [ "error" => "No data found" ];
		}

		// @todo get request data based on config. > Move execution logic to model.
		$automation->setRunning( false );
		$automation->persist( $entityManager, true );

		return $return;
	}
}
