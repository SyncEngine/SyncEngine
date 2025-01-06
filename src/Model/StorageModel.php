<?php

namespace SyncEngine\Model;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use SyncEngine\Entity\Storage;
use SyncEngine\Model\Abstract\EngineModel;
use SyncEngine\Model\Interface\Supervisable;
use SyncEngine\Model\Interface\Taggable;
use SyncEngine\Model\Trait\Data;
use SyncEngine\Model\Trait\Supervisor;
use SyncEngine\Model\Trait\Tags;
use SyncEngine\Service\Data\MapData;
use SyncEngine\Service\Data\SchemaData;
use SyncEngine\Service\DataFormatter;
use SyncEngine\Service\SchemaConverter;

/**
 * @extends EngineModel<Storage>
 *
 * @method int getId()
 * @method setId( int $id )
 * @method string getName()
 * @method setName( string $name )
 * @method string getDescription()
 * @method setDescription( string $description )
 * @method string getType()
 */
class StorageModel extends EngineModel implements Taggable, Supervisable
{
	use Data {
		getData as getDataDefault;
	}
	use Tags;
	use Supervisor;

	protected static array $_TYPES = [
		''         => 'Generic / Other',
		'entities' => 'Entities',
		'schema'   => 'Schema',
		'mapper'   => 'Mapper',
		'raw'      => 'Raw',
	];

	public function __construct( ?Storage $storage = null )
	{
		parent::__construct( $storage );
	}

	public function handleRequest( Request $request ): Response
	{
		return new Response();
	}

	public function parseConfig(): void
	{
		$this->setType( $this->getConfig( 'type', '' ) );
	}

	public static function getTypes(): array
	{
		return self::$_TYPES;
	}

	public static function addType( $type, $label ): void
	{
		self::$_TYPES[ $type ] = $label;
	}

	public function isRaw(): bool
	{
		return 'raw' === $this->getType();
	}

	public function setType( $type ): void
	{
		if ( ! array_key_exists( $type, self::$_TYPES ) ) {
			return;
		}
		$this->setConfig( $type, 'type' );
		$this->entity->setType( $type );
	}

	/**
	 * Get plain data.
	 *
	 * @param $key
	 * @param $default
	 *
	 * @return mixed
	 */
	public function getData( $key = null, $default = null ): mixed
	{
		if ( $this->isRaw() ) {
			return $this->getDataDefault( 'value', $default );
		}

		return $this->getDataDefault( $key, $default );
	}

	/**
	 * Return the storage data as a mapper format.
	 * The source key is the array key and the target key is the array value.
	 *
	 * @param  string  $sourceKey
	 * @param  string  $targetKey
	 *
	 * @return MapData
	 */
	public function getDataMap( string $sourceKey = '', string $targetKey = '' ): MapData
	{
		// Enforces data existence.
		$strictSourceKey = false;
		$strictTargetKey = false;

		$data = new MapData();

		if ( $this->isRaw() ) {
			return $data;
		}

		// Find column names.
		if ( ! $sourceKey || ! $targetKey ) {
			switch ( $this->getType() ) {
				case 'mapper':
					$sourceKey = $sourceKey ?: 'source';
					$targetKey = $targetKey ?: 'target';

					// The mapper type is strict on formatting.
					$strictSourceKey = true;
					$strictTargetKey = true;
				break;
				case 'schema':
					$config = $this->getConfig( 'schema', [] );

					$sourceKey = $sourceKey ?: $config['name_key'] ?? null;
					$targetKey = $targetKey ?: $config['label_key'] ?? null;

					if ( $sourceKey ) {
						$strictSourceKey = true;
					}
					if ( $targetKey ) {
						$strictTargetKey = true;
					}

					$sourceKey = $sourceKey ?? 'name';
					$targetKey = $targetKey ?? 'label';
				break;
				default:
					$columns = $this->getColumns();

					if ( ! $sourceKey && ! empty( $columns[0]['key'] ) ) {
						$sourceKey = $columns[0]['key'];
					}
					if ( ! $sourceKey && ! empty( $columns[1]['key'] ) ) {
						$targetKey = $columns[1]['key'];
					}
				break;
			}
		}

		foreach ( $this->getData() as $index => $value ) {

			if ( ! is_array( $value ) ) {
				if ( $strictSourceKey || $strictTargetKey ) {
					continue;
				}
				$left  = $index;
				$right = $value;
			} else {
				$left  = $value[ $sourceKey ] ?? null;
				$right = $value[ $targetKey ] ?? null;

				if ( null === $left ) {
					if ( $strictSourceKey ) {
						continue;
					}
					$left = $index;
				}
				if ( null === $right && $strictTargetKey ) {
					continue;
				}
			}

			$data->add( $left, $right );
		}

		if ( 'entities' === $this->getType() ) {
			$schema = $this->getDataSchema();
		}

		return $data;
	}

	public function getDataSchema(): ?SchemaData
	{
		switch ( $this->getType() ) {
			case 'schema':
				$definitions = $this->getConfig( 'schema.columns' );
				return SchemaData::fromDefinitions( $definitions );

			case 'mapper':
				// A mapper storage will return the output (target) schema.
				$target = $this->getConfig( 'mapper.schema.target' );
				return static::get( $target )?->getDataSchema() ?? new SchemaData( [] );
		}

		return null;
	}

	public function getDataSchemaConverter(): ?SchemaConverter
	{
		switch ( $this->getType() ) {
			case 'schema':
				$definitions = $this->getConfig( 'schema.columns' );
				return new SchemaConverter( SchemaData::fromDefinitions( $definitions ) );

			case 'mapper':
				$source = $this->getConfig( 'mapper.schema.source' );
				$target = $this->getConfig( 'mapper.schema.target' );

				return new SchemaConverter(
					static::get( $target )?->getDataSchema() ?? new SchemaData( [] ),
					$source ? static::get( $source )?->getDataSchema() : null
				);
		}

		return null;
	}

	/**
	 * Return storage data as associative array.
	 * It will use the data keys as the index for each value.
	 *
	 * @param $key
	 *
	 * @return array
	 */
	public function getDataAssociative( $key = '' ): array
	{
		if ( $this->isRaw() ) {
			return [ $key => $this->getData() ];
		}

		if ( ! $key ) {
			switch ( $this->getType() ) {
				case 'mapper':
					$key = 'source';
				break;
				case 'schema':
					$config = $this->getConfig( 'schema' );
					$key    = ( $config['name_key'] ?? '' ) ?: 'name';
				break;
				default:
					$key = 'key';
				break;
			}
		}

		$data = [];

		foreach ( $this->getData() as $index => $value ) {
			$data[ $value[ $key ] ?? $index ] = $value;
		}

		return $data;
	}

	/**
	 * Get all keys from the storage data.
	 * In case of an entity or columns it will return all column keys.
	 * In case of a mapper it will return all source keys.
	 * In case of a fields list it will return all field name keys.
	 *
	 * @return array
	 */
	public function getDataKeys(): array
	{
		if ( $this->isRaw() ) {
			return [];
		}

		switch ( $this->getType() ) {
			case 'mapper':
			case 'schema':
				return $this->getDataMap()->getSources();
			case 'entities':
			default:
				return $this->getColumns( 'key' ) ?: array_keys( $this->getData() ?? [] );
		}
	}

	/**
	 * Return only the root of all data keys.
	 * Any traversal data will be removed.
	 *
	 * Example: root.depth1.depth2 => root.
	 *
	 * @return array
	 */
	public function getDataRootKeys(): array
	{
		$keys = $this->getDataKeys();

		foreach ( $keys as &$key ) {
			$key = explode( '.', (string) $key )[0];
		}

		return array_unique( $keys );
	}

	/**
	 * Return all columns for this storage data.
	 * Can be filtered by key.
	 *
	 * @param $key
	 *
	 * @return array[]
	 */
	public function getColumns( $key = '' ): array
	{
		$columns = $this->getConfig( 'columns', [] );
		if ( 'mapper' === $this->getType() ) {
			$columns = [
				[
					'key'  => 'source',
					'name' => 'From',
				],
				[
					'key'  => 'target',
					'name' => 'To',
				],
			];
		}

		if ( $columns && $key ) {
			foreach ( $columns as &$column ) {
				$column = $column[ $key ] ?? '';
			}
		}

		return $columns;
	}

	public function normalize( $dependencies = false, $dependents = false ): array
	{
		$normalized = parent::normalize( $dependencies, $dependents );

		if ( 'schema' === $this->getType() ) {
			$normalized['_schema'] = $this->getDataSchema();
		}

		return $normalized;
	}

	public function getFields(): array
	{
		$choices = [];
		foreach ( self::getTypes() as $type => $label ) {
			$choices[ $type ] = $this->trans( $label );
		}

		return [
			'type' => [
				'label'   => $this->trans( 'Data type' ),
				'type'    => 'select',
				'default' => '',
				'choices' => $choices,
				'fields'  => [
					'entities' => [
						'conditions' => [ 'type' => [ '', 'entities' ] ],
						'fields'     => [
							'schema'  => [
								'label'   => $this->trans( 'Schema' ),
								'type'    => 'entity',
								'entity'  => 'storage',
								'query'   => [ 'where' => [ 'type' => 'schema' ] ],
								'actions' => [ 'edit', 'create' ],
							],
							'columns' => [
								'label'   => $this->trans( 'Columns' ),
								'type'    => 'grid',
								'name'    => 'columns',
								'columns' => [
									'key'  => $this->trans( 'Key' ),
									'name' => $this->trans( 'Name' ),
								],
							],
						],
					],
					'schema'   => [
						'conditions' => [ 'type' => 'schema' ],
						'nested'     => [
							'name_key'  => [
								'label'       => $this->trans( 'Field name key' ),
								'help'        => $this->trans(
									'By default it will fetch the index key unless the value is an array containing field information.'
								),
								'type'        => 'text',
								'placeholder' => 'name',
							],
							'label_key' => [
								'label'       => $this->trans( 'Field label key' ),
								'help'        => $this->trans(
									'Used in case the value is an array containing field information.'
								),
								'type'        => 'text',
								'placeholder' => 'label',
							],
							'columns'   => [
								'label'       => $this->trans( 'Column definitions' ),
								'description' => $this->trans(
									'Configure column types for each field.'
								),
								'type'        => 'schema',
							],
						],
					],
					'mapper'   => [
						'conditions' => [ 'type' => 'mapper' ],
						'label'      => $this->trans( 'Schema storages' ),
						'nested'     => [
							'schema' => [
								'nested' => [
									'' => [
										'inline' => 'fixed',
										'fields' => [
											'source' => [
												'label'   => $this->trans( 'From source schema' ),
												'type'    => 'entity',
												'entity'  => 'storage',
												'query'   => [ 'where' => [ 'type' => 'schema' ] ],
												'actions' => [ 'edit', 'create' ],
											],
											'target' => [
												'label'   => $this->trans( 'To target schema' ),
												'type'    => 'entity',
												'entity'  => 'storage',
												'query'   => [ 'where' => [ 'type' => 'schema' ] ],
												'actions' => [ 'edit', 'create' ],
											],
										],
									],
								],
							],
						],
					],
					'format'   => [
						'conditions' => [ 'type' => 'format' ],
						'label'      => $this->trans( 'Format options' ),
						'fields'     => [
							'format' => ( new DataFormatter() )->getFormatDecodeField(),
						],
					],
				],
			],
		];
	}

	public function getTags(): array
	{
		return [
			'config' => [],
			'data'   => [],
		];
	}

	public static function getEntityClass(): string
	{
		return Storage::class;
	}
}
