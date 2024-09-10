<?php

namespace SyncEngine\Controller\Api\Rest\v1;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use SyncEngine\Controller\DefaultController;
use SyncEngine\Model\Abstract\EntityModel;

#[Route('/rest/v1', name: 'rest_v1_')]
class ApiRestV1Controller extends DefaultController
{

	#[Route( '/{entity}', name: 'list_entities', methods: [ 'GET' ] )]
	public function list_entities( Request $request, string $entity ): JsonResponse
	{
		/** @var EntityModel $model */
		$model = EntityModel::getEntityModelClass( $entity );

		if ( ! $model ) {
			return $this->json( [ 'message' => $this->trans( 'Entity not found' ) ], Response::HTTP_NOT_IMPLEMENTED );
		}

		try {
			$query = $request->request->all();

			// Max 100 items.
			if ( ! isset( $query['limit'] ) || 100 < $query['limit'] ) {
				$query['limit'] = 100;
			}

			$list = $model::getAll( $query );
			foreach ( $list as &$entity ) {
				$entity = $entity->normalize();
			}
		} catch ( \Exception $e ) {
			return $this->json( [ 'message' => $this->trans( $e->getMessage() ) ], Response::HTTP_INTERNAL_SERVER_ERROR );
		}

		return $this->json( $list );
	}


	#[Route( '/{entity}/{id}', name: 'get_entity', requirements: [ 'id' => '\d+' ], methods: [ 'GET' ] )]
	public function get_entity( Request $request, string $entity, int $id = 0 ): JsonResponse
	{
		/** @var EntityModel $model */
		$model = EntityModel::getEntityModelClass( $entity );

		if ( ! $model ) {
			return $this->json( [ 'message' => $this->trans( 'Entity not found' ) ], Response::HTTP_NOT_IMPLEMENTED );
		}

		try {
			$fetch = $model::get( $id )->normalize();
		} catch ( \Exception $e ) {
			return $this->json( [ 'message' => $this->trans( $e->getMessage() ) ], Response::HTTP_INTERNAL_SERVER_ERROR );
		}

		return $this->json( $fetch );
	}
}
