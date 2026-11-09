<?php

namespace SyncEngine\Tests\Service;

use PHPUnit\Framework\TestCase;
use SyncEngine\Service\Requirements;

class RequirementsTest extends TestCase
{
	private Requirements $requirements;

	protected function setUp(): void
	{
		$this->requirements = new Requirements( __DIR__ . '/Fixtures/Requirements' );
	}

	public function testLoadReturnsEmptyArrayForMissingFile(): void
	{
		$requirements = new Requirements( __DIR__ . '/Fixtures/RequirementsMissing' );

		$this->assertSame( [], $requirements->load() );
		$this->assertSame( [], $requirements->getExtensions() );
		$this->assertSame( [], $requirements->getIniSettings() );
	}

	public function testPhpVersionStatus(): void
	{
		$this->assertSame( 'error', $this->requirements->getPhpVersion( '0.9.0' )['status'] );
		$this->assertSame( 'warning', $this->requirements->getPhpVersion( '1.0.0' )['status'] );
		$this->assertSame( 'ok', $this->requirements->getPhpVersion( '999.0.0' )['status'] );
	}

	public function testExtensionsReportStatusAndAlternatives(): void
	{
		$extensions = [];
		foreach ( $this->requirements->getExtensions() as $extension ) {
			$extensions[ $extension['name'] ] = $extension;
		}

		$this->assertTrue( $extensions['json']['loaded'] );
		$this->assertSame( 'ok', $extensions['json']['status'] );

		$this->assertFalse( $extensions['syncengine_missing_required_ext']['loaded'] );
		$this->assertSame( 'error', $extensions['syncengine_missing_required_ext']['status'] );

		$this->assertSame( 'warning', $extensions['syncengine_missing_recommended_ext']['status'] );
		$this->assertSame( 'info', $extensions['syncengine_missing_optional_ext']['status'] );

		$this->assertTrue( $extensions['syncengine_fake_group']['loaded'] );
		$this->assertSame( 'json', $extensions['syncengine_fake_group']['loadedVia'] );
		$this->assertSame( 'ok', $extensions['syncengine_fake_group']['status'] );

		$this->assertFalse( $extensions['syncengine_missing_group']['loaded'] );
		$this->assertSame( 'warning', $extensions['syncengine_missing_group']['status'] );
	}

	public function testIniSettingsStatus(): void
	{
		$settings = $this->getIniSettings(
			[
				'memory_limit'       => '64M',
				'max_execution_time' => '10',
			]
		);

		$this->assertSame( 'error', $settings['memory_limit']['status'] );
		$this->assertSame( 'error', $settings['max_execution_time']['status'] );

		$settings = $this->getIniSettings(
			[
				'memory_limit'       => '128M',
				'max_execution_time' => '30',
			]
		);

		$this->assertSame( 'warning', $settings['memory_limit']['status'] );
		$this->assertSame( 'warning', $settings['max_execution_time']['status'] );

		$settings = $this->getIniSettings(
			[
				'memory_limit'       => '-1',
				'max_execution_time' => '0',
			]
		);

		$this->assertSame( 'ok', $settings['memory_limit']['status'] );
		$this->assertSame( 'ok', $settings['max_execution_time']['status'] );

		$settings = $this->getIniSettings(
			[
				'memory_limit'       => '512M',
				'max_execution_time' => '300',
			]
		);

		$this->assertSame( 'ok', $settings['memory_limit']['status'] );
		$this->assertSame( 'ok', $settings['max_execution_time']['status'] );
	}

	private function getIniSettings( array $currentValues ): array
	{
		$settings = [];
		foreach ( $this->requirements->getIniSettings( $currentValues ) as $setting ) {
			$settings[ $setting['key'] ] = $setting;
		}

		return $settings;
	}
}
