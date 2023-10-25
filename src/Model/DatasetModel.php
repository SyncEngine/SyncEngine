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

	public function getFields(): array
	{
		return [
			'columns'    => [
				'label'   => 'Columns',
				'type'    => 'columns',
				'columns' => [
					'key'  => 'Key',
					'name' => 'Name',
				],
			],
			// @todo only for format type => Move dataset format type to config.
			'formatWrap' => [
				'label'        => 'Format options',
				'conditionals' => [ '.type' => 'format' ],
				'fields'       => [
					'format' => ( new Formatter() )->getFormatDecodeField(),
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

	public function getData( $key = null, $default = null ): mixed
	{
		$type = $this->getType();

		if ( 'format' === $type ) {
			return $this->getDataDefault( 'value', $default );
		}

		return $this->getDataDefault( $key, $default );
	}

	public function getDataAsMap( $leftKey = '', $rightKey = '' ): array
	{
		$data = $this->getData();

		// Find column names.
		if ( ! $leftKey || ! $rightKey ) {
			$columns = $this->getConfig( 'columns' );

			if ( ! $leftKey && ! empty( $columns[0]['key'] ) ) {
				$leftKey = $columns[0]['key'];
			}
			if ( ! $leftKey && ! empty( $columns[1]['key'] ) ) {
				$rightKey = $columns[1]['key'];
			}
		}

		$return = [];

		foreach ( $data as $key => $value ) {
			$left  = $value[ $leftKey ] ?? '';
			$right = $value[ $rightKey ] ?? '';

			$return[ $left ] = $right;
		}

		return $return;
	}

	public static function getEntityClass(): string
	{
		return Dataset::class;
	}
}
