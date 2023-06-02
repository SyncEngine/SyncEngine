<?php

namespace App\Model\Trait;

trait Task
{
	public $type = '';
	public $name = '';
	public $description = '';

	public function getArgs()
	{
		$props = get_object_vars( $this );
		$props['fields'] = $this->getFields();
		return $props;
	}

	public function getType() {
		return $this->type;
	}

	abstract function getFields(): array;

	abstract function execute( array $config, array $data ): array;
}
