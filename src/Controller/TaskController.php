<?php

namespace App\Controller;

abstract class TaskController extends DefaultController
{
	public $type = '';
	public $name = '';
	public $description = '';

	public function __construct() {

	}

	public function getArgs() {
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
