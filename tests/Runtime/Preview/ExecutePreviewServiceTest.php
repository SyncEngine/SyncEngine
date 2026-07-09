<?php

namespace SyncEngine\Tests\Runtime\Preview;

use SyncEngine\Runtime\ExecutePreview;
use SyncEngine\Tests\TestCase\PreviewRuntimeScenarioTestCase;

class ExecutePreviewServiceTest extends PreviewRuntimeScenarioTestCase
{
	public function testExecutePreviewDefaultModeIsSafe(): void
	{
		$executePreview = static::getContainer()->get( ExecutePreview::class );
		$this->assertSame( 'safe', $executePreview->getMode() );
		$this->assertFalse( $executePreview->isLive() );
	}

	public function testExecutePreviewIsDebugAlwaysTrue(): void
	{
		$executePreview = static::getContainer()->get( ExecutePreview::class );
		$this->assertTrue( $executePreview->isDebug() );
	}

	public function testExecutePreviewScopeReturnsEmptyInitially(): void
	{
		$executePreview = static::getContainer()->get( ExecutePreview::class );
		$scope = $executePreview->getScope();
		$this->assertIsArray( $scope );
		$this->assertEmpty( $scope );
	}

	public function testExecutePreviewModeConstantsDefined(): void
	{
		$this->assertSame( 'safe', ExecutePreview::MODE_SAFE );
		$this->assertSame( 'live', ExecutePreview::MODE_LIVE );
	}

	public function testExecutePreviewScheduleSkippedInPreviewMode(): void
	{
		$executePreview = static::getContainer()->get( ExecutePreview::class );
		$automation = $this->createAutomationScenario( 'Schedule Test', [
			'source' => [ 'request' ],
			'actions' => 'routine',
			'routine' => $this->createRoutineScenario( 'Test Routine', [
				[ '_class' => 'Set', '_ref' => 'test', 'params' => [] ],
			] )->getId(),
		] );

		$context = $this->getContext( $automation );
		$result = $executePreview->schedule( $automation, $context );

		$this->assertSame( 'skipped', $result->getStatus() );
		$this->assertSame( 'preview_mode', $result->getReason() );
	}
}
