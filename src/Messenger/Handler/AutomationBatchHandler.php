<?php

namespace SyncEngine\Messenger\Handler;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use SyncEngine\Controller\DefaultController;
use SyncEngine\Messenger\Message\AutomationBatch;
use SyncEngine\Model\AutomationModel;
use SyncEngine\Model\TraceModel;
use SyncEngine\Service\Execute;
use SyncEngine\Service\ExecuteContext;

#[AsMessageHandler]
class AutomationBatchHandler
{
	public function __construct(
		// Load required to instantiate container.
		private readonly DefaultController $controller,
		private readonly Execute           $executeService
	) {}

	public function __invoke( AutomationBatch $message ): void
	{
		// @todo Verify if we want do reset automatically.
		//DefaultController::getEntityManager()->clear();

		$model = AutomationModel::get( $message->getAutomationId() );

		// @todo Provide context about previous loop?
		$context = new ExecuteContext( $this->executeService, $model );

		$traceId = $message->getTraceId();
		if ( ! $traceId ) {
			throw new \LogicException( 'Trace ID is required for execution.' );
		}

		$trace = TraceModel::load( $model, $traceId );
		if ( ! $trace ) {
			throw new \LogicException( 'Could not find a registered trace.' );
		}

		$context->registerTrace( $trace );

		// Message payload can carry iterator-updated request state; queued trace payload is fallback.
		$query   = $message->getRequestQuery();
		$request = $message->getRequestParams();

		if ( empty( $query ) && empty( $request ) ) {
			// Fallback.
			$queuedRequest = $trace->getRequest();
			$query   = (array) ( $queuedRequest['query'] ?? [] );
			$request = (array) ( $queuedRequest['params'] ?? [] );
		}

		if ( $query || $request ) {
			$context->registerRequest( new Request( $query, $request ) );
		}

		$this->executeService->execute( $model, $context );
	}
}
