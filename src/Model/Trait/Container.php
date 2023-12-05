<?php

namespace App\Model\Trait;

use Psr\Container\ContainerInterface;

trait Container
{
	protected ContainerInterface $container;

	public function setContainer( ContainerInterface $container ): void
	{
		$this->container = $container;
	}

	public function getContainer(): ?ContainerInterface
	{
		return $this->container ?? null;
	}
}
