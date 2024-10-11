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

	public function buildModulesRegister(): void
	{
		$modulesPath = $this->getProjectDir() . '/modules';

		$vendors = array_filter(
			scandir( $modulesPath ),
			function ( $folder ) use ( $modulesPath ) {
				return is_dir( $modulesPath . '/' . $folder ) && ! in_array( $folder, [ '.', '..' ] );
			}
		);

		$register = [];
		foreach ( $vendors as $vendor ) {
			$vendorPath = $modulesPath . '/' . $vendor;
			$modules    = array_filter(
				scandir( $vendorPath ),
				function ( $folder ) use ( $vendorPath ) {
					return is_dir( $vendorPath . '/' . $folder ) && ! in_array( $folder, [ '.', '..' ] );
				}
			);

			foreach ( $modules as $moduleFolder ) {
				$filename     = 'register.php';
				$autoloadPath = $vendorPath . '/' . $moduleFolder . '/' . $filename;
				$autoloadFile = $vendor . '/' . $moduleFolder . '/' . $filename;

				if ( file_exists( $autoloadPath ) ) {
					$register[] = "include __DIR__ . '/{$autoloadFile}';";
				}
			}
		}

		$register = "<?php\n" . implode( "\n", $register );

		file_put_contents( $this->getProjectDir() . '/modules/registry.php', $register, LOCK_EX );
	}

	protected function build( ContainerBuilder $container ): void
	{
		$this->buildModulesRegister();

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
