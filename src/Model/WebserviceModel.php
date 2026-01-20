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
use SyncEngine\Service\Provider\Webservices;
use SyncEngine\Webservice\Helper\Result;
use SyncEngine\Webservice\Type\WebserviceTypeInterface;

abstract class WebserviceModel extends ServiceModel implements Requestable, Configurable, Taggable
{
	use Config;
	use Format;
	use Tags;

	const SERVICE = Webservices::class;

	/**
	 * The type of webservice. Can be used for categorizing.
	 *
	 * @var string|WebserviceTypeInterface
	 */
	public string|WebserviceTypeInterface $type = '';

	/**
	 * Icon used in the interface.
	 *
	 * @var string
	 */
	public string $icon = '';

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
		return $this->type instanceof WebserviceTypeInterface ? $this->type->getType() : $this->type;
	}

	public function getIcon(): string
	{
		return $this->icon ?: $this->getModule()?->getIcon() ?? '';
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
				'label'  => 'Request',
				'nested' => $this->getRequestFields( $defaults['request'] ?? [] ),
			],
			'response' => [
				'label'  => 'Response',
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
		$connection = $config['connection'] ?? $config['id'] ?? 0;

		if ( ! $connection instanceof ConnectionModel ) {
			$connection = ConnectionModel::get( $connection );
		}

		return $connection?->getTagsResource() ?? [];
	}

	abstract public function getAuthFields(): array;

	/**
	 * All fields will be nested under '_connect';
	 * @return array|bool
	 */
	public function getConnectFields(): array|bool
	{
		return [];
	}

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
		return new Result(
			true,
			true,
			[
				'Message' => $this->trans( 'This webservice does not validate authorization.' ),
				'Config'  => $config,
			]
		);
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
			if ( $authConfig ) {
				// @todo Temp update only? This will persist the config when the connection has been tested by the ConnectionModel.
				$connection->setConfig( json_decode( $authConfig, true ), 'webservice' );
			}

			$connectConfig = (array) json_decode( $request->get( 'connectConfig' ) ?? '', true );

			// @todo provide context.
			return $connection->handleConnect( array_filter( $connectConfig ), null )->getDebugResponse();
		}

		return new Response();
	}

	public function normalize(): array
	{
		$props = [
			'_class'      => $this->getClassLocator(),
			'type'        => $this->getType(),
			'icon'        => $this->getIcon(),
			'name'        => $this->getName(),
			'description' => $this->getDescription(),
			'fields'      => [
				'retrieve' => $this->getRetrieveFields(),
				'send'     => $this->getSendFields(),
				'auth'     => $this->getAuthFields(),
				'connect'  => $this->getConnectFields(),
			],
			'tags'        => [
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
