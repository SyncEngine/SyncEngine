<?php

namespace SyncEngine\Tests\Framework;

use Symfony\Component\Filesystem\Filesystem;
use SyncEngine\Framework\ModuleRegistryManager;
use SyncEngine\Tests\TestCase\BaseTestCase;

class ModuleRegistryManagerTest extends BaseTestCase
{
	private string $projectDir;

	public function setUp(): void
	{
		parent::setUp();

		$root = static::$kernel->getProjectDir();
		$dir  = 'syncengine-module-registry-' . bin2hex( random_bytes( 6 ) );

		$this->projectDir = $root . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . $dir;

		( new Filesystem() )->mkdir( $this->projectDir );
	}

	protected function tearDown(): void
	{
		( new Filesystem() )->remove( $this->projectDir );

		parent::tearDown();
	}

	public function testBuildModuleRegistryUsesComposerSemverConstraints(): void
	{
		$this->createModule( 'Vendor', 'Matching', '^1.2' );
		$this->createModule( 'Vendor', 'OutOfRange', '^2.0' );

		$manager  = new ModuleRegistryManager( $this->projectDir, '1.2.3' );
		$registry = $manager->buildModuleRegistry();

		$this->assertArrayHasKey( 'Vendor/Matching', $registry['status']['active'] );
		$this->assertArrayHasKey( 'Vendor/OutOfRange', $registry['status']['disabled'] );
		$this->assertStringContainsString(
			'Module requires core version "^2.0"',
			$registry['status']['disabled']['Vendor/OutOfRange']['reason']
		);
	}

	public function testBuildModuleRegistryAllowsPrereleaseCoreForSameBaseGteConstraint(): void
	{
		$this->createModule( 'Vendor', 'Prerelease', '>=0.1.0' );

		$manager  = new ModuleRegistryManager( $this->projectDir, '0.1.0-beta.2' );
		$registry = $manager->buildModuleRegistry();

		$this->assertArrayHasKey( 'Vendor/Prerelease', $registry['status']['active'] );
		$this->assertArrayNotHasKey( 'Vendor/Prerelease', $registry['status']['disabled'] );
	}

	public function testAppendDisabledModulesPersistsIntoSingleRegistryFile(): void
	{
		$this->createModule( 'Vendor', 'Manual' );

		$manager = new ModuleRegistryManager( $this->projectDir, '1.2.3' );
		$manager->appendDisabledModules( [ 'Vendor/Manual' ], 'Disabled manually for test' );

		$status = $manager->getModuleStatus();
		$this->assertSame( 'Disabled manually for test', $status['manual']['disabled']['Vendor/Manual'] ?? null );

		$registry = $manager->buildModuleRegistry();
		$this->assertArrayHasKey( 'Vendor/Manual', $registry['status']['disabled'] );
		$this->assertSame( 'Disabled manually for test', $registry['status']['disabled']['Vendor/Manual']['reason'] );
	}

	public function testEnableModuleRemovesAnyDisabledReasonAndReactivatesModule(): void
	{
		$this->createModule( 'Vendor', 'Recoverable' );

		$manager = new ModuleRegistryManager( $this->projectDir, '1.2.3' );
		$manager->appendDisabledModules( [ 'Vendor/Recoverable' ], 'Auto-disabled after runtime module exception: test' );

		$disabledRegistry = $manager->buildModuleRegistry();
		$this->assertArrayHasKey( 'Vendor/Recoverable', $disabledRegistry['status']['disabled'] );

		$manager->enableModule( 'Vendor/Recoverable' );

		$enabledRegistry = $manager->buildModuleRegistry();
		$this->assertArrayHasKey( 'Vendor/Recoverable', $enabledRegistry['status']['active'] );
		$this->assertArrayNotHasKey( 'Vendor/Recoverable', $enabledRegistry['status']['disabled'] );
	}

	private function createModule( string $vendor, string $module, ?string $coreConstraint = null ): void
	{
		$moduleRoot = $this->projectDir . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . $vendor . DIRECTORY_SEPARATOR . $module;
		$srcDir     = $moduleRoot . DIRECTORY_SEPARATOR . 'src';

		( new Filesystem() )->mkdir( $srcDir );

		$manifest = [
			'name'  => strtolower( $vendor . '/' . $module ),
			'require' => [
				'syncengine' => $coreConstraint ?? '*',
			]
		];

		( new Filesystem() )->dumpFile(
			$moduleRoot . DIRECTORY_SEPARATOR . 'module.json',
			json_encode( $manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n"
		);
	}
}

