<?php

namespace SyncEngine\Service;

use SyncEngine\Model\StorageModel;
use SyncEngine\Service\Data\SchemaData;

class SchemaConverter
{
	public function __construct(
		private SchemaData $target,
		private ?SchemaData $source = null
	) {}

	public static function fromStorage( StorageModel|string|int $storage ): SchemaConverter
	{
		return StorageModel::get( $storage )?->getDataSchemaConverter() ?? new SchemaConverter( new SchemaData() );
	}

	/**
	 * @param  array{ key: string, column: array }  $definitions
	 *
	 * @return SchemaConverter
	 */
	public static function fromDefinitions( array $targetDefinitions, array $sourceDefinitions = [] ): SchemaConverter
	{
		return new SchemaConverter(
			SchemaData::fromDefinitions( $targetDefinitions ),
			$sourceDefinitions ? SchemaData::fromDefinitions( $sourceDefinitions ) : null
		);
	}

	public function getTarget(): SchemaData
	{
		return $this->target;
	}

	public function getSource(): ?SchemaData
	{
		return $this->source;
	}

	public function convert( iterable $collection ): iterable
	{
		foreach ( $collection as $key => $value ) {
			$target = $this->getTarget()->getColumn( $key );
			if ( $target ) {
				$collection[ $key ] = $target->format( $value, $target->getConfig(), $this->getSource()?->getColumn( $key ) );
			}
		}

		return $collection;
	}
}
