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

		if ( $model->isRunning() ) {
			throw new \ErrorException( 'Endpoint already running.' );
		}

		// @todo Provide context about previous loop?
		$context = new ExecuteContext( $this->executeService, $model );

		$traceId = $message->getTraceId();
		if ( $traceId ) {
			$context->setTrace( TraceModel::load( $model, $traceId ) );
		}

		// Note: Request content is never passed through a message but should be stored in a batch.
		$query   = $message->getRequestQuery();
		$request = $message->getRequestParams();
		if ( $query || $request ) {
			$context->setRequest( new Request( $query, $request ) );
		}

		$this->executeService->execute( $model, $context );
	}
}
