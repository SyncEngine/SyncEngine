<?php

namespace SyncEngine\Column;

use SyncEngine\Column\Interface\CollectionColumnInterface;
use SyncEngine\Column\Interface\SchemaColumnInterface;
use SyncEngine\Column\Type\CollectionColumnType;
use SyncEngine\Model\ColumnModel;
use SyncEngine\Model\StorageModel;
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

		$targetSchema = $this->getSchemaColumns();
		$sourceSchema = [];

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
	 * @return ColumnModel[]
	 */
	public function getSchemaColumns( ?array $config = null ): array
	{
		$config = $config ?? $this->getConfig();

		$source = $config['source'] ?? null;
		if ( 'storage' === $source ) {
			$storage = StorageModel::get( $config['storage'] );
			$columns = $storage?->getDataSchema() ?? [];
		} else {
			$columns = $config['columns'] ?? [];
		}

		$schema = [];

		if ( $columns ) {
			foreach ( $columns as $index => $columnConfig ) {
				$column = ColumnModel::get( $columnConfig['_class'] );
				if ( $column ) {
					$column->setConfig( $column );
					$schema[ $index ] = $column;
				}
			}
		}

		return $schema;
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
