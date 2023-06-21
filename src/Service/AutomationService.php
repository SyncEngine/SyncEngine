<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Request;
use App\Controller\DefaultController;
use App\Component\AutomationContext;
use App\Entity\Automation;

class AutomationService
{
	public static function execute( Automation $automation, Request $request ): array
	{
		if ( ! $automation->getFlow() ) {
			return ["Relation automation flow" => "Missing"];
		}

		$data = [];
		// @todo get request data based on config.
		$context = new AutomationContext( $automation );
		return FlowService::execute( $automation->getFlow(), $context, $data );
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
