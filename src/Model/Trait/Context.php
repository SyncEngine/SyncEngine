<?php

namespace App\Model\Trait;

use App\Model\ModuleModel;
use App\Service\ModuleService;

trait Context
{
	private array $context = [];

	public function setContext( array $context, string $key ): void
	{
		$this->context[ $key ] = $context;
	}

	public function getContext( string $key = null ): mixed
	{
		if ( $key ) {
			return $this->context[ $key ] ?? null;
		}
		return $this->context;
	}
}
