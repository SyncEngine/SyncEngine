<?php

namespace SyncEngine\Messenger\Handler;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use SyncEngine\Controller\DefaultController;
use SyncEngine\Messenger\Message\AsyncExecuteMessage;
use SyncEngine\Model\AutomationModel;
use SyncEngine\Model\TraceModel;
use SyncEngine\Service\Execute;
use SyncEngine\Service\ExecuteContext;

#[AsMessageHandler]
class ExecuteBatchHandler
{
	public function __construct(
		// Load required to instantiate container.
		private readonly DefaultController $controller,
		private readonly Execute           $executeService
	) {}

	public function __invoke( AsyncExecuteMessage $message ): void
	{
		$model = AutomationModel::get( $message->getAutomationId() );

		// @todo Provide context about previous loop?
		$context = new ExecuteContext( $this->executeService, $model );

		$traceId = $message->getTraceId();
		if ( $traceId ) {
			$context->setTrace( TraceModel::load( $model, $traceId ) );
		}

		// @todo provide request of previous loop?
		$this->executeService->execute( $model, $context );
	}
}
