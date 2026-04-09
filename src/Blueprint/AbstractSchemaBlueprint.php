<?php

namespace SyncEngine\Blueprint;

use SyncEngine\Structure\Data\ResourceData;

abstract class AbstractSchemaBlueprint extends AbstractStorageBlueprint
{
	/**
	 * @required
	 * @var array
	 */
	protected array $object_columns = [];

	public function init(): void
	{
		if ( empty( $this->object_columns ) ) {
			throw new \ErrorException( 'Object columns not defined.' );
		}

		parent::init();
		$this->storageType = 'schema';
		$this->fields = [
			'object_columns' => [
				'label'    => $this->trans( 'Column definitions' ),
				'default'  => $this->object_columns,
				'type'     => 'schema',
				'readonly' => true,
			],
			'columns'     => [
				'label'       => $this->trans( 'Extend column definitions' ),
				'description' => $this->trans(
					'Configure custom column types for each field.'
				),
				'type'        => 'schema',
			],
		];
	}

	public function getObjectColumns(): array
	{
		return $this->object_columns;
	}

	public function parseConfig( $config ): array
	{
		$config  = ResourceData::create( $config );
		$columns = $config->get( 'schema.columns', [] );

		$config->set( array_merge( $this->getObjectColumns(), $columns ), 'schema.columns' );

		return $config->get();
	}
}
