<?php

namespace App\Service;

use App\Controller\DefaultController;
use App\Component\AutomationContext;
use App\Entity\Automation;

class AutomationService
{
	public static function execute( Automation $automation, $data = array() ): array
	{
		$context = new AutomationContext( $automation );
		return FlowService::execute( $automation->getFlow(), $data, $context );
	}

	public static function getAutomation( int $id ): Automation
	{
		return DefaultController::getEntityManager()->getRepository( Automation::class )->findOneBy( [ 'id' => $id ] );
	}

	public static function getAutomations(): array
	{
		return DefaultController::getEntityManager()->getRepository( Automation::class )->findAll();
	}
}
