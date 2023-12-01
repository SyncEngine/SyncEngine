<?php

namespace App\Webservice\Helper;

use Symfony\Component\HttpFoundation\Response;

class Result extends Response
{
	public array $info = [];

	public function __construct( ?string $content = '', int $status = 200, array $headers = [], array $info = [] ) {
		parent::__construct( $content, $status, $headers );
		$this->info = $info;
	}

	public function getInfo(): array
	{
		return $this->info;
	}

	public function getHeaders(): array
	{
		return $this->headers->all();
	}
}
