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
		$model = AutomationModel::get( $message->getAutomationId() );

		// @todo Provide context about previous loop?
		$context = new ExecuteContext( $this->executeService, $model );

		$traceId = $message->getTraceId();
		if ( $traceId ) {
			$context->setTrace( TraceModel::load( $model, $traceId ) );
		}

		$query   = $message->getRequestQuery();
		$request = $message->getRequestParams();
		if ( $query || $request ) {
			$context->setRequest( new Request( $query, $request ) );
		}

		// @todo provide request of previous loop?
		$this->executeService->execute( $model, $context );
	}
}
