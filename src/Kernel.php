<?php

namespace SyncEngine;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use SyncEngine\Attribute\MenuItem;

class Kernel extends BaseKernel
{
	use MicroKernelTrait;

	protected function build( ContainerBuilder $container ): void
	{
		parent::build( $container );

		$container->registerAttributeForAutoconfiguration(MenuItem::class, static function (
			ChildDefinition $definition,
			MenuItem $attribute,
			\ReflectionClass|\ReflectionMethod $reflector
		): void {
			$args = [];
			if ( $reflector instanceof \ReflectionMethod ) {
				$args['method'] = $reflector->getName();
			}
			$definition->addTag( 'syncengine.attribute.menuitem', $args );
		});
	}
}
