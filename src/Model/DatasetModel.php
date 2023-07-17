<?php

namespace App\Model;

use App\Entity\Dataset;
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
class DatasetModel implements Exportable
{
	use Entity;
	use Config;
	use Data;
	use Ref;

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
}
