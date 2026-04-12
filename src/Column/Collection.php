<?php

namespace SyncEngine\Column;

use SyncEngine\Column\Interface\CollectionColumnInterface;
use SyncEngine\Column\Interface\SchemaColumnInterface;
use SyncEngine\Column\Type\CollectionColumnType;
use SyncEngine\Form\Fields\InputFieldType;
use SyncEngine\Form\Fields\Interface\FieldConfigInterface;
use SyncEngine\Model\ColumnModel;
use SyncEngine\Service\Format\ArrayFormatter;
use SyncEngine\Service\Interface\FormatInterface;
use SyncEngine\Structure\Data\ResourceData;

class Collection extends ColumnModel implements CollectionColumnInterface
{
	public function __construct()
	{
		parent::__construct();

		$this->type        = CollectionColumnType::TYPE;
		$this->name        = $this->trans( 'Collection' );
		$this->description = $this->trans( 'A collection of a column type' );
	}

	public function getFields(): array
	{
		return [
			'associative' => [
				'label' => $this->trans( 'Associative' ),
				'type'  => 'switch',
			],
			'column' => [
				'label' => $this->trans( 'Column Type' ),
				'type'  => 'column',
				'view'  => 'full',
			],
		];
	}

	public function format( $value, ?array $config = null, ?ColumnModel $source = null )
	{
		$collection = parent::format( $value, $config, $source );

		$column = $this->getCollectionColumn( $config );

		if ( $column ) {

			if ( $source instanceof CollectionColumnInterface ) {
				$subSource = $source->getCollectionColumn();

				foreach ( $collection as $index => $collectionValue ) {
					$collection[ $index ] = $column->format( $collectionValue, $column->getConfig(), $subSource ?? null );
				}
			} elseif ( $source instanceof SchemaColumnInterface ) {
				$subSchema = $source->getSchemaColumns();

				foreach ( $collection as $index => $collectionValue ) {
					$collection[ $index ] = $column->format( $collectionValue, $column->getConfig(), $subSchema[ $index ] ?? null );
				}
			} else {
				foreach ( $collection as $index => $collectionValue ) {
					$collection[ $index ] = $column->format( $collectionValue, $column->getConfig() );
				}
			}
		}

		return ( ! empty( $config['associative'] ) ) ? $collection : ResourceData::values( $collection );
	}

	public function getCollectionColumn( ?array $config = null ): ?ColumnModel
	{
		$config = $config ?? $this->getConfig();
		$columnConfig = $config['column'] ?? null;

		if ( $columnConfig ) {
			$column = ColumnModel::create( $columnConfig['_class'] );
			$column->setConfig( $columnConfig );
		}

		return $column ?? null;
	}

	public function initFormatter( $config = [] ): FormatInterface
	{
		$context[ ArrayFormatter::LIST ] = empty( $config['associative'] );
		return new ArrayFormatter( $context );
	}

	public function getInput( array $config = [] ): ?FieldConfigInterface
	{
		$config = $config ?? $this->getConfig();

		$column = $this->getCollectionColumn( $config );

		if ( $column instanceof SchemaColumnInterface ) {
			$field = [
				'type'         => 'repeater',
				'customizable' => true,
				'actions'      => [ 'delete' ],
				'fieldset'     => $column->getSchemaColumns()?->getFields()->generateLabels(),
			];
		} else {
			$input = $column?->getInput() ?? [ 'type' => 'input' ];
			$field = [
				'type'         => 'params',
				'customizable' => true,
				'format'       => 'json',
				'columns'      => ! empty( $config['associative'] ) ?
					[
						'key'   => $this->trans( 'Key/Name' ),
						'value' => $input
					]
					:
					[
						'value' => $input
					],
			];
		}

		return new InputFieldType( $field );
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
