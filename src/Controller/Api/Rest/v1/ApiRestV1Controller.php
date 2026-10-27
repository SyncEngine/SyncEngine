<?php

namespace SyncEngine\Controller\Api\Rest\v1;

use ReflectionClass;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use SyncEngine\Attribute\ApiProperty;
use SyncEngine\Controller\Api\Abstract\AbstractApiController;
use SyncEngine\Entity\Abstract\EngineEntity;
use SyncEngine\Model\Abstract\EngineModel;
use SyncEngine\Model\AutomationModel;
use SyncEngine\Model\ConnectionModel;
use SyncEngine\Model\FlowModel;
use SyncEngine\Model\RoutineModel;
use SyncEngine\Model\StorageModel;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

#[Route( '/rest/v1', name: 'rest_v1_' )]
class ApiRestV1Controller extends AbstractApiController
{
	private const ALLOWED_MODELS = [
		'automation' => AutomationModel::class,
		'connection' => ConnectionModel::class,
		'flow'       => FlowModel::class,
		'routine'    => RoutineModel::class,
		'storage'    => StorageModel::class,
	];

	#[Route( '/{entity:entity}', name: 'list_entities', methods: [ 'GET' ] )]
	public function list_entities( Request $request, string $entity ): JsonResponse
	{
		/** @var class-string<EngineModel<EngineEntity>> $model */
		$model = $this->resolveModel( $entity );

		if ( ! $model ) {
			return $this->json( [ 'message' => $this->trans( 'Entity type not found' ) ], Response::HTTP_NOT_IMPLEMENTED );
		}

		// Check read scope.
		if ( ! $this->isGrantedScope( $entity . ':read' ) ) {
			throw new AccessDeniedException( 'Insufficient scopes: ' . $entity . ':read' );
		}

		try {
			$query = $request->query->all();

			// Max 100 items.
			if ( ! isset( $query['limit'] ) || 100 < $query['limit'] ) {
				$query['limit'] = 100;
			}

			if ( empty( $query['where']['status'] ) ) {
				$query['where']['status'] = '*';
			}

			if ( empty( $query['where']['visibility'] ) ) {
				$query['where']['visibility'] = '*';
			}

			$list = $model::getAll( $query );
			foreach ( $list as &$entityItem ) {
				$entityItem = $entityItem->normalize();
			}
			unset( $entityItem ); // Remove reference to avoid accidental modifications.
		} catch ( \Exception $e ) {
			return $this->errorResponse(
				$e,
				'An error occurred while retrieving entities',
				Response::HTTP_INTERNAL_SERVER_ERROR,
				'Error in list_entities'
			);
		}

		return $this->json( $list );
	}

	#[Route( '/{entity:entity}/{id:id}', name: 'get_entity', requirements: [ 'id' => '\d+' ], methods: [ 'GET' ] )]
	public function get_entity( Request $request, string $entity, int $id = 0 ): JsonResponse
	{
		/** @var class-string<EngineModel<EngineEntity>> $model */
		$model = $this->resolveModel( $entity );

		if ( ! $model ) {
			return $this->json( [ 'message' => $this->trans( 'Entity type not found' ) ], Response::HTTP_NOT_IMPLEMENTED );
		}

		// Check read scope.
		if ( ! $this->isGrantedScope( $entity . ':read' ) ) {
			throw new AccessDeniedException( 'Insufficient scopes: ' . $entity . ':read' );
		}

		$modelInstance = $model::get( $id );

		if ( ! $modelInstance || ! $modelInstance->hasEntity() ) {
			return $this->json( [ 'message' => $this->trans( 'Entity not found' ) ], Response::HTTP_NOT_FOUND );
		}

		try {
			$fetch = $modelInstance->normalize();
			if ( ! $fetch ) {
				return $this->json(
					[ 'message' => $this->trans( 'Entity not found' ) ],
					Response::HTTP_NOT_FOUND
				);
			}
		} catch ( \Exception $e ) {
			return $this->errorResponse(
				$e,
				'An error occurred while retrieving the entity',
				Response::HTTP_INTERNAL_SERVER_ERROR,
				'Error in get_entity'
			);
		}

		return $this->json( $fetch );
	}

	#[Route( '/{entity:entity}', name: 'create_entity', methods: [ 'POST' ] )]
	public function create_entity( Request $request, string $entity ): JsonResponse
	{
		/** @var class-string<EngineModel<EngineEntity>> $model */
		$model = $this->resolveModel( $entity );

		if ( ! $model ) {
			return $this->json( [ 'message' => $this->trans( 'Entity not found' ) ], Response::HTTP_NOT_IMPLEMENTED );
		}

		// Check write scope.
		if ( ! $this->isGrantedScope( $entity . ':create' ) ) {
			throw new AccessDeniedException( 'Insufficient scopes: ' . $entity . ':write' );
		}

		$body = json_decode( $request->getContent(), true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return $this->json( [ 'message' => 'Invalid JSON body' ], Response::HTTP_BAD_REQUEST );
		}

		if ( empty( $body ) ) {
			return $this->json( [ 'message' => 'Empty JSON body' ], Response::HTTP_BAD_REQUEST );
		}

		try {
			$modelInstance = $model::create();

			$success = $this->setModelValuesByArray( $modelInstance, $body, 'create' );
			if ( true !== $success ) {
				return $success;
			}

			try {
				$modelInstance->save( true );
			} catch ( \Throwable $e ) {
				return $this->errorResponse(
					$e,
					'Could not create entity',
					Response::HTTP_BAD_REQUEST,
					'Error saving entity in create_entity'
				);
			}

			return $this->json( $modelInstance->normalize(), Response::HTTP_CREATED );
		} catch ( \Throwable $e ) {
			return $this->errorResponse(
				$e,
				'An error occurred while creating the entity',
				Response::HTTP_INTERNAL_SERVER_ERROR,
				'Error in create_entity'
			);
		}
	}

	#[Route( '/{entity:entity}/{id:id}', name: 'update_entity', requirements: [ 'id' => '\d+' ], methods: [
		'PUT',
		'PATCH',
	] )]
	public function update_entity( Request $request, string $entity, int $id ): JsonResponse
	{
		/** @var class-string<EngineModel<EngineEntity>> $model */
		$model = $this->resolveModel( $entity );

		if ( ! $model ) {
			return $this->json(
				[ 'message' => $this->trans( 'Entity type not found' ) ],
				Response::HTTP_NOT_IMPLEMENTED
			);
		}

		// Check update scope.
		if ( ! $this->isGrantedScope( $entity . ':update' ) ) {
			throw new AccessDeniedException( 'Insufficient scopes: ' . $entity . ':update' );
		}

		$modelInstance = $model::get( $id );

		if ( ! $modelInstance || ! $modelInstance->hasEntity() ) {
			return $this->json( [ 'message' => $this->trans( 'Entity not found' ) ], Response::HTTP_NOT_FOUND );
		}

		$body = json_decode( $request->getContent(), true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return $this->json( [ 'message' => 'Invalid JSON body' ], Response::HTTP_BAD_REQUEST );
		}

		if ( empty( $body ) ) {
			return $this->json( [ 'message' => 'Empty JSON body' ], Response::HTTP_BAD_REQUEST );
		}

		try {
			$success = $this->setModelValuesByArray( $modelInstance, $body, 'edit' );
			if ( true !== $success ) {
				return $success;
			}

			try {
				$modelInstance->update( true );
			} catch ( \Throwable $e ) {
				return $this->errorResponse(
					$e,
					'Could not update entity',
					Response::HTTP_BAD_REQUEST,
					'Error updating entity in update_entity'
				);
			}

			return $this->json( $modelInstance->normalize() );
		} catch ( \Throwable $e ) {
			return $this->errorResponse(
				$e,
				'An error occurred while updating the entity',
				Response::HTTP_INTERNAL_SERVER_ERROR,
				'Error in update_entity'
			);
		}
	}

	#[Route( '/{entity:entity}/{id:id}', name: 'delete_entity', requirements: [ 'id' => '\d+' ], methods: [ 'DELETE' ] )]
	public function delete_entity( Request $request, string $entity, int $id ): JsonResponse
	{
		/** @var class-string<EngineModel<EngineEntity>> $model */
		$model = $this->resolveModel( $entity );

		if ( ! $model ) {
			return $this->json( [ 'message' => $this->trans( 'Entity not found' ) ], Response::HTTP_NOT_IMPLEMENTED );
		}

		// Check delete scope.
		if ( ! $this->isGrantedScope( $entity . ':delete' ) ) {
			throw new AccessDeniedException( 'Insufficient scopes: ' . $entity . ':delete' );
		}

		$modelInstance = $model::get( $id );

		if ( ! $modelInstance ) {
			return $this->json( [ 'message' => $this->trans( 'Entity not found' ) ], Response::HTTP_NOT_FOUND );
		}

		try {
			$modelInstance->trash( true );

			return $this->json( [ 'message' => $this->trans( 'Entity deleted' ) ], Response::HTTP_OK );
		} catch ( \Exception $e ) {
			return $this->errorResponse(
				$e,
				'Could not delete entity',
				Response::HTTP_INTERNAL_SERVER_ERROR,
				'Error deleting entity in delete_entity'
			);
		}
	}

	/**
	 * Set model values from request data, respecting Permissions attributes.
	 *
	 * Uses reflection to read #[Permissions] attributes on entity properties
	 * and only allows updates to fields with appropriate permissions for the operation.
	 *
	 * @param  EngineModel<EngineEntity>  $model
	 * @param  'create'|'edit' $operation
	 * @return true|JsonResponse
	 */
	private function setModelValuesByArray( EngineModel $model, array $data, string $operation = 'edit' ): true|JsonResponse
	{
		$entity = $model->getEntity();
		$writableFields = $this->getWritableFields( $entity, $operation );

		if ( empty( $writableFields ) ) {
			return $this->json(
				[ 'message' => 'Model type does not support field updates.' ],
				Response::HTTP_BAD_REQUEST
			);
		}

		$errors = [];
		$unknown = [];
		$notAllowed = [];

		foreach ( $data as $key => $value ) {
			// Check if field is writable
			if ( ! in_array( $key, $writableFields, true ) ) {
				$notAllowed[] = $key;
				continue;
			}

			$setter = 'set' . ucfirst( $key );
			if ( method_exists( $entity, $setter ) ) {
				try {
					$model->$setter( $value );
				} catch ( \Throwable $e ) {
					$errors[ $key ] = $e->getMessage();
				}
			} else {
				$unknown[] = $key;
			}
		}

		// Check for fields not allowed to be written (security concern)
		if ( ! empty( $notAllowed ) ) {
			return $this->json(
				[
					'message' => 'Fields cannot be updated: ' . implode( ', ', $notAllowed ),
					'errors' => [ 'not_allowed' => $notAllowed ],
				],
				Response::HTTP_BAD_REQUEST
			);
		}

		// Check for unknown fields (field doesn't exist on entity)
		if ( ! empty( $unknown ) ) {
			return $this->json(
				[ 'message' => 'Unknown fields: ' . implode( ', ', $unknown ) ],
				Response::HTTP_BAD_REQUEST
			);
		}

		// Check for validation errors
		if ( ! empty( $errors ) ) {
			return $this->json(
				[
					'message' => 'Invalid field values: ' . implode( ', ', array_keys( $errors ) ),
					'errors' => $errors,
				],
				Response::HTTP_BAD_REQUEST
			);
		}

		return true;
	}

	/**
	 * Get list of writable field names for an entity.
	 *
	 * Inspects the entity class for properties with #[Permissions] attributes
	 * where write=true and returns a list of field names that can be written to.
	 *
	 * @param EngineEntity $entity
	 * @return list<string>
	 */
	private function getWritableFields( EngineEntity $entity, string $operation = 'edit' ): array
	{
		$writableFields = [];
		$reflection = new \ReflectionClass( $entity );

		foreach ( $reflection->getProperties() as $property ) {
			$attributes = $property->getAttributes( ApiProperty::class );

			foreach ( $attributes as $attribute ) {
				/** @var ApiProperty $apiProperty */
				$apiProperty = $attribute->newInstance();

				// Check operation-specific access
				$isWritable = match( $operation ) {
					'create' => $apiProperty->creatable,
					'edit' => $apiProperty->updatable,
					default => false,
				};

				if ( $isWritable ) {
					$writableFields[] = $property->getName();
				}
			}
		}

		return $writableFields;
	}

	private function resolveModel( string $entity ): ?string
	{
		if ( ! isset( self::ALLOWED_MODELS[ $entity ] ) ) {
			return null;
		}

		$modelClass = self::ALLOWED_MODELS[ $entity ];

		if ( ! is_subclass_of( $modelClass, EngineModel::class ) ) {
			return null;
		}

		return $modelClass;
	}
}
