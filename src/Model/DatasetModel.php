<?php

namespace App\Model;

use App\Entity\Dataset;
use App\Model\Interface\Configurable;
use App\Model\Interface\Exportable;
use App\Model\Trait\Config;
use App\Model\Trait\Data;
use App\Model\Trait\Entity;
use App\Model\Trait\Ref;
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
 * @method setType( string $endpoint )
 */
class DatasetModel implements Exportable, Configurable
{
	use Entity;
	use Ref;
	use Config;
	use Data;

	const ENTITY = Dataset::class;

	public function __construct( Dataset $dataset )
	{
		$this->entity = $dataset;
		$this->config = $dataset->getConfig();
	}

	public function handleRequest( Request $request ): Response
	{
		return new Response();
	}

	public static function getFields(): array
	{
		return [
			'columns' => [
				'label'   => 'Columns',
				'type'    => 'columns',
				'columns' => [
					'key'  => 'Key',
					'name' => 'Name',
				],
			],
		];
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
			$left = $value[ $leftKey ] ?? '';
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
