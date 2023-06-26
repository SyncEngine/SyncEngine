<?php

namespace App\Model;

use App\Entity\Connection;
use App\Model\Trait\Config;
use App\Model\Trait\Entity;
use App\Service\WebserviceService;

/**
 * @method int getId()
 * @method setId( int $id )
 * @method string getName()
 * @method setName( string $name )
 * @method string getDescription()
 * @method setDescription( string $description )
 */
class ConnectionModel
{
	use Entity;
	use Config;

	public function __construct( Connection $connection )
	{
		$this->entity = $connection;
		$this->config = $connection->getConfig();
	}

	public function getWebservice(): WebserviceModel|null {
		$webservice = $this->getConfig( 'webservice' );
		return WebserviceService::getWebservice( $webservice['_class'] ?? '' );
	}

	public static function getFields(): array
	{
		// @todo Implement fields.
		return [
			'webservice' => [
				'label' => 'Webservice',
				'type' => 'webservice',
			]
		];
	}
}
