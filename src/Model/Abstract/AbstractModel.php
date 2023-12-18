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

	public function getClassName(): string
	{
		return ( new \ReflectionClass( $this ) )->getShortName();
	}

	public function getModelName(): string
	{
		// Remove "Model" from class short name.
		return substr( $this->getClassName(), 0, -5 );
	}

	public static function getModelClassName( $name ): string
	{
		return ucfirst( $name ) . 'Model';
	}

	public static function getModelClass( $name ): ?string
	{
		$name = __NAMESPACE__ . '\\' . self::getModelClass( $name );
		if ( class_exists( $name ) ) {
			return $name;
		}
		return null;
	}
}
