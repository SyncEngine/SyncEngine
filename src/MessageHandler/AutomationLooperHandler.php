<?php

namespace App\MessageHandler;

use App\Controller\ApiController;
use App\Message\AutomationLooper;
use App\Repository\AutomationRepository;
use App\Service\AutomationService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class AutomationLooperHandler
{
	public function __construct( private AutomationRepository $automationRepository, private ApiController $apiController, private AutomationService $automationService ) {}

	public function __invoke( AutomationLooper $message ): void
	{
		$automation = $this->automationRepository->find( $message->getAutomationID() );

		$this->apiController->endpoint( $automation, $this->automationService );
	}
}