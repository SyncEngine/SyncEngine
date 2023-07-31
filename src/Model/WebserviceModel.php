<?php

namespace App\Model;

use App\Model\Trait\Format;
use App\Model\Trait\Module;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class WebserviceModel
{
	use Module;
	use Format;

	private string $_class = '';

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

	public function getFields(): array
	{
		return [
			'request'  => [
				//'label'  => 'Request',
				'nested' => $this->getRequestFields(),
			],
			'response' => [
				//'label'  => 'Response',
				'nested' => [
					'format' => $this->getFormatDecodeField(),
				],
			],
		];
	}

	abstract public function getAuthFields(): array;

	abstract public function getRequestFields( $defaults = [] ): array;

	abstract public function getRequestUrl( array $config ): string;

	public function authorize( array $config ): array
	{
		return $config;
	}

	abstract public function retrieve( array $config );

	abstract public function send( array $config, $data );

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
			'fields'      => $this->getFields(),
			'auth'        => $this->getAuthFields(),
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

	final public function getClassName(): string
	{
		if ( ! $this->_class ) {
			$this->parseClassName();
		}

		$prefix = '';
		if ( $this->isFromModule() ) {
			$prefix = $this->getModule()->getName() . ':';
		}

		return $prefix . $this->_class;
	}

	private function parseClassName(): void
	{
		$pos          = strrpos( static::class, '\\' );
		$this->_class = false === $pos ? static::class : substr( static::class, $pos + 1 );
	}
}
