<?php

namespace SyncEngine\Model\Trait;

use Psr\Container\ContainerInterface;

trait Container
{
	private ContainerInterface $_container;

	public function setContainer( ContainerInterface $container ): void
	{
		$this->_container = $container;
	}

	public function getContainer(): ?ContainerInterface
	{
		return $this->_container ?? null;
	}
}
