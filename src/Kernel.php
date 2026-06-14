<?php

namespace SyncEngine;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use SyncEngine\Attribute\MenuItem;
use SyncEngine\Framework\ModuleRegistryManager;

class Kernel extends BaseKernel
{
	use MicroKernelTrait;

	const VERSION = '0.1.0-beta.2';

	private function refreshModuleRegistry(): void
	{
		$manager = new ModuleRegistryManager( $this->getProjectDir(), self::VERSION );
		$manager->refreshRegistry();
	}

	protected function initializeContainer(): void
	{
		parent::initializeContainer();
	}


	protected function build( ContainerBuilder $container ): void
	{
		$this->refreshModuleRegistry();

		parent::build( $container );

		$container->registerAttributeForAutoconfiguration(
			MenuItem::class,
			static function (
				ChildDefinition $definition, MenuItem $attribute, \ReflectionClass|\ReflectionMethod $reflector
			): void {
				$args = [];
				if ( $reflector instanceof \ReflectionMethod ) {
					$args['method'] = $reflector->getName();
				}
				$definition->addTag( 'syncengine.attribute.menuitem', $args );
			}
		);
	}
}
