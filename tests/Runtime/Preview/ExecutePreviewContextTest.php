<?php

namespace SyncEngine\Tests\Runtime\Preview;

use SyncEngine\Model\AutomationModel;
use SyncEngine\Model\TraceModel;
use SyncEngine\Runtime\ExecutePreview;
use SyncEngine\Runtime\ExecutePreviewContext;
use SyncEngine\Tests\TestCase\ExecuteTestCase;

class ExecutePreviewContextTest extends ExecuteTestCase
{
	public function testPreviewContextIsCreatedWithVariables(): void
	{
		$executePreview = static::getContainer()->get( ExecutePreview::class );
		$variables = [ 'test_var' => 'test_value', 'another' => 'another_value' ];

		$context = new ExecutePreviewContext( $executePreview, variables: $variables );

		$this->assertInstanceOf( ExecutePreviewContext::class, $context );
		$this->assertSame( 'test_value', $context->getVariables()['test_var'] );
		$this->assertSame( 'another_value', $context->getVariables()['another'] );
	}

	public function testPreviewContextRegistersTrace(): void
	{
		$executePreview = static::getContainer()->get( ExecutePreview::class );
		$context = new ExecutePreviewContext( $executePreview );

		$trace = TraceModel::create();
		$context->registerTrace( $trace );

		$this->assertSame( $trace, $context->getTrace() );
	}

	public function testPreviewContextIsPreviewReturnsTrueForSafe(): void
	{
		$executePreview = static::getContainer()->get( ExecutePreview::class );
		$context = new ExecutePreviewContext( $executePreview );

		// Default mode is safe.
		$this->assertTrue( $context->isPreview( 'safe' ) );
		$this->assertFalse( $context->isPreview( 'live' ) );
	}

	public function testPreviewContextIsPreviewReturnsTrueForLive(): void
	{
		$executePreview = static::getContainer()->get( ExecutePreview::class );
		$context = new ExecutePreviewContext( $executePreview );

		$executePreview->setMode( 'live' );
		$this->assertFalse( $context->isPreview( 'safe' ) );
		$this->assertTrue( $context->isPreview( 'live' ) );
	}

	public function testPreviewContextGetCacheReturnsEmptyInitially(): void
	{
		$executePreview = static::getContainer()->get( ExecutePreview::class );
		$context = new ExecutePreviewContext( $executePreview );

		$cache = $context->getCache()->normalize();
		$this->assertEmpty( $cache );
	}

	public function testPreviewContextAddErrorStoresErrors(): void
	{
		$executePreview = static::getContainer()->get( ExecutePreview::class );
		$context = new ExecutePreviewContext( $executePreview );

		$testException = new \RuntimeException( 'Test error' );
		$context->addError( $testException );

		$errors = $context->getErrors();
		$this->assertNotEmpty( $errors );
	}

	public function testPreviewContextAddLogStoresLogs(): void
	{
		$executePreview = static::getContainer()->get( ExecutePreview::class );
		$context = new ExecutePreviewContext( $executePreview );

		$context->addLog( 'Test log entry' );

		$logs = $context->getLogs();
		$this->assertNotEmpty( $logs );
	}

	public function testPreviewContextInitScopeSetsAutomation(): void
	{
		$executePreview = static::getContainer()->get( ExecutePreview::class );
		$context = new ExecutePreviewContext( $executePreview );

		$automation = AutomationModel::create();
		$automation->setName( 'Test Automation' );
		$automation->setConfig( [ 'variables' => [ 'scope_var' => 'scope_value' ] ] );

		$context->initScope( $automation );

		$this->assertSame( $automation, $context->getAutomation() );
	}

	public function testPreviewContextGetExecuteServiceReturnsPreview(): void
	{
		$executePreview = static::getContainer()->get( ExecutePreview::class );
		$context = new ExecutePreviewContext( $executePreview );

		$retrieved = $context->getExecuteService();
		$this->assertInstanceOf( ExecutePreview::class, $retrieved );
	}

	public function testPreviewContextVariablesMergeWithScope(): void
	{
		$executePreview = static::getContainer()->get( ExecutePreview::class );
		$context = new ExecutePreviewContext( $executePreview, variables: [ 'local_var' => 'local_value' ] );

		$automation = AutomationModel::create();
		$automation->setConfig( [ 'variables' => [ 'scope_var' => 'scope_value' ] ] );

		$context->initScope( $automation );

		$variables = $context->getVariables();
		$this->assertSame( 'local_value', $variables['local_var'] );
		$this->assertSame( 'scope_value', $variables['scope_var'] );
	}
}
