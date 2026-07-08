<?php

namespace SyncEngine\Controller\Api\Rest\v1;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use SyncEngine\Controller\Api\ApiController;
use SyncEngine\Model\Abstract\EntityModel;

#[Route('/rest/v1', name: 'rest_v1_')]
class ApiRestV1Controller extends ApiController
{

	#[Route( '/{entity:entity}', name: 'list_entities', methods: [ 'GET' ] )]
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


	#[Route( '/{entity:entity}/{id:id}', name: 'get_entity', requirements: [ 'id' => '\d+' ], methods: [ 'GET' ] )]
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


	#[Route( '/{entity:entity}', name: 'create_entity', methods: [ 'POST' ] )]
	public function create_entity( Request $request, string $entity ): JsonResponse
	{
		/** @var EntityModel $model */
		$model = EntityModel::getEntityModelClass( $entity );

		if ( ! $model ) {
			return $this->json( [ 'message' => $this->trans( 'Entity not found' ) ], Response::HTTP_NOT_IMPLEMENTED );
		}

		$body = json_decode( $request->getContent(), true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return $this->json( [ 'message' => 'Invalid JSON body' ], Response::HTTP_BAD_REQUEST );
		}

		try {
			$modelInstance = $model::create();

			$unknown = [];
			foreach ( $body as $key => $value ) {
				$setter = 'set' . ucfirst( $key );
				if ( method_exists( $modelInstance->getEntity(), $setter ) ) {
					$modelInstance->$setter( $value );
				} else {
					$unknown[] = $key;
				}
			}

			if ( $unknown ) {
				return $this->json( [ 'message' => 'Unknown fields: ' . implode( ', ', $unknown ) ], Response::HTTP_BAD_REQUEST );
			}

			$modelInstance->save( true );

			return $this->json( $modelInstance->normalize(), Response::HTTP_CREATED );
		} catch ( \Exception $e ) {
			return $this->json( [ 'message' => $this->trans( $e->getMessage() ) ], Response::HTTP_INTERNAL_SERVER_ERROR );
		}
	}


	#[Route( '/{entity:entity}/{id:id}', name: 'update_entity', requirements: [ 'id' => '\d+' ], methods: [ 'PUT', 'PATCH' ] )]
	public function update_entity( Request $request, string $entity, int $id ): JsonResponse
	{
		/** @var EntityModel $model */
		$model = EntityModel::getEntityModelClass( $entity );

		if ( ! $model ) {
			return $this->json( [ 'message' => $this->trans( 'Entity type not found' ) ], Response::HTTP_NOT_IMPLEMENTED );
		}

		$modelInstance = $model::get( $id );

		if ( ! $modelInstance || ! $modelInstance->hasEntity() ) {
			return $this->json( [ 'message' => $this->trans( 'Entity not found' ) ], Response::HTTP_NOT_FOUND );
		}

		$body = json_decode( $request->getContent(), true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return $this->json( [ 'message' => 'Invalid JSON body' ], Response::HTTP_BAD_REQUEST );
		}

		try {
			$unknown = [];
			foreach ( $body as $key => $value ) {
				$setter = 'set' . ucfirst( $key );
				if ( method_exists( $modelInstance->getEntity(), $setter ) ) {
					$modelInstance->$setter( $value );
				} else {
					$unknown[] = $key;
				}
			}

			if ( $unknown ) {
				return $this->json( [ 'message' => 'Unknown fields: ' . implode( ', ', $unknown ) ], Response::HTTP_BAD_REQUEST );
			}

			$modelInstance->update( true );

			return $this->json( $modelInstance->normalize() );
		} catch ( \Exception $e ) {
			return $this->json( [ 'message' => $this->trans( $e->getMessage() ) ], Response::HTTP_INTERNAL_SERVER_ERROR );
		}
	}


	#[Route( '/{entity:entity}/{id:id}', name: 'delete_entity', requirements: [ 'id' => '\d+' ], methods: [ 'DELETE' ] )]
	public function delete_entity( Request $request, string $entity, int $id ): JsonResponse
	{
		/** @var EntityModel $model */
		$model = EntityModel::getEntityModelClass( $entity );

		if ( ! $model ) {
			return $this->json( [ 'message' => $this->trans( 'Entity not found' ) ], Response::HTTP_NOT_IMPLEMENTED );
		}

		$modelInstance = $model::get( $id );

		if ( ! $modelInstance ) {
			return $this->json( [ 'message' => $this->trans( 'Entity not found' ) ], Response::HTTP_NOT_FOUND );
		}

		try {
			$modelInstance->delete( true );
			return $this->json( [ 'message' => $this->trans( 'Entity deleted' ) ], Response::HTTP_OK );
		} catch ( \Exception $e ) {
			return $this->json( [ 'message' => $this->trans( $e->getMessage() ) ], Response::HTTP_INTERNAL_SERVER_ERROR );
		}
	}
}
