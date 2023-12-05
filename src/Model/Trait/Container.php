<?php

namespace App\Model\Trait;

trait Container
{
	protected object $container;

	public function setContainer( $container )
	{
		$this->container = $container;
	}

	public function getContainer()
	{
		return $this->container ?? null;
	}
}
