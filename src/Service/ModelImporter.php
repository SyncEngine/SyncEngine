<?php

namespace SyncEngine\Service;

use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use SyncEngine\Model\Abstract\EntityModel;

class ModelImporter
{
	private array $done = [];
	private array $errors = [];
	private array $data = [];
	private EntityManagerInterface $em;
	private TranslatorInterface $translator;

	public function __construct(
		EntityManagerInterface $entityManager, TranslatorInterface $translator
	) {
		$this->em         = $entityManager;
		$this->translator = $translator;
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

		$entity = $fields['_entity'] ?? '';
		if ( ! $entity ) {
			$this->errors[] = $this->translator->trans( 'Entity not found for: {ref}', [ 'ref' => $ref ] );

			return null;
		}
		unset( $fields['_entity'] );

		$modelClass = EntityModel::getEntityModelClass( $entity );
		if ( ! class_exists( $modelClass ) ) {
			$this->errors[] = $this->translator->trans(
				'Model not found for: {ref} ({class})',
				[ 'ref' => $ref, 'class' => $modelClass ]
			);

			return null;
		}

		$model = $modelClass::get( $ref );
		if ( $model ) {
			// Update.
			$entity = $model->getEntity();
		} else {
			// Create.
			$entity = "\\SyncEngine\\Entity\\" . $entity;
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

			if ( method_exists( $entity, $setter ) || method_exists( $model, $setter ) ) {
				$this->setMethodValue( $value, $setter, $model, $entity );
			}
		}

		// Make sure that we store the scalar properties first so we can get an ID for this entity.
		$model->persist( true, $this->em );

		foreach ( $queued as $property => $value ) {

			if ( is_string( $value ) && ! empty( $this->data[ $value ] ) ) {
				$relation = $this->importRef( $value, $this->data[ $value ] );
				$value    = $relation->getEntity();
			}

			if ( is_array( $value ) ) {
				$value = $this->parseSubFields( $value );
			}

			$setter = 'set' . ucfirst( $property );
			if ( method_exists( $entity, $setter ) || method_exists( $model, $setter ) ) {
				$this->setMethodValue( $value, $setter, $model, $entity );
			}
		}

		$model->update( false, $this->em );

		return $model;
	}

	public function setMethodValue( $value, string $method, EntityModel $model, object $entity ): void
	{
		try {
			call_user_func( [ $model, $method ], $value );

			return;
		} catch ( \TypeError $e ) {
			// Nope.
		}

		try {
			$methodRef = new \ReflectionMethod( $model, $method );
		} catch ( \Exception $e ) {
			$methodRef = new \ReflectionMethod( $entity, $method );
		}

		$firstParam = $methodRef->getParameters()[0];
		$paramType  = (string) $firstParam->getType();

		if ( 'mixed' === $paramType ) {
			return;
		}
		if ( str_starts_with( $paramType, '?' ) ) {
			$paramType = str_replace( '?', 'null|', $paramType );
		}

		$paramTypes = explode( '|', $paramType );

		if ( ! in_array( gettype( $value ), $paramTypes ) ) {
			foreach ( $paramTypes as $type ) {
				if ( ! class_exists( $type ) ) {
					continue;
				}
				try {
					$typeRef = new \ReflectionClass( $type );
					if ( $typeRef->isEnum() ) {
						$value = $type::from( $value );
					} elseif ( $typeRef->implementsInterface( DateTimeInterface::class ) ) {
						if ( str_starts_with( $value, '-' ) ) {
							$value = null;
						} else {
							$value = new $type( $value );
						}
					} else {
						$value = new $type( $value );
					}
				} catch ( \Exception $e ) {
					$this->errors[] = $e->getMessage();
				}
			}
		}

		try {
			call_user_func( [ $model, $method ], $value );
		} catch ( \TypeError $e ) {
			$message = [
				$e->getMessage(),
				...$this->errors
			];
			throw new \TypeError( implode( ' || ', $message ), $e->getCode(), $e );
		}
	}

	public function parseSubFields( array $fields ): array
	{
		foreach ( $fields as $key => $value ) {
			if ( is_string( $value ) ) {
				if ( isset( $this->data[ $value ] ) ) {
					$model          = $this->importRef( $value, $this->data[ $value ] );
					$fields[ $key ] = $model->getId();
				}
			} elseif ( is_array( $value ) ) {
				$fields[ $key ] = $this->parseSubFields( $value );
			}
		}

		return $fields;
	}
}
