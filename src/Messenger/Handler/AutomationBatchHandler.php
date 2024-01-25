<?php

namespace SyncEngine\Messenger\Handler;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use SyncEngine\Controller\DefaultController;
use SyncEngine\Messenger\Message\AutomationBatch;
use SyncEngine\Model\AutomationModel;
use SyncEngine\Service\Execute;
use SyncEngine\Service\ExecutionContext;

#[AsMessageHandler]
class AutomationBatchHandler
{
	public function __construct(
		private readonly Execute $execute,
		// Load required to instantiate container.
		private readonly DefaultController $controller
	) {}

	public function __invoke( AutomationBatch $message ): void
	{
		$model = AutomationModel::get( $message->getAutomationId() );

		// @todo Provide context about previous loop?
		$context = new ExecutionContext( $this->execute, $model );

		$traceId = $message->getTraceId();
		if ( $traceId ) {
			$this->execute->trace()->load( $model, $traceId );
		}

		// @todo provide request of previous loop?
		$this->execute->execute( $model, $context );
	}
}
