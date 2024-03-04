<?php

namespace SyncEngine\Model;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use SyncEngine\Model\Abstract\ServiceModel;
use SyncEngine\Model\Interface\Configurable;
use SyncEngine\Model\Interface\Requestable;
use SyncEngine\Model\Interface\Taggable;
use SyncEngine\Model\Trait\Config;
use SyncEngine\Model\Trait\Format;
use SyncEngine\Model\Trait\Tags;
use SyncEngine\Webservice\Helper\Result;

abstract class WebserviceModel extends ServiceModel implements Requestable, Configurable, Taggable
{
	use Config;
	use Format;
	use Tags;

	const SERVICE = 'Webservices';

	/**
	 * The type of webservice, can be used for categorizing.
	 *
	 * @var string
	 */
	public string $type = '';

	/**
	 * Human-readable name used in the interface.
	 *
	 * @var string
	 */
	public string $name = '';

	/**
	 * Human-readable description used in the interface.
	 *
	 * @var string
	 */
	public string $description = '';

	public function getType(): string
	{
		return $this->type;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getDescription(): string
	{
		return $this->description;
	}

	public function getFields( array $defaults = [] ): array
	{
		return [
			'request'  => [
				//'label'  => 'Request',
				'nested' => $this->getRequestFields( $defaults['request'] ?? [] ),
			],
			'response' => [
				//'label'  => 'Response',
				'nested' => $this->getResponseFields( $defaults['response'] ?? [] ),
			],
		];
	}

	public function getAuthTags(): array
	{
		return [];
	}

	public function getAuthTagsResource( array $config ): array
	{
		return [];
	}

	abstract public function getAuthFields(): array;

	public function getRetrieveFields( array $defaults = [] ): array
	{
		return $this->getFields( $defaults );
	}

	public function getSendFields( array $defaults = [] ): array
	{
		return $this->getFields( $defaults );
	}

	abstract public function getRequestFields( array $defaults = [] ): array;

	public function getResponseFields( array $defaults = [] ): array
	{
		$field = $this->getFormatDecodeField( [], $defaults['format'] ?? [] );
		$field['label'] = $this->trans( 'Response format' );

		return [
			'format' => $field,
		];
	}

	abstract public function getRequestUrl( array $config ): string;

	/**
	 * @throws \Throwable
	 *
	 * @param  array  $config
	 *
	 * @return array
	 */
	public function authorize( array $config ): array
	{
		return $config;
	}

	public function connect( array $config ): Result
	{
		return new Result( true, true, [ 'Message' => 'This webservice does not verify authorization data.', 'Config' => $config ] );
	}

	/**
	 * @throws \Throwable
	 *
	 * @param  array  $config
	 *
	 * @return mixed
	 */
	abstract public function retrieve( array $config, $data = null ): Result;

	/**
	 * @throws \Throwable
	 *
	 * @param         $data
	 * @param  array  $config
	 *
	 * @return mixed
	 */
	abstract public function send( array $config, $data ): Result;

	public function handleRequest( Request $request, ConnectionModel $connection ): Response
	{
		if ( 'connect' === $request->get( 'action' ) ) {
			$authConfig = $request->get( 'authConfig' );
			$connectConfig = $request->get( 'connectConfig' );
			if ( $authConfig ) {
				$connection->setConfig( json_decode( $authConfig, true ), 'webservice' );
			}
			// @todo Vault?
			$config = $connection->handleAuthorization( json_decode( $connectConfig ?? '', true ), null );

			return $this->connect( $config )->getDebugResponse();
		}

		return new Response();
	}

	public function normalize(): array
	{
		$props = [
			'_class'      => $this->getClassLocator(),
			'type'        => $this->getType(),
			'name'        => $this->getName(),
			'description' => $this->getDescription(),
			'fields'      => [
				'retrieve' => $this->getRetrieveFields(),
				'send'     => $this->getSendFields(),
				'auth'     => $this->getAuthFields(),
			],
			'tags' => [
				'auth' => $this->getAuthTags(),
			],
		];

		if ( $this->isFromModule() ) {
			$props['module'] = $this->getModule()->getName();
		}

		return $props;
	}

	final static function isWebservice( $class ): bool
	{
		return $class instanceof WebserviceModel;
	}
}
