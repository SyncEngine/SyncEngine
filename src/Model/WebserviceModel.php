<?php

namespace SyncEngine\Model;

use SyncEngine\Model\Abstract\AbstractModel;
use SyncEngine\Model\Interface\Configurable;
use SyncEngine\Model\Interface\Requestable;
use SyncEngine\Model\Interface\Taggable;
use SyncEngine\Model\Trait\Config;
use SyncEngine\Model\Trait\Format;
use SyncEngine\Model\Trait\Module;
use SyncEngine\Model\Trait\Tags;
use SyncEngine\Service\Modules;
use SyncEngine\Webservice\Helper\Result;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class WebserviceModel extends AbstractModel implements Requestable, Configurable, Taggable
{
	use Config;
	use Format;
	use Module;
	use Tags;

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
		return [
			'format' => $this->getFormatDecodeField( [], $defaults['format'] ?? [] ),
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

	/**
	 * @throws \Throwable
	 *
	 * @param  array  $config
	 *
	 * @return mixed
	 */
	abstract public function retrieve( array $config ): Result;

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
		return new Response();
	}

	public function normalize(): array
	{
		$props = [
			'_class'      => $this->getClassName(),
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

	final public static function getClassName(): string
	{
		$ref = ( new \ReflectionClass( static::class ) );

		$name      = $ref->getShortName();
		$namespace = $ref->getNamespaceName();

		if ( str_starts_with( $namespace, Modules::getRootNamespace() ) ) {
			$parts  = explode( '\\', $namespace );
			$module = $parts[1];

			return $module . ':' . $name;
		}

		return $name;
	}
}
