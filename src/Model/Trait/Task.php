<?php

namespace App\Model\Trait;

use App\Model\ModuleModel;
use App\Service\ModuleService;

trait Task
{
	use ModuleContext;

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
		$props['slug']   = $this->getSlug();
		$props['fields'] = $this->getFields();
		if ( $this->isFromModule() ) {
			$props['module'] = $this->getModule() ? ( new \ReflectionClass( $this->getModule() ) )->getShortName() : '';
		}
		return $props;
	}

	final public function getSlug(): string
	{
		$prefix = '';
		if ( $this->isFromModule() ) {
			$prefix = ( new \ReflectionClass( $this->getModule() ) )->getShortName() . ':';
		}
		return $prefix . $this->getType();
	}

	abstract function getFields(): array;

	abstract function execute( array $config, array $data ): array;
}
