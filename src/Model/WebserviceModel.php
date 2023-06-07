<?php

namespace App\Model;

use App\Model\Trait\ModuleContext;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

abstract class WebserviceModel
{
	use ModuleContext;

	public $type = '';
	public $name = '';
	public $description = '';
	public $client = null;

	public function __construct()
	{
		// Construct.
	}

	public function getClient(): HttpClientInterface
	{
		if ( null === $this->client ) {
			$this->client = HttpClient::create();
		}
		return $this->client;
	}

	abstract public function getAuthFields();

	abstract public function getFields();

	abstract public function retrieve();

	abstract public function send();

	final static function isWebservice( $class ): bool
	{
		return $class instanceof WebserviceModel;
	}
}
