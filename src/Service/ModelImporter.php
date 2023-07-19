<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class ModelImporter
{
	private array $done = [];
	private array $errors = [];
	private array $data = [];
	private EntityManagerInterface $em;

	public function __construct(
		EntityManagerInterface $entityManager
	) {
		$this->em = $entityManager;
	}

	public function import( array $data ): array
	{
		$this->done = [];
		$this->data = $data;
		foreach ( $data as $ref => $fields ) {
			if ( isset( $this->done[ $ref ] ) ) {
				continue;
			}
			$this->importRef( $ref, $fields );
			$this->done[ $ref ] = true;
		}

		$this->em->flush();

		return $this->errors;
	}

	public function importRef( $ref, $fields )
	{
		// Already running.
		if ( isset( $this->done[ $ref ] ) ) {
			return $this->done[ $ref ];
		}

		$entity = $fields['_entity'];
		if ( ! $entity ) {
			$this->errors[] = 'Entity not found for: ' . $ref;

			return null;
		}
		unset( $fields['_entity'] );

		$modelClass = $this->getModelClass( $entity );
		if ( ! class_exists( $modelClass ) ) {
			$this->errors[] = 'Model not found for: ' . $ref . ' (' . $modelClass . ')';

			return null;
		}

		$model = $modelClass::get( $ref );
		if ( $model ) {
			// Update.
			$entity = $model->getEntity();
		} else {
			// Create.
			$entity = "\\App\\Entity\\" . $entity;
			$entity = new $entity();
			$entity->setRef( $ref );

			$model = new $modelClass( $entity );
		}

		$this->done[ $ref ] = $model;

		$queued = [];

		foreach ( $fields as $property => $value ) {
			if ( 'ref' === $property ) {
				continue;
			}

			if ( is_string( $value ) && ! empty( $this->data[ $value ] ) ) {
				$queued[ $property ] = $value;
				continue;
			}

			if ( is_array( $value ) ) {
				$queued[ $property ] = $value;
				continue;
			}

			$setter = 'set' . ucfirst( $property );
			if ( method_exists( $entity, $setter ) ) {
				// Call setter on model.
				call_user_func( [ $model, $setter ], $value );
			}
		}

		// Make sure that we store the scalar properties first so we can get an ID for this entity.
		$model->persist( $this->em );

		foreach ( $queued as $property => $value ) {

			if ( is_string( $value ) && ! empty( $this->data[ $value ] ) ) {
				$relation = $this->importRef( $value, $this->data[ $value ] );
				$value    = $relation->getEntity();
			}

			if ( is_array( $value ) ) {
				$value = $this->parseConfigFields( $value );
			}

			$setter = 'set' . ucfirst( $property );
			if ( method_exists( $entity, $setter ) ) {
				// Call setter on model.
				call_user_func( [ $model, $setter ], $value );
			}
		}

		$model->persist( $this->em );

		return $model;
	}

	public function parseConfigFields( array $config ): array
	{
		foreach ( $config as $key => $value ) {
			if ( is_string( $key ) ) {
				if ( isset( $this->data[ $key ] ) ) {
					$model          = $this->importRef( $value, $this->data[ $value ] );
					$config[ $key ] = $model->getId();
				}
			} elseif ( is_array( $value ) ) {
				$config[ $key ] = $this->parseConfigFields( $value );
			}
		}

		return $config;
	}

	public function getModelClass( $class ): string
	{
		if ( is_object( $class ) ) {
			$class = ( new \ReflectionClass( $class ) )->getShortName();
		}

		return '\\App\\Model\\' . $class . 'Model';
	}
}
