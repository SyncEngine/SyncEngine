<?php

namespace SyncEngine\Controller\Api;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use SyncEngine\Messenger\MessengerManager;
use SyncEngine\Model\AutomationModel;
use SyncEngine\Model\Enum\TraceStatus;
use SyncEngine\Model\TraceModel;
use SyncEngine\Service\Execute;
use SyncEngine\Service\ExecuteContext;
use SyncEngine\Service\ExecuteData;
use SyncEngine\Service\ExecuteScheduler;

class ApiEndpointController extends ApiController
{
	public function __construct(
		private readonly MessengerManager $messengerManager,
		private readonly ExecuteScheduler $scheduler,
	) {}

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

	#[Route( '/endpoint/{endpoint}/{action}', name: 'endpoint_action', defaults: [ 'action' => '' ], methods: [ 'GET', 'POST', 'TRACE' ] )]
	#[Route( '/endpoint/{endpoint}/{action}/', name: 'endpoint_action_slash', defaults: [ 'action' => '' ], methods: [ 'GET', 'POST', 'TRACE' ] )]
	public function endpoint( string $endpoint, string $action, Execute $execute, ?Request $request = null ): Response
	{
		$model = AutomationModel::get( [ 'endpoint' => $endpoint ] );

		if ( ! $model || ! $model->hasEntity() ) {
			return $this->json(
				[ 'message' => $this->trans( 'Endpoint not found: {value}', [ 'value' => $endpoint ] ) ],
				Response::HTTP_NOT_FOUND
			);
		}

		if ( ! $action ) {
			if (
				$request->isMethod( 'POST' ) &&
				// @todo Create helper method?
				( ! $this->messengerManager->isInternal() || $this->messengerManager->isEnabled() )
			) {
				$action = 'schedule';
			} else {
				$action = 'execute';
			}
		}

		switch ( $action ) {
			// Runs automation instantly and returns result.
			case 'execute':
				if ( ! $model->canRunNow() ) {
					return $this->json(
						[ 'message' => $this->trans( 'Automation is already running' ) ],
						Response::HTTP_LOCKED
					);
				}

				$context = new ExecuteContext( $execute, $model );

				$context->registerRequest( $request );

				$results      = $execute->execute( $model, $context, $request );
				$responseType = $model->getConfig( 'response.type' );

				if ( 'file' === $responseType ) {
					$file = ExecuteData::create( $results['data'] ?? [] )->get( $model->getConfig( 'response.file.key' ), '' );

					if ( ! empty( $file ) ) {
						if ( is_string( $file ) && file_exists( $file ) ) {
							return new BinaryFileResponse( $file );
						}

						// @todo \SplTempFileObject support
						// @todo StreamedResponse support
						// @todo deleteAfterSend?
						// @see https://symfony.com/doc/current/components/http_foundation.html#serving-files
					}

					return new JsonResponse( [ 'message' => $this->trans( 'File not found' ) ], Response::HTTP_NOT_FOUND );
				}

				$results = $responseType ? $results[ $responseType ] ?? null : $results;
			break;

			// Schedules the automation and returns success state.
			case 'schedule':
				if ( $this->messengerManager->isInternal() && ! $this->messengerManager->isEnabled() ) {
					return $this->json(
						[ 'message' => $this->trans( 'Scheduled processes are disabled.' ) ],
						Response::HTTP_LOCKED
					);
				}

				$stamps = [];
				$delay  = $request->get( 'delay' ) ?? null;

				if ( $delay ) {
					$stamps[] = is_numeric( $delay ) ? new DelayStamp( (int) $delay ) : DelayStamp::delayUntil(
						new \DateTimeImmutable( $delay )
					);
				}

				$context = new ExecuteContext( $execute, $model );
				$context->registerRequest( $request );

				$scheduled = $this->scheduler->scheduleNewTrace( $model, $context, $stamps );
				if ( $scheduled->isRejected() ) {
					return $this->json(
						[ 'message' => $this->trans( 'Automation cannot accept a new request right now.' ) ],
						Response::HTTP_LOCKED
					);
				}

				$results = [
					'success' => true,
					'trace'   => $scheduled->getTraceId(),
					'status'  => $scheduled->getStatus(),
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
