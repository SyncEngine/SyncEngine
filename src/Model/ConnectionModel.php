<?php

namespace SyncEngine\Model;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use SyncEngine\Entity\Connection;
use SyncEngine\Model\Abstract\EngineModel;
use SyncEngine\Model\Interface\Taggable;
use SyncEngine\Model\Trait\Tags;
use SyncEngine\Service\ExecuteContext;
use SyncEngine\Service\Tag\TagParser;
use SyncEngine\Webservice\Helper\Result;

/**
 * @extends EngineModel<Connection>
 *
 * @method int getId()
 * @method setId( int $id )
 * @method string getName()
 * @method setName( string $name )
 * @method string getDescription()
 * @method setDescription( string $description )
 */
class ConnectionModel extends EngineModel implements Taggable
{
	use Tags;

	public function __construct( ?Connection $connection = null )
	{
		parent::__construct( $connection );
	}

	public function isConnected(): ?bool
	{
		return $this->getData( 'connected' );
	}

	public function setConnected( bool $connected, $flush = true ): void
	{
		if ( $connected === $this->isConnected() ) {
			return;
		}

		$this->setData( $connected, 'connected' );

		if ( $this->hasEntity() ) {
			$this->persist( $flush );
		}
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

	public function handleAuthorization( array $config, ?ExecuteContext $context ): array
	{
		$config = array_merge( $this->getConfig( 'webservice' ), $config );

		$config = ( new TagParser( $this->getTagsResource( $config, $context ), false, true, false ) )->parseTagArray( $config );

		$config['connection'] = $this;

		try {
			$config = $this->getWebservice()->authorize( $config );
			$this->setConnected( true );
		} catch ( \Exception $e ) {
			$this->setConnected( false );
			throw $e;
		}

		return $config;
	}

	public function handleConnect( array $config, ?ExecuteContext $context ): Result
	{
		$config     = $this->handleAuthorization( $config, $context );
		$webservice = $this->getWebservice();

		$result = $webservice->connect( $config );

		$this->setConnected( $result->isSuccessful() );

		return $result;
	}

	public function handleSend( array $config, ExecuteContext $context, $data ): Result
	{
		$config     = $this->handleAuthorization( $config, $context );
		$webservice = $this->getWebservice();

		return $webservice->send( $config, $data );
	}

	public function handleRetrieve( array $config, ExecuteContext $context, $data = null ): Result
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
			'vault' => '_input',
		];
	}

	public function getTagsResource( $config = [], ?ExecuteContext $context = null ): array
	{
		$vault = [];
		if ( $context ) {
			$vault = $context->getExecuteService()->vault()->get();
		} elseif ( $this->getContainer()->has( 'Vault' ) ) {
			$vault = $this->getContainer()->get( 'Vault' )?->get();
		}
		return [
			'vault' => $vault,
		];
	}

	public static function getEntityClass(): string
	{
		return Connection::class;
	}
}
