<?php

namespace App\Model;

use App\Entity\Connection;
use App\Model\Trait\Config;
use App\Model\Trait\Data;
use App\Model\Trait\Entity;
use App\Service\WebserviceService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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
	use Data;

	public function __construct( Connection $connection )
	{
		$this->entity = $connection;
		$this->config = $connection->getConfig();
	}

	public function getWebservice(): WebserviceModel|null
	{
		$webservice = $this->getConfig( 'webservice' );
		return WebserviceService::getWebservice( $webservice['_class'] ?? '' );
	}

	public function handleRequest( Request $request ): Response
	{
		$webservice = $this->getWebservice();
		return $webservice->handleRequest( $request );
	}

	public function authorize( array $config ): array
	{
		$config = array_merge( $this->getConfig(), $config );
		$webservice = $this->getWebservice();

		return $webservice->authorize( $config );
	}

	public function send( array $config, $data ): array
	{
		$config = $this->authorize( $config );
		$webservice = $this->getWebservice();

		return $webservice->send( $config, $data );
	}

	public function retrieve( array $config ): array
	{
		$config = $this->authorize( $config );
		$webservice = $this->getWebservice();

		return $webservice->retrieve( $config );
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
