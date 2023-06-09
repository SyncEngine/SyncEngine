<?php

namespace App\Model;

use App\Model\Trait\ModuleContext;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

abstract class WebserviceModel
{
	use ModuleContext;

	public string $type = '';
	public string $name = '';
	public string $description = '';

	protected HttpClientInterface $client;

	public function __construct()
	{
		// Construct.
	}

	public function getType(): string {
		return $this->type;
	}

	public function getName(): string {
		return $this->name;
	}

	public function getDescription(): string {
		return $this->description;
	}

	public function getClient( array $config = array() ): HttpClientInterface
	{
		if ( empty( $this->client ) ) {
			$this->setClient( HttpClient::create() );
		}

		if ( $config ) {
			$this->client->withOptions( $this->getClientOptions( $config ) );
		}

		return $this->client;
	}

	public function getClientOptions( array $config = array() ): array
	{
		return [];
	}

	public function getArgs(): array
	{
		$props = get_object_vars( $this );
		$props['slug']   = $this->getSlug();
		$props['auth']   = $this->getAuthFields();
		$props['fields'] = $this->getFields();
		if ( $this->isModuleContext() ) {
			$props['module'] = $this->getModule() ? ( new \ReflectionClass( $this->getModule() ) )->getShortName() : '';
		}
		return $props;
	}

	final public function getSlug(): string
	{
		$prefix = '';
		if ( $this->isModuleContext() ) {
			$prefix = ( new \ReflectionClass( $this->getModule() ) )->getShortName() . ':';
		}
		return $prefix . $this->getType();
	}

	abstract public function getRequestUrl( array $config ): string;

	abstract public function getAuthFields(): array;

	abstract public function getFields();

	abstract public function retrieve( array $config );

	abstract public function send( array $config, $data );

	public function setClient( $client ): void
	{
		$this->client = $client;
	}

	final static function isWebservice( $class ): bool
	{
		return $class instanceof WebserviceModel;
	}
}
