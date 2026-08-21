<?php

namespace SyncEngine\Tests\Service;

use Symfony\Component\Filesystem\Filesystem;
use SyncEngine\Service\Env;
use SyncEngine\Tests\TestCase\BaseTestCase;

class EnvTest extends BaseTestCase
{

	private string $tempDir;

	public function setUp(): void
	{
		parent::setUp();

		$this->tempDir = sys_get_temp_dir() . '/syncengine_env_test_' . uniqid();
		var_dump($this->tempDir);
		( new Filesystem() )->mkdir( $this->tempDir );
	}

	protected function tearDown(): void
	{
		( new Filesystem() )->remove( $this->tempDir );
	}

	public function testSetAndFetchVariable(): void
	{
		$env = new Env( $this->tempDir );
		$env->setEnvFile( 'test.tmp' );

		$env->set( 'CUSTOM_VAR', 'hello_world' );

		$this->assertEquals( 'hello_world', $env->get( 'CUSTOM_VAR' ) );
	}

	public function testPersistWritesToEnvFile(): void
	{
		$env = new Env( $this->tempDir );
		$env->setEnvFile( 'test' );

		$env->set( 'SYNCENGINE_TEST_VAR', 'test_value_123' );
		$env->persist();

		$this->assertEquals( 'test_value_123', $env->get( 'SYNCENGINE_TEST_VAR' ) );

		// Verify the actual file contains the value.
		$file = $this->tempDir . '/.env.test';
		$this->assertTrue( file_exists( $file ), "Env file was not created at {$file}" );

		$content = file_get_contents( $file );
		$this->assertStringContainsString( 'SYNCENGINE_TEST_VAR=test_value_123', $content );
	}

	public function testGetReturnsNullForMissingKey(): void
	{
		$env = new Env( $this->tempDir );
		$env->setEnvFile( 'test' );

		$this->assertNull( $env->get( 'NONEXISTENT_KEY' ) );
	}

	public function testGetReturnsAllVarsWhenNoKeyGiven(): void
	{
		$env = new Env( $this->tempDir );
		$env->setEnvFile( 'test' );

		$env->set( 'A', '1' );
		$env->set( 'B', '2' );

		$all = $env->get();

		$this->assertIsArray( $all );
		$this->assertEquals( '1', $all['A'] );
		$this->assertEquals( '2', $all['B'] );
	}

	public function testPersistWithSpecialCharsQuotesValue(): void
	{
		$env = new Env( $this->tempDir );
		$env->setEnvFile( 'test' );

		// Value contains a colon — should be quoted by write().
		$env->set( 'SPECIAL_VAR', 'http://example.com' );
		$env->persist();

		$file = $this->tempDir . '/.env.test';
		$content = file_get_contents( $file );

		$this->assertStringContainsString( 'SPECIAL_VAR="http://example.com"', $content );
	}
}
