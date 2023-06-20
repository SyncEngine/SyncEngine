<?php

namespace App\Model;

use App\Model\Trait\Format;
use App\Model\Trait\Http;
use App\Model\Trait\ModuleContext;
use Symfony\Contracts\HttpClient\HttpClientInterface;

abstract class WebserviceModel
{
	use ModuleContext;
	use Format;
	use Http;

	private string $_name = '';

	public string $type = '';
	public string $name = '';
	public string $description = '';

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

	public function getArgs(): array
	{
		$props = get_object_vars( $this );
		$props['_name']  = $this->getInternalName();
		$props['auth']   = $this->getAuthFields();
		$props['fields'] = $this->getFields();
		if ( $this->isModuleContext() ) {
			$props['module'] = $this->getModule()->getName();
		}
		return $props;
	}

	public function getFields(): array
	{
		return array_merge( $this->getHttpFields(), $this->getFormatFields() );
	}

	abstract public function getAuthFields(): array;

	abstract public function getRequestUrl( array $config ): string;

	abstract public function retrieve( array $config );

	abstract public function send( array $config, $data );

	final static function isWebservice( $class ): bool
	{
		return $class instanceof WebserviceModel;
	}

	final public function getInternalName(): string
	{
		if ( ! $this->_name ) {
			$this->parseClassName();
		}

		$prefix = '';
		if ( $this->isModuleContext() ) {
			$prefix = $this->getModule()->getName() . ':';
		}
		return $prefix . $this->_name;
	}

	private function parseClassName(): void
	{
		$pos = strrpos(static::class, '\\');
		$this->_name = false === $pos ? static::class : substr(static::class, $pos + 1);
	}
}
