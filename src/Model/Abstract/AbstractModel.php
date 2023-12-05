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

	protected function trans( ?string $id, array $parameters = [], string $domain = null, string $locale = null ): string {
		return $this->getContainer()->get('translator')->trans( $id, $parameters, $domain, $locale );
	}
}
