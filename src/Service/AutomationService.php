<?php

namespace App\Service;

use App\Component\TagParser;
use App\Controller\DefaultController;
use App\Component\ExecutionContext;
use App\Entity\Automation;
use App\Model\AutomationModel;

class AutomationService
{
	public static function execute( AutomationModel $automation, ExecutionContext $context, $data ): array
	{
		$flow = FlowService::getFlow( $automation->getFlow() );
		if ( ! $flow ) {
			return [ "Relation automation flow" => "Missing" ];
		}

		$automation->setRunning( true );
		$return = [];

		$tasks = $automation->getConfig( 'source_tasks' );

		if ( $tasks ) {

			if ( $automation->getLimit() ) {
				if ( $automation->getIteration() ) {
					$automation->setIteration( 1 );
				} else {
					$automation->nextIteration();
				}

				$entityManager = DefaultController::getEntityManager();
				$automation->persist( $entityManager, true );

				$parser = new TagParser( [ 'config' => $automation->getConfig(), 'data' => $automation->getData() ] );
				$tasks = $parser->parseTagArray( $tasks );

				$data = TaskService::execute( $tasks[0], $context, $data );

				if ( $automation->getLimit() > count( $data ) ) {
					// Last iteration.
					$automation->setIteration( 0 );
					$automation->persist( $entityManager, true );

					$return = FlowService::execute( $flow, $context, $data );
				} else {
					// Continue iteration.
					FlowService::execute( $flow, $context, $data );

					$return = [ "__continue" ];
				}
			} else {
				$data = TaskService::execute( $tasks[0], $context, $data );

				$return = FlowService::execute( $flow, $context, $data );
			}
		}

		// @todo get request data based on config. > Move execution logic to model.
		$automation->setRunning( false );
		return $return;
	}

	public static function getAutomation( Automation|int $automation ): AutomationModel|null
	{
		if ( ! $automation instanceof Automation ) {
			$automation = DefaultController::getEntityManager()
			                               ->getRepository( Automation::class )
			                               ->findOneBy( [ 'id' => $automation ] );
		}
		if ( $automation ) {
			return new AutomationModel( $automation );
		}

		return null;
	}

	public static function getAutomations(): array
	{
		$automations = DefaultController::getEntityManager()->getRepository( Automation::class )->findAll();
		$models      = [];
		foreach ( $automations as $automation ) {
			$models[ $automation->getId() ] = new AutomationModel( $automation );
		}

		return $models;
	}
}
