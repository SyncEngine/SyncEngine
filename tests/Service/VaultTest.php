<?php

namespace SyncEngine\Tests\Service;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use SyncEngine\Service\Vault;

class VaultTest extends KernelTestCase
{
	private Vault $vault;

	protected function setUp(): void
	{
		self::bootKernel();
		$this->vault = static::getContainer()->get( Vault::class );
	}

	public function testStoreAndFetchSecret(): void
	{
		$this->vault->seal( 'test_secret_key', 'test_secret_value' );
		$this->assertEquals( 'test_secret_value', $this->vault->reveal( 'test_secret_key' ) );
	}

	public function testFetchAllSecrets(): void
	{
		$this->vault->seal( 'test_fetch_key_1', 'value1' );
		$this->vault->seal( 'test_fetch_key_2', 'value2' );

		$all = $this->vault->list( true );

		$this->assertArrayHasKey( 'test_fetch_key_1', $all );
		$this->assertArrayHasKey( 'test_fetch_key_2', $all );
		$this->assertEquals( 'value1', $all['test_fetch_key_1'] );
		$this->assertEquals( 'value2', $all['test_fetch_key_2'] );
	}

	public function testKeyRotation(): void
	{
		$this->vault->seal( 'test_rotation_key', 'rotation_value' );
		$original = $this->vault->reveal( 'test_rotation_key' );
		$this->assertEquals( 'rotation_value', $original );

		$this->vault->generateKeys( true );

		$after = $this->vault->reveal( 'test_rotation_key' );
		$this->assertEquals( 'rotation_value', $after );
	}

	public function testStoreAndFetchSpecialCharacters(): void
	{
		$passwords = [
			'p@$$w0rd!#%^&*()',
			'Q3$k&bN9!mL2@xP7#vR4',
			'admin; DROP TABLE users;--',
			'file/path\to/special:chars<>|"?',
			"line1\nline2\ttab\rcarriage",
			'unicode: 你好世界 🌍 émojis 😀🎉',
			'base64-like: dGVzdA==',
			'json-like: {"key": "value", "nested": {"a": 1}}',
			'yaml-like: key: value\n- item1\n- item2',
			'sql: SELECT * FROM users WHERE name = \'O\'Brien\'',
			'',
			str_repeat( 'a', 500 ),
			random_bytes( 512 )
		];

		foreach ( $passwords as $index => $value ) {
			$key = "test_special_key_{$index}";
			$this->vault->seal( $key, $value );
			$retrieved = $this->vault->reveal( $key );
			$this->assertEquals( $value, $retrieved, "Failed for value at index {$index}" );
		}
	}
}
