<?php

namespace App\Controller;

abstract class ModuleController extends DefaultController
{
	public $name = '';
	public $label = '';
	public $descrioption = '';
	public $author = '';
	public $version = '';

	public function __construct() {

	}

	abstract function executeTask( array $config, array $data ): array;
}
