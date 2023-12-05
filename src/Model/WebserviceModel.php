<?php

namespace App\Model;

use App\Model\Interface\Configurable;
use App\Model\Interface\Requestable;
use App\Model\Interface\Taggable;
use App\Model\Trait\Config;
use App\Model\Trait\Format;
use App\Model\Trait\Module;
use App\Model\Trait\Tags;
use App\Service\Modules;
use App\Webservice\Helper\Result;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class WebserviceModel implements Requestable, Configurable, Taggable
{
	use Config;
	use Format;
	use Module;
	use Tags;

	private static string $_class = '';
	private static string $_namespace = '';

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

	public function __construct()
	{
		// Construct.
	}

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
		if ( ! self::$_class ) {
			self::parseClassName();
		}

		if ( str_starts_with( self::$_namespace, Modules::getRootNamespace() ) ) {
			$parts  = explode( '\\', self::$_namespace );
			$module = $parts[1];

			return $module . ':' . self::$_class;
		}

		return self::$_class;
	}

	final protected static function parseClassName(): void
	{
		$pos = strrpos(static::class, '\\');
		self::$_namespace = false === $pos ? '' : substr(static::class, 0, $pos);
		self::$_class ??= false === $pos ? static::class : substr(static::class, $pos + 1);
	}
}
