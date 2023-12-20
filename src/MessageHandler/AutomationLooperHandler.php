<?php

namespace SyncEngine\MessageHandler;

use SyncEngine\Controller\ApiController;
use SyncEngine\Controller\DefaultController;
use SyncEngine\Message\AutomationLooper;
use SyncEngine\Model\AutomationModel;
use SyncEngine\Repository\AutomationRepository;
use SyncEngine\Service\Execute;
use SyncEngine\Service\ExecutionContext;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class AutomationLooperHandler
{
	public function __construct(
		private readonly Execute $execute,
		// Load required to instantiate container.
		private readonly DefaultController $controller
	) {}

	public function __invoke( AutomationLooper $message ): void
	{
		$model = AutomationModel::get( $message->getAutomationId() );

		// @todo Provide context about previous loop?
		$context = new ExecutionContext( $this->execute, $model );

		$this->execute->trace()->load( $model, $message->getTraceId() );

		// @todo provide request of previous loop?
		$this->execute->execute( $model, $context );
	}
}
