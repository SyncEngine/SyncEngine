<?php

namespace App\Controller;

abstract class TaskController extends DefaultController
{
	public $name = '';
	public $label = '';
	public $description = '';

	public function __construct() {

	}

	abstract function getFields(): array;

	abstract function execute( array $config, array $data ): array;
}
