<?php

namespace SyncEngine\Controller\Api;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use SyncEngine\Model\AutomationModel;
use SyncEngine\Model\Enum\TraceStatus;
use SyncEngine\Model\TraceModel;
use SyncEngine\Service\Execute;
use SyncEngine\Service\ExecuteContext;

class ApiEndpointController extends ApiController
{
	#[Route( '/endpoint', name: 'list_endpoints', methods: [ 'GET' ] )]
	public function list_endpoints( Request $request ): JsonResponse
	{
		try {
			$query = $request->request->all();

			// Max 100 items.
			if ( ! isset( $query['limit'] ) || 100 < $query['limit'] ) {
				$query['limit'] = 100;
			}

			$automations = AutomationModel::getAll( $query );

			$endpoints = [];
			foreach ( $automations as $automation ) {
				$endpoints[] = [
					'endpoint'    => (string) $automation->getEndpoint(),
					'name'        => (string) $automation->getName(),
					'description' => (string) $automation->getDescription(),
					'link'        => $this->generateUrl(
						'syncengine_api_endpoint_execute',
						[ 'endpoint' => $automation->getEndpoint() ],
						UrlGeneratorInterface::ABSOLUTE_URL
					),
				];
			}
		} catch ( \Exception $e ) {
			return $this->json(
				[ 'message' => $this->trans( $e->getMessage() ) ],
				Response::HTTP_INTERNAL_SERVER_ERROR
			);
		}

		return $this->json( $endpoints );
	}

	#[Route( '/endpoint/{endpoint:endpoint}/{action:action}', name: 'endpoint_execute', defaults: [ 'action' => 'execute' ], methods: [ 'GET', 'POST', 'TRACE' ] )]
	public function endpoint( string $endpoint, string $action, Execute $execute, Request $request = null ): JsonResponse
	{
		$model = AutomationModel::get( [ 'endpoint' => $endpoint ] );

		if ( ! $model || ! $model->hasEntity() ) {
			return $this->json(
				[ 'message' => $this->trans( 'Endpoint not found: {value}', [ 'value' => $endpoint ] ) ],
				Response::HTTP_NOT_FOUND
			);
		}

		switch ( $action ) {
			case 'execute':
				if ( $model->isRunning() ) {
					return $this->json(
						[ 'message' => $this->trans( 'Automation is already running' ) ],
						Response::HTTP_LOCKED
					);
				}

				$context = new ExecuteContext( $execute, $model );

				$context->setRequest( $request );

				$results = $execute->execute( $model, $context, $request );
				$param   = $model->getConfig( 'response' );

				$results = $param ? $results[ $param ] ?? null : $results;
			break;

			case 'schedule':
				/*if ( $model->isRunning() ) {
					return $this->json( [ 'message' => $this->trans( 'Automation is already running' ) ], Response::HTTP_LOCKED );
				}

				if ( $model->isScheduled() ) {
					return $this->json( [ 'message' => $this->trans( 'Automation is already scheduled' ) ], Response::HTTP_LOCKED );
				}*/

				$stamps = [];
				$delay  = $request->get( 'delay' ) ?? null;

				if ( $delay ) {
					$stamps[] = is_numeric( $delay ) ? new DelayStamp( (int) $delay ) : DelayStamp::delayUntil(
						new \DateTimeImmutable( $delay )
					);
				}

				$context = new ExecuteContext( $execute, $model );
				$context->setRequest( $request );

				$execute->schedule( $model, $context, $stamps );

				$results = [
					'success' => true,
				];
			break;

			case 'status':
				if ( ! $model->isRunning() ) {
					if ( $model->isScheduled() ) {
						return $this->json(
							[
								'status'  => TraceStatus::SCHEDULED->value,
								'message' => $this->trans( 'Automation is scheduled' ),
							],
							Response::HTTP_FOUND
						);
					}

					return $this->json(
						[
							'status'  => 'idle',
							'message' => $this->trans( 'Automation is idle' ),
						],
						Response::HTTP_FOUND
					);
				}

				$trace = TraceModel::get(
					[
						'automation' => $model->getId(),
						'status'     => [ TraceStatus::RUNNING, TraceStatus::SCHEDULED ],
					]
				);

				if ( $trace->hasEntity() ) {
					$results = [
						'success' => true,
						'trace'   => $trace->getId(),
						'status'  => $trace->getStatus(),
						//@todo 'iteration' => $trace->getIteration(),
					];
				} else {
					$results = [
						'success' => false,
					];
				}
			break;
			default:
				throw $this->createNotFoundException();
		}

		return $this->json( $results );
	}

}
