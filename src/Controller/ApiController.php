<?php

namespace SyncEngine\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use SyncEngine\Entity\Automation;
use SyncEngine\Model\Abstract\EntityModel;
use SyncEngine\Model\AutomationModel;
use SyncEngine\Service\Execute;
use SyncEngine\Service\ExecuteContext;

class ApiController extends DefaultController
{
	#[Route( '/api/status', name: 'api' )]
	public function index(): JsonResponse
	{
		$results = [ $this->trans( 'API status' ) => $this->trans( 'Online' ) ];

		return $this->json( $results );
	}

	#[Route( '/api/v1/{entity}', name: 'api_list_entities' )]
	public function list_entities( Request $request, string $entity ): JsonResponse
	{
		/** @var EntityModel $model */
		$model = EntityModel::getEntityModelClass( $entity );

		if ( ! $model ) {
			return $this->json( [ 'success' => false, 'error' => $this->trans( 'Entity not found' ) ], Response::HTTP_NOT_IMPLEMENTED );
		}

		try {
			$query = $request->request->all();

			$list = $model::getAll( $query );
			foreach ( $list as &$entity ) {
				$entity = $entity->normalize();
			}
		} catch ( \Exception $e ) {
			return $this->json( [ 'success' => false, 'error' => $this->trans( $e->getMessage() ) ], Response::HTTP_INTERNAL_SERVER_ERROR );
		}

		$return = [
			'success' => true,
			'data'    => $list,
		];

		return $this->json( $return );
	}


	#[Route( '/api/v1/{entity}/{id}', name: 'api_get_entity', requirements: [ 'id' => '\d+' ] )]
	public function get_entity( Request $request, string $entity, int $id = 0 ): JsonResponse
	{
		/** @var EntityModel $model */
		$model = EntityModel::getEntityModelClass( $entity );

		if ( ! $model ) {
			return $this->json( [ 'success' => false, 'error' => $this->trans( 'Entity not found' ) ], Response::HTTP_NOT_IMPLEMENTED );
		}

		try {
			$fetch = $model::get( $id )->normalize();
		} catch ( \Exception $e ) {
			return $this->json( [ 'success' => false, 'error' => $this->trans( $e->getMessage() ) ], Response::HTTP_INTERNAL_SERVER_ERROR );
		}

		$return = [
			'success' => true,
			'data'    => $fetch,
		];

		return $this->json( $return );
	}

	#[Route( '/api/{endpoint}', name: 'api_endpoint' )]
	public function endpoint( Automation $automation, Execute $execute, Request $request = null ): JsonResponse
	{
		$model   = AutomationModel::get( $automation );
		$context = new ExecuteContext( $execute, $model );

		if ( $model->isRunning() ) {
			$context->addError( $this->trans( 'Automation is already running.' ) );

			return $this->json(
				[
					'success' => true,
					'errors'  => $context->getErrors(),
				]
			);
		}

		$context->setRequest( $request );
		$results = $execute->execute( $model, $context, $request );

		switch ( $model->getConfig( 'response' ) ) {
			case 'success':
				$results = $results['success'];
			break;
			case 'data':
				$results = $results['data'];
			break;
		}

		return $this->json( $results );
	}

	// @todo Allow in dev only.
	#[Route( '/profiler/api/{endpoint}', name: 'api_endpoint_profiler' )]
	public function endpoint_profiler( Automation $automation, Execute $execute, Request $request = null ): Response
	{
		$response = $this->endpoint( $automation, $execute, $request );

		try {
			$response = $this->render( 'api/endpoint.html.twig', [ 'response' => json_decode( $response->getContent(), true ) ] );
		} catch ( \Exception $e ) {}

		return $response;
	}

}
