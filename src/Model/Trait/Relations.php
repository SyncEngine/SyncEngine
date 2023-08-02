<?php

namespace App\Model\Trait;

use App\Controller\EntityController;
use App\Entity\Automation;
use App\Entity\Flow;
use App\Entity\Step;
use App\Entity\Connection;
use App\Entity\Dataset;
use App\Entity\Relation;
use App\Model\Interface\Persistable;

trait Relations
{
	private array $relations = [];
	private array $_relationEntities = [];

	public function isRelationEmpty( Relation $entity ): bool {
		$objects = [
			Automation::class => $entity->getAutomation(),
			Connection::class => $entity->getConnection(),
			Dataset::class => $entity->getDataset(),
			Flow::class => $entity->getFlow(),
			Step::class => $entity->getStep(),
		];

		$objects = array_filter( $objects );

		return empty( $objects );
	}

	public function fetchRelations(): array
	{
		// Reset relations, do NOT reset relationIds for cleaning purposes!
		$this->relations = [];

		if ( isset( $this->entity ) && is_callable( [ $this->entity, 'getRelations' ] ) ) {
			foreach ( $this->entity->getRelations() as $relation ) {
				$objects = [
					Automation::class => $relation->getAutomation(),
					Connection::class => $relation->getConnection(),
					Dataset::class => $relation->getDataset(),
					Flow::class => $relation->getFlow(),
					Step::class => $relation->getStep(),
				];

				foreach ( $objects as $type => $object ) {
					if ( $object ) {
						$this->relations[ $type ][ $object->getId() ] = $object;
						$this->_relationEntities[ $type ][ $object->getId() ] = $relation;
					}
				}
			}
		}

		return $this->relations;
	}

	public function getRelations(): mixed
	{
		if ( empty( $this->relations ) ) {
			$this->fetchRelations();
		}

		return $this->relations;
	}

	public function setRelations( $relations = [] ): void
	{
		// Make sure we have the latest data.
		$this->fetchRelations();

		$newRelationships = [];

		foreach ( $relations as $relation ) {
			if ( $relation instanceof Persistable ) {
				$relation = $relations->getEntity();
			}

			$class = EntityController::getEntityClass( $relation );

			$newRelationships[ $class ][] = $relation->getId();

			$exists = $this->relations[ $class ][ $relation->getId() ] ?? null;
			if ( $exists ) {
				$_relation = $this->_relationEntities[ $class ][ $relation->getId() ] ?? null;
				if ( $_relation ) {
					continue;
				}
			}

			$this->addRelation( $relation );
		}

		foreach ( $this->_relationEntities as $type => $objects ) {
			foreach ( $objects as $id => $entity ) {
				if ( ! isset( $newRelationships[ $type ][ $id ] ) ) {
					$this->removeRelation( $entity );
				}
			}
		}
	}

	public function removeRelation( $object ): void {
		if ( $object instanceof Persistable ) {
			$object = $object->getEntity();
		}

		$class = EntityController::getEntityClass( $object );

		if ( $this instanceof Persistable ) {
			$relationEntity = $this->_relationEntities[ $class ][ $object->getId() ] ?? null;
			if ( $relationEntity ) {
				switch ( true ) {
					case $object instanceof Automation:
						$relationEntity->removeAutomation( $object );
					break;
					case $object instanceof Connection:
						$relationEntity->removeConnection( $object );
					break;
					case $object instanceof Dataset:
						$relationEntity->removeDataset( $object );
					break;
					case $object instanceof Flow:
						$relationEntity->removeFlow( $object );
					break;
					case $object instanceof Step:
						$relationEntity->removeStep( $object );
					break;
				}

				if ( $this->isRelationEmpty( $relationEntity ) ) {
					$this->getEntity()->removeRelation( $relationEntity );
				}
			}
		}

		unset( $this->_relationEntities[ $class ][ $object->getId() ] );
		unset( $this->_relationEntities[ $class ][ $object->getId() ] );
	}

	public function addRelation( $object ): void
	{
		if ( $object instanceof Persistable ) {
			$object = $object->getEntity();
		}

		$class = EntityController::getEntityClass( $object );

		$this->relations[ $class ][ $object->getId() ] = $object;

		if ( $this instanceof Persistable ) {
			$relation = new Relation();

			if ( $object instanceof Persistable ) {
				$object = $object->getEntity();
			}
			switch ( true ) {
				case $object instanceof Automation:
					$relation->setAutomation( $object );
				break;
				case $object instanceof Connection:
					$relation->setConnection( $object );
				break;
				case $object instanceof Dataset:
					$relation->setDataset( $object );
				break;
				case $object instanceof Flow:
					$relation->setFlow( $object );
				break;
				case $object instanceof Step:
					$relation->setStep( $object );
				break;
				default:
					// Not an entity.
					return;
			}

			// Set this entity.
			switch ( true ) {
				case $this->entity instanceof Automation:
					$relation->setAutomation( $this->entity );
				break;
				case $this->entity instanceof Connection:
					$relation->setConnection( $this->entity );
				break;
				case $this->entity instanceof Dataset:
					$relation->setDataset( $this->entity );
				break;
				case $this->entity instanceof Flow:
					$relation->setFlow( $this->entity );
				break;
				case $this->entity instanceof Step:
					$relation->setStep( $this->entity );
				break;
			}

			$this->getEntity()->addRelation( $relation );
		}
	}
}
