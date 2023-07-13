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

		$return = [];
		$automation->setRunning( true );

		$entityManager = DefaultController::getEntityManager();
		$automation->persist( $entityManager, true );

		// Start new iteration. Will set to 1 if it's a new loop.
		$automation->nextIteration();

		if ( empty( $data ) ) {
			$sources = (array) $automation->getConfig( 'source' );

			if ( in_array( 'request', $sources ) ) {
				$request = $context->getRequest()->getContent();
				if ( $request ) {
					$data = $request;
				}
			}

			if ( empty( $data ) && in_array( 'tasks', $sources ) ) {
				$tasks = $automation->getConfig( 'source_tasks' );

				if ( $tasks ) {
					// Parse iteration data.
					$parser = new TagParser( [ 'context' => $context, 'iterator' => $automation->getIterator() ] );
					$tasks  = $parser->parseTagArray( $tasks );

					$data = TaskService::execute( $tasks[0], $context, $data );
				}
			}
		}

		if ( $data ) {

			// Run!
			$return = FlowService::execute( $flow, $context, $data );

			if ( $automation->getLimit() !== count( $data ) ) {
				// Last iteration.
				$automation->endIterator();
			} else {
				// Continue iteration.
				$return = [ "__continue" ];
			}
		} else {
			// End iteration.
			$automation->endIterator();
			// No data found.
			$return = [ "error" ];
		}

		// @todo get request data based on config. > Move execution logic to model.
		$automation->setRunning( false );
		$automation->persist( $entityManager, true );

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
