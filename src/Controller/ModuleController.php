<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

abstract class ModuleController extends AbstractController
{
	public $name = '';
	public $label = '';
	public $descrioption = '';
	public $author = '';
	public $version = '';

	public function __construct() {

	}

	abstract function executeStep();
}
