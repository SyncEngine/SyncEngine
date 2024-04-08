<?php

namespace SyncEngine\Column;

use SyncEngine\Column\Type\CollectionColumnType;
use SyncEngine\Model\ColumnModel;
use SyncEngine\Service\Format\ArrayFormatter;
use SyncEngine\Service\Interface\FormatInterface;

class Schema extends ColumnModel
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

	public function format( $value, array $config, ?FormatInterface $source = null )
	{
		// Format each sub-field.

		return parent::format( $value, $config, $source );
	}

	public function getFormatter( $config = [] ): FormatInterface
	{
		$context[ ArrayFormatter::LIST ] = false;
		return new ArrayFormatter( $context );
	}
}
