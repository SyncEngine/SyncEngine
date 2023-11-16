<?php

namespace App\Model;

use App\Entity\Dataset;
use App\Model\Interface\Configurable;
use App\Model\Interface\Exportable;
use App\Model\Interface\Persistable;
use App\Model\Interface\Taggable;
use App\Model\Trait\Config;
use App\Model\Trait\Data;
use App\Model\Trait\Entity;
use App\Model\Trait\Ref;
use App\Model\Trait\Tags;
use App\Service\Formatter;
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
class DatasetModel implements Exportable, Configurable, Persistable, Taggable
{
	use Entity;
	use Ref;
	use Config;
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

	public function __construct( Dataset $dataset )
	{
		$this->entity = $dataset;
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

	public function getData( $key = null, $default = null ): mixed
	{
		$type = $this->getType();

		if ( 'format' === $type ) {
			return $this->getDataDefault( 'value', $default );
		}

		return $this->getDataDefault( $key, $default );
	}

	public function getDataAsMap( string $sourceKey = '', string $targetKey = '' ): array
	{
		// Find column names.
		if ( ! $sourceKey || ! $targetKey ) {
			if ( 'mapper' === $this->getType() ) {
				$sourceKey = $sourceKey ?: 'source';
				$targetKey = $targetKey ?: 'target';
			} else {
				$columns = $this->getColumns();

				if ( ! $sourceKey && ! empty( $columns[0]['key'] ) ) {
					$sourceKey = $columns[0]['key'];
				}
				if ( ! $sourceKey && ! empty( $columns[1]['key'] ) ) {
					$targetKey = $columns[1]['key'];
				}
			}
		}

		$data = [];

		foreach ( $this->getData() as $value ) {
			$left  = $value[ $sourceKey ] ?? '';
			$right = $value[ $targetKey ] ?? '';

			$data[ $left ] = $right;
		}

		return $data;
	}

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

	public function getDataKeys(): array
	{
		switch ( $this->getType() ) {
			case 'formatted':
				return [];
			case 'mapper':
				return array_keys( $this->getDataAsMap() );
			case 'entities':
			default:
				return $this->getColumns( 'key' ) ?: array_keys( $this->getData() );
		}
	}

	public function getDataRootKeys(): array
	{
		$keys = $this->getDataKeys();

		foreach ( $keys as &$key ) {
			$key = explode( '.', $key )[0];
		}

		return $keys;
	}

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
				'label'   => 'Data type',
				'type'    => 'select',
				'default' => '',
				'choices' => array_flip( self::$_TYPES ),
				'fields'  => [
					'columns' => [
						'conditionals' => [ 'type' => [ '', 'entities' ] ],
						'label'        => 'Columns',
						'type'         => 'columns',
						'name'         => 'columns',
						'columns'      => [
							'key'  => 'Key',
							'name' => 'Name',
						],
					],
					'fields'  => [
						'conditionals' => [ 'type' => 'fields' ],
						'nested'       => [
							'configuration' => [
								'label'   => 'Configuration',
								'type'    => 'select',
								'choices' => [
									''         => 'Simple',
									//'advanced' => 'Advanced', @todo Field config.
								],
							],
						],
					],
					'mapper'  => [
						'conditionals' => [ 'type' => 'mapper' ],
						'label'        => 'Field datasets',
						'nested'       => [
							'choices' => [
								'nested' => [
									'' => [
										'inline' => 'columns',
										'fields' => [
											'source' => [
												'label'   => 'From Fields Dataset',
												'type'    => 'entity',
												'entity'  => 'dataset',
												'query'   => [ 'where' => [ 'type' => 'fields' ] ],
												'actions' => [ 'edit', 'create' ],
											],
											'target' => [
												'label'   => 'To Fields Dataset',
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
						'label'        => 'Format options',
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
