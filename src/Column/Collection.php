<?php

namespace SyncEngine\Column;

use SyncEngine\Column\Type\CollectionColumnType;
use SyncEngine\Model\ColumnModel;
use SyncEngine\Service\Format\ArrayFormatter;
use SyncEngine\Service\Interface\FormatInterface;

class Collection extends ColumnModel
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

	public function format( $value, array $config, ?FormatInterface $source = null )
	{
		// Format each sub-field.

		return parent::format( $value, $config, $source );
	}

	public function getFormatter( $config = [] ): FormatInterface
	{
		$context[ ArrayFormatter::LIST ] = empty( $config['associative'] );
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
