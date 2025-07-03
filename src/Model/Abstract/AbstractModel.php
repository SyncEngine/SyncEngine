<?php

namespace SyncEngine\Model\Abstract;

use Psr\Container\ContainerInterface;
use SyncEngine\Controller\DefaultController;
use SyncEngine\Model\Interface\Normalizable;

abstract class AbstractModel implements Normalizable
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

	public function getParameter( string $name, string $prefix = 'syncengine' ): mixed
	{
		if ( $prefix ) {
			$name = $prefix . '.' . $name;
		}
		return $this->getContainer()->get('parameter_bag')->get( $name );
	}

	protected function trans( ?string $id, array $parameters = [], ?string $domain = null, ?string $locale = null ): string {
		return $this->getContainer()->get('translator')->trans( $id, $parameters, $domain, $locale );
	}

	public static function getClassName(): string
	{
		return ( new \ReflectionClass( static::class ) )->getShortName();
	}

	public static function getModelName( string $name = '' ): string
	{
		if ( ! $name ) {
			$name = static::getClassName();
		}
		if ( ! str_ends_with( $name, 'Model' ) ) {
			return $name;
		}
		// Remove "Model" from class short name.
		return substr( $name, 0, -5 );
	}

	public static function getModelClassName( string $name = '' ): string
	{
		if ( ! $name ) {
			$name = static::getModelName();
		}
		return ucfirst( $name ) . 'Model';
	}

	public static function getModelClass( string $name ): ?string
	{
		// @todo Cache models within a container instead of hardcoding the namespace.
		$namespace = substr( __NAMESPACE__, 0, -8 );
		$name = $namespace . self::getModelClassName( $name );
		if ( class_exists( $name ) ) {
			return $name;
		}
		return null;
	}
}
