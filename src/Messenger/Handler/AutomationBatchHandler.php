<?php

namespace SyncEngine\Messenger\Handler;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use SyncEngine\Controller\DefaultController;
use SyncEngine\Messenger\Message\AutomationBatch;
use SyncEngine\Model\AutomationModel;
use SyncEngine\Model\Enum\TraceStatus;
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

		if ( ! $model->canRunNow() ) {
			throw new \ErrorException( 'Endpoint already running.' );
		}

		// @todo Provide context about previous loop?
		$context = new ExecuteContext( $this->executeService, $model );

		$traceId = $message->getTraceId();
		$queuedRequest = [];

		if ( $traceId ) {
			$trace = TraceModel::load( $model, $traceId );
			if ( TraceStatus::QUEUED === $trace->getStatus() ) {
				$queuedRequest = $trace->pullQueuedRequest();
			}

			$context->registerTrace( $trace );
		}

		// Note: Request content is never passed through a message but should be stored in a batch.
		$query   = $message->getRequestQuery();
		$request = $message->getRequestParams();

		// @todo Set queued request in message? Or always use trace? Or merge request?
		if ( empty( $query ) && empty( $request ) && ! empty( $queuedRequest ) ) {
			$query   = (array) ( $queuedRequest['query'] ?? [] );
			$request = (array) ( $queuedRequest['params'] ?? [] );
		}

		if ( $query || $request ) {
			$context->registerRequest( new Request( $query, $request ) );
		}

		$this->executeService->execute( $model, $context );
	}
}
