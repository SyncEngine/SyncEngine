<?php

namespace SyncEngine\Model;

use SyncEngine\Entity\Dataset;
use SyncEngine\Model\Abstract\EntityModel;
use SyncEngine\Model\Interface\Taggable;
use SyncEngine\Model\Trait\Data;
use SyncEngine\Model\Trait\Tags;
use SyncEngine\Service\Formatter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @method int getId()
 * @method setId( int $id )
 * @method string getName()
 * @method setName( string $name )
 * @method string getDescription()
 * @method setDescription( string $description )
 * @method string getType()
 */
class DatasetModel extends EntityModel implements Taggable
{
	use Data {
		getData as getDataDefault;
	}
	use Tags;

	protected static array $_TYPES = [
		'Generic / Other' => '',
		'Entities'        => 'entities',
		'Fields'          => 'fields',
		'Mapper'          => 'mapper',
		'Formatted'       => 'format',
	];

	public function __construct( ?Dataset $dataset = null )
	{
		parent::__construct( $dataset );
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
		self::$_TYPES[ $label ] = $type;
	}

	public function setType( $type ): void
	{
		if ( ! in_array( $type, self::$_TYPES, true ) ) {
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
		$type = $this->getType();

		if ( 'format' === $type ) {
			return $this->getDataDefault( 'value', $default );
		}

		return $this->getDataDefault( $key, $default );
	}

	/**
	 * Return the dataset as a mapper format.
	 * The source key is the array key and the target key is the array value.
	 *
	 * @param  string  $sourceKey
	 * @param  string  $targetKey
	 *
	 * @return array
	 */
	public function getDataMap( string $sourceKey = '', string $targetKey = '' ): array
	{
		// Enforces data existence.
		$strictSourceKey = false;
		$strictTargetKey = false;

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
				case 'fields':
					$config = $this->getConfig( 'fields', [] );

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

		$data = [];

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

			$data[ $left ] = $right;
		}

		return $data;
	}

	/**
	 * Return dataset as associative array.
	 * It will use the data keys as the index for each value.
	 *
	 * @param $key
	 *
	 * @return array
	 */
	public function getDataAssociative( $key = '' ): array
	{
		if ( ! $key ) {
			switch ( $this->getType() ) {
				case 'formatted':
					return [ $key => $this->getData() ];
				break;
				case 'mapper':
					$key = 'source';
				break;
				case 'fields':
					$config = $this->getConfig( 'fields' );
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
	 * Get all keys from the dataset.
	 * In case of an entity or columns it will return all column keys.
	 * In case of a mapper it will return all source keys.
	 * In case of a fields list it will return all field name keys.
	 *
	 * @return array
	 */
	public function getDataKeys(): array
	{
		switch ( $this->getType() ) {
			case 'formatted':
				return [];
			case 'mapper':
			case 'fields':
				return array_keys( $this->getDataMap() );
			case 'entities':
			default:
				return $this->getColumns( 'key' ) ?: array_keys( $this->getData() );
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
	 * Return all columns for this dataset.
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

	public function getFields(): array
	{
		return [
			'type' => [
				'label'   => $this->trans( 'Data type' ),
				'type'    => 'select',
				'default' => '',
				'choices' => array_flip( self::$_TYPES ),
				'fields'  => [
					'columns' => [
						'conditionals' => [ 'type' => [ '', 'entities' ] ],
						'label'        => $this->trans( 'Columns' ),
						'type'         => 'grid',
						'name'         => 'columns',
						'columns'      => [
							'key'  => $this->trans( 'Key' ),
							'name' => $this->trans( 'Name' ),
						],
					],
					'fields'  => [
						'conditionals' => [ 'type' => 'fields' ],
						'nested'       => [
							'configuration' => [
								'label'   => $this->trans( 'Configuration' ),
								'type'    => 'select',
								'choices' => [
									'' => $this->trans( 'Simple' ),
									//'advanced' => 'Advanced', @todo Field config.
								],
							],
							'name_key'      => [
								'label'       => $this->trans( 'Field name key' ),
								'help'        => $this->trans( 'By default it will fetch the array key unless the value is an array containing field information.' ),
								'type'        => 'text',
								'placeholder' => 'name',
							],
							'label_key'     => [
								'label'       => $this->trans( 'Field label key' ),
								'help'        => $this->trans( 'Used in case the value is an array containing field information.' ),
								'type'        => 'text',
								'placeholder' => 'label',
							],
						],
					],
					'mapper'  => [
						'conditionals' => [ 'type' => 'mapper' ],
						'label'        => $this->trans( 'Field datasets' ),
						'nested'       => [
							'choices' => [
								'nested' => [
									'' => [
										'inline' => 'fixed',
										'fields' => [
											'source' => [
												'label'   => $this->trans( 'From Fields Dataset' ),
												'type'    => 'entity',
												'entity'  => 'dataset',
												'query'   => [ 'where' => [ 'type' => 'fields' ] ],
												'actions' => [ 'edit', 'create' ],
											],
											'target' => [
												'label'   => $this->trans( 'To Fields Dataset' ),
												'type'    => 'entity',
												'entity'  => 'dataset',
												'query'   => [ 'where' => [ 'type' => 'fields' ] ],
												'actions' => [ 'edit', 'create' ],
											],
										],
									],
								],
							],
						],
					],
					'format'  => [
						'conditionals' => [ 'type' => 'format' ],
						'label'        => $this->trans( 'Format options' ),
						'fields'       => [
							'format' => ( new Formatter() )->getFormatDecodeField(),
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
		return Dataset::class;
	}
}
