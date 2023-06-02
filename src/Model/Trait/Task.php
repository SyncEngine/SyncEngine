<?php

namespace App\Model\Trait;

trait Task
{
	public function getType() {
		return $this->type ?? '';
	}

	public function getName() {
		return $this->name ?? '';
	}

	public function getDescription() {
		return $this->description ?? '';
	}

	public function getArgs()
	{
		$props = get_object_vars( $this );
		$props['fields'] = $this->getFields();
		return $props;
	}

	abstract function getFields(): array;

	abstract function execute( array $config, array $data ): array;
}
