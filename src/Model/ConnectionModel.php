<?php

namespace App\Model;

use App\Entity\Connection;
use App\Model\Abstract\EntityModel;
use App\Model\Interface\Taggable;
use App\Model\Trait\Config;
use App\Model\Trait\Data;
use App\Model\Trait\Ref;
use App\Model\Trait\Tags;
use App\Service\Webservices;
use App\Webservice\Helper\Result;
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
class ConnectionModel extends EntityModel implements Taggable
{
	use Ref;
	use Config;
	use Data;
	use Tags;

	public function __construct( Connection $connection )
	{
		parent::__construct( $connection );
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
		$config     = array_merge( $this->getConfig( 'webservice' ), $config, [ 'connection' => $this ] );
		$webservice = $this->getWebservice();

		return $webservice->authorize( $config );
	}

	public function handleSend( array $config, $data ): Result
	{
		$config     = $this->handleAuthorization( $config );
		$webservice = $this->getWebservice();

		return $webservice->send( $config, $data );
	}

	public function handleRetrieve( array $config ): Result
	{
		$config     = $this->handleAuthorization( $config );
		$webservice = $this->getWebservice();

		return $webservice->retrieve( $config );
	}

	public function getWebservice(): WebserviceModel|null
	{
		$webservice = $this->getConfig( 'webservice' );
		if ( $webservice['_class'] ?? false ) {
			return $this->getContainer()->get('Webservices')->getWebservice( $webservice['_class'] );
		}

		return null;
	}

	public function getFields(): array
	{
		// @todo Implement fields.
		return [
			'webservice' => [
				'required' => true,
				'wrap'     => false,
				'label'    => 'Webservice',
				'type'     => 'authentication',
			],
		];
	}

	public static function getEntityClass(): string
	{
		return Connection::class;
	}
}
