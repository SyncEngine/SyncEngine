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

	const VERSION = '1.0.0-beta';

	private $serviceTemplate = '
    SyncEngine\Module\%vendor%\%module%\:
        resource: "%kernel.project_dir%/modules/%vendor%/%module%/src"
        lazy: false';

	public function generateRegistry()
	{
		$modulesPath = $this->getProjectDir() . '/modules';
		$vendors     = $this->getDirectories( $modulesPath );
		$register    = [];
		$services    = [];

		foreach ( $vendors as $vendor ) {
			$vendorPath = $modulesPath . '/' . $vendor;
			$modules    = $this->getDirectories( $vendorPath );

			foreach ( $modules as $module ) {
				$autoloadFilePath = $vendorPath . '/' . $module . '/register.php';

				if ( file_exists( $autoloadFilePath ) ) {
					$register[] = "include __DIR__ . '/" . $vendor . '/' . $module . "/register.php';";
				}

				if ( is_dir( $vendorPath . '/' . $module . '/src' ) ) {
					$services[] = $this->generateServiceEntry( $vendor, $module );
				}
			}
		}

		$this->writeRegistryFile( $register );
		$this->writeServicesFile( $services );
	}

	private function getDirectories( string $path ): array
	{
		return array_filter(
			scandir( $path ),
			function ( $folder ) use ( $path ) {
				return is_dir( $path . '/' . $folder ) && ! in_array( $folder, [ '.', '..' ] );
			}
		);
	}

	private function generateServiceEntry( string $vendor, string $module ): string
	{
		return str_replace(
			[ '%vendor%', '%module%' ],
			[ $vendor, $module ],
			$this->serviceTemplate
		);
	}

	private function writeRegistryFile( array $register )
	{
		$content = "<?php\n" . implode( "\n", $register );
		$this->safeFilePutContents( $this->getProjectDir() . '/modules/registry.php', $content );
	}

	private function writeServicesFile( array $services )
	{
		$content = '
services:
' . implode( "\n", $services ) . '

    _defaults:
        autowire: true
        autoconfigure: true

    # Autoconfigure service tags based on instances.
    _instanceof:
        SyncEngine\Model\Abstract\EntityModel:
            tags: [ \'syncengine.model.entity\' ]
        SyncEngine\Model\ModuleModel:
            tags: [ \'syncengine.model.module\' ]
        SyncEngine\Model\BlueprintModel:
            tags: [ \'syncengine.model.blueprint\' ]
        SyncEngine\Model\ColumnModel:
            tags: [ \'syncengine.model.column\' ]
        SyncEngine\Model\CodecModel:
            tags: [ \'syncengine.model.codec\' ]
        SyncEngine\Model\TaskModel:
            tags: [ \'syncengine.model.task\' ]
        SyncEngine\Model\WebserviceModel:
            tags: [ \'syncengine.model.webservice\' ]';

		// @todo Generate YAML based on array?
		$this->safeFilePutContents( $this->getProjectDir() . '/config/modules.yaml', $content );
	}

	private function safeFilePutContents( string $filePath, string $content )
	{
		if ( file_put_contents( $filePath, $content, LOCK_EX ) === false ) {
			throw new \RuntimeException( "Failed to write module registry to file: {$filePath}" );
		}
	}

	protected function build( ContainerBuilder $container ): void
	{
		$this->generateRegistry();

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
