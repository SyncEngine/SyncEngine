<?php

namespace App\MessageHandler;

use App\Controller\ApiController;
use App\Controller\DefaultController;
use App\Message\AutomationLooper;
use App\Model\AutomationModel;
use App\Repository\AutomationRepository;
use App\Service\Execute;
use App\Service\ExecutionContext;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class AutomationLooperHandler
{
	public function __construct( private readonly Execute $execute, private readonly DefaultController $controller ) {}

	public function __invoke( AutomationLooper $message ): void
	{
		$model = AutomationModel::get( $message->getAutomationId() );

		// @todo Provide context about previous loop?
		$context = new ExecutionContext( $this->execute, $model );

		// @todo provide request of previous loop?
		$this->execute->execute( $model, $context );
	}
}
