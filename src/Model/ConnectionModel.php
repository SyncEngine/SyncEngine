<?php

namespace SyncEngine\Model;

use SyncEngine\Entity\Connection;
use SyncEngine\Model\Abstract\EngineModel;
use SyncEngine\Model\Interface\Taggable;
use SyncEngine\Model\Trait\Data;
use SyncEngine\Model\Trait\Tags;
use SyncEngine\Webservice\Helper\Result;
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
class ConnectionModel extends EngineModel implements Taggable
{
	use Data;
	use Tags;

	public function __construct( ?Connection $connection = null )
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

	public function handleRetrieve( array $config, $data = null ): Result
	{
		$config     = $this->handleAuthorization( $config );
		$webservice = $this->getWebservice();

		return $webservice->retrieve( $config, $data );
	}

	public function getWebservice(): WebserviceModel|null
	{
		$webservice = $this->getConfig( 'webservice' );
		if ( $webservice['_class'] ?? false ) {
			return WebserviceModel::get( $webservice['_class'] );
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
				'label'    => $this->trans( 'Webservice' ),
				'type'     => 'authentication',
			],
		];
	}

	public static function getEntityClass(): string
	{
		return Connection::class;
	}
}
