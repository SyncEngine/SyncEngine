<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Request;
use App\Controller\DefaultController;
use App\Component\AutomationContext;
use App\Entity\Automation;
use App\Model\AutomationModel;

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

	public static function getAutomation( Automation|int $automation ): AutomationModel|null
	{
		if ( ! $automation instanceof Automation ) {
			$automation = DefaultController::getEntityManager()->getRepository( Automation::class )->findOneBy( [ 'id' => $automation ] );
		}
		if ( $automation ) {
			return new AutomationModel( $automation );
		}
		return null;
	}

	public static function getAutomations(): array
	{
		$automations = DefaultController::getEntityManager()->getRepository( Automation::class )->findAll();
		$models = [];
		foreach ( $automations as $automation ) {
			$models[ $automation->getId() ] = new AutomationModel( $automation );
		}
		return $models;
	}
}
