<?php

namespace App\Controller;

abstract class TaskController extends DefaultController
{
	public $name = '';
	public $label = '';
	public $description = '';

	public function __construct() {

	}

	public function getArgs() {
		$props = get_object_vars( $this );
		$props['fields'] = $this->getFields();
		return $props;
	}

	abstract function getFields(): array;

	abstract function execute( array $config, array $data ): array;
}
