<?php

namespace App\Model;

use App\Model\Trait\ModuleContext;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

abstract class WebserviceModel
{
	use ModuleContext;

	public $type = '';
	public $name = '';
	public $description = '';

	protected $client = null;

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

	public function getClient(): HttpClientInterface
	{
		if ( null === $this->client ) {
			$this->setClient( HttpClient::create() );
		}
		return $this->client;
	}

	public function getArgs(): array
	{
		$props = get_object_vars( $this );
		$props['slug']   = $this->getSlug();
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

	abstract public function getAuthFields();

	abstract public function getFields();

	abstract public function retrieve();

	abstract public function send();

	public function setClient( $client )
	{
		$this->client = $client;
	}

	final static function isWebservice( $class ): bool
	{
		return $class instanceof WebserviceModel;
	}
}
