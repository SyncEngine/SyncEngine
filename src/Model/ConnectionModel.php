<?php

namespace SyncEngine\Model;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use SyncEngine\Entity\Connection;
use SyncEngine\Model\Abstract\EngineModel;
use SyncEngine\Model\Interface\Taggable;
use SyncEngine\Model\Trait\Data;
use SyncEngine\Model\Trait\Tags;
use SyncEngine\Service\ExecutionContext;
use SyncEngine\Service\Tag\TagParser;
use SyncEngine\Webservice\Helper\Result;

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

	public function handleAuthorization( array $config, ExecutionContext $context ): array
	{
		$config = array_merge( $this->getConfig( 'webservice' ), $config );

		$config = ( new TagParser( $this->getTagsResource( $config, $context ), false, true, false ) )->parseTagArray( $config );

		$config['connection'] = $this;

		return $this->getWebservice()->authorize( $config );
	}

	public function handleSend( array $config, ExecutionContext $context, $data ): Result
	{
		$config     = $this->handleAuthorization( $config, $context );
		$webservice = $this->getWebservice();

		return $webservice->send( $config, $data );
	}

	public function handleRetrieve( array $config, ExecutionContext $context, $data = null ): Result
	{
		$config     = $this->handleAuthorization( $config, $context );
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

	public function getTags(): array
	{
		return [
			'vault' => '',
		];
	}

	public function getTagsResource( $config = [], ?ExecutionContext $context = null ): array
	{
		return [
			'vault' => $context ? $context->getExecuteService()->vault()->get() : [],
		];
	}

	public static function getEntityClass(): string
	{
		return Connection::class;
	}
}
