<?php

namespace SyncEngine\Model\Abstract;

use Psr\Container\ContainerInterface;
use SyncEngine\Controller\DefaultController;

abstract class AbstractModel
{
	private ContainerInterface $_container;

	public function __construct()
	{
		$this->setContainer( DefaultController::getContainer() );
	}

	public function setContainer( ContainerInterface $container ): void
	{
		$this->_container = $container;
	}

	public function getContainer(): ?ContainerInterface
	{
		return $this->_container ?? null;
	}
}
