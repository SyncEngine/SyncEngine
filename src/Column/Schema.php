<?php

namespace SyncEngine\Column;

use SyncEngine\Column\Interface\CollectionColumnInterface;
use SyncEngine\Column\Interface\SchemaColumnInterface;
use SyncEngine\Column\Type\CollectionColumnType;
use SyncEngine\Model\ColumnModel;
use SyncEngine\Service\Data\SchemaData;
use SyncEngine\Service\Format\ArrayFormatter;
use SyncEngine\Service\Interface\FormatInterface;

class Schema extends ColumnModel implements SchemaColumnInterface
{
	public function __construct()
	{
		parent::__construct();

		$this->type        = CollectionColumnType::TYPE;
		$this->name        = $this->trans( 'Schema' );
		$this->description = $this->trans( 'Schema definitions column' );
	}

	public function getFields(): array
	{
		return [
			'source'     => [
				'label'   => $this->trans( 'Schema source' ),
				'type'    => 'select',
				'default' => '',
				'choices' => [
					''        => $this->trans( 'Manual' ),
					'storage' => $this->trans( 'Storage' ),
				],
			],
			'storage'        => [
				'label'      => $this->trans( 'Schema storage' ),
				'type'       => 'entity',
				'entity'     => 'storage',
				'actions'    => [ 'edit', 'create' ],
				'query'      => [ 'where' => [ 'type' => 'schema' ] ],
				'conditions' => [ 'source' => 'storage', ],
			],
			'columns' => [
				'label'       => $this->trans( 'Column definitions' ),
				'description' => $this->trans(
					'Configure column types for each field.'
				),
				'type'        => 'schema',
				'conditions' => [ 'source' => [ 'operator' => 'empty' ] ],
			],
		];
	}

	public function format( $value, ?array $config = null, ?ColumnModel $source = null )
	{
		// Format each sub-field.
		$collection = parent::format( $value, $config, $source );

		if ( is_iterable( $collection ) ) {
			return $this->applySchema( $collection, $this->getSchemaColumns( $config ), $source );
		}

		return $collection;
	}

	protected function applySchema( iterable $collection, SchemaData $targetSchema, ?ColumnModel $source = null ): iterable
	{
		$sourceSchema = null;

		if ( $source instanceof SchemaColumnInterface ) {
			$sourceSchema = $source->getSchemaColumns();
		} elseif ( $source instanceof CollectionColumnInterface ) {
			$sourceColumn = $source->getCollectionColumn();
		}

		foreach ( $collection as $index => $collectionValue ) {
			if ( empty( $targetSchema[ $index ] ) ) {
				continue;
			}

			$column = $targetSchema[ $index ];

			if ( $sourceSchema ) {
				$sourceColumn = $sourceSchema[ $index ] ?? null;
			}

			$collection[ $index ] = $column->format( $collectionValue, $column->getConfig(), $sourceColumn ?? null );
		}

		return $collection;
	}

	/**
	 * @param  array|null  $config
	 *
	 * @return SchemaData
	 */
	public function getSchemaColumns( ?array $config = null ): SchemaData
	{
		$config = $config ?? $this->getConfig();

		$source = $config['source'] ?? null;
		if ( 'storage' === $source ) {
			return SchemaData::fromStorage( $config['storage'] ?? '' );
		}

		return SchemaData::fromDefinitions( $config['columns'] ?? [] );
	}

	public function initFormatter( $config = [] ): FormatInterface
	{
		$context[ ArrayFormatter::LIST ] = false;
		return new ArrayFormatter( $context );
	}

	public function normalize(): array
	{
		$normalize = parent::normalize();

		$normalize['modalProps'] = [
			'size' => 'lg',
		];

		return $normalize;
	}
}
