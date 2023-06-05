<?php

namespace App\Service;

use App\Controller\DefaultController;
use App\Entity\Automation;

class AutomationService
{
	public function execute( Automation $automation, $data = array() ): array
	{
		$flowService = new FlowService();
		return $flowService->execute( $automation->getFlow(), $data );
	}

	public function getAutomation( int $id ): Automation
	{
		return DefaultController::getEntityManager()->getRepository( Automation::class )->findOneBy( [ 'id' => $id ] );
	}

	public function getAutomations(): array
	{
		return DefaultController::getEntityManager()->getRepository( Automation::class )->findAll();
	}
}
