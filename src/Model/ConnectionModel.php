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
		$this->data   = $connection->getData();
	}

	public function getWebservice(): WebserviceModel|null
	{
		$webservice = $this->getConfig( 'webservice' );
		if ( $webservice['_class'] ?? false ) {
			return WebserviceService::getWebservice( $webservice['_class'] );
		}
		return null;
	}

	public function handleRequest( Request $request ): Response
	{
		$config = $request->get( 'config' );
		if ( $config ) {
			$this->setConfig( json_decode( $config, true ) );
		}

		$webservice = $this->getWebservice();
		if ( ! $webservice ) {
			return new Response();
		}
		return $webservice->handleRequest( $request, $this );
	}

	public function handleAuthorization( array $config ): array
	{
		$config = array_merge( $this->getConfig( 'webservice' ), $config );
		$webservice = $this->getWebservice();

		return $webservice->authorize( $config );
	}

	public function handleSend( array $config, $data )
	{
		$config = $this->handleAuthorization( $config );
		$webservice = $this->getWebservice();

		return $webservice->send( $config, $data );
	}

	public function handleRetrieve( array $config )
	{
		$config = $this->handleAuthorization( $config );
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
