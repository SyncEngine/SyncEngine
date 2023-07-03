<?php

namespace App\Model;

use App\Entity\Dataset;
use App\Model\Trait\Config;
use App\Model\Trait\Entity;
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
 * @method array getData()
 * @method setData( array $flow )
 */
class DatasetModel
{
	use Entity;
	use Config;

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
				'label' => 'Columns',
				'type' => 'params',
				'columns' => [
					'key' => 'Key',
					'name' => 'Name',
				]
			]
		];
	}
}
