<?php

namespace SyncEngine\EventListener;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadataInfo;

#[AsDoctrineListener( event: 'loadClassMetadata' )]
class TablePrefixEventListener
{
	public function __construct(
		private readonly string $prefix
	) {}

	public function loadClassMetadata( LoadClassMetadataEventArgs $eventArgs ): void
	{
		$classMetadata = $eventArgs->getClassMetadata();

		if (
			! $classMetadata->isInheritanceTypeSingleTable()
			|| $classMetadata->getName() === $classMetadata->rootEntityName
		) {
			$this->prefixTable( $classMetadata );
		}

		foreach ( $classMetadata->getAssociationMappings() as $fieldName => $mapping ) {
			if (
				$mapping['type'] === ClassMetadataInfo::MANY_TO_MANY
				&& $mapping['isOwningSide']
			) {
				$this->prefixJoinTable( $classMetadata, $fieldName, $mapping );
			}
		}
	}

	private function prefixTable( $classMetadata ): void
	{
		$tableName = $classMetadata->getTableName();

		if ( ! str_starts_with( $tableName, $this->prefix ) ) {
			$classMetadata->setPrimaryTable( [ 'name' => $this->prefix . $tableName ] );
		}
	}

	private function prefixJoinTable( $classMetadata, string $fieldName, array $mapping ): void
	{
		$tableName = $mapping['joinTable']['name'];

		if ( ! str_starts_with( $tableName, $this->prefix ) ) {
			$classMetadata->associationMappings[ $fieldName ]['joinTable']['name'] =
				$this->prefix . $tableName;
		}
	}
}
