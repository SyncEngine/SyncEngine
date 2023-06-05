<?php

namespace App\Model\Trait;

trait Task
{
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
		$props['fields'] = $this->getFields();
		return $props;
	}

	abstract function getFields(): array;

	abstract function execute( array $config, array $data ): array;
}
