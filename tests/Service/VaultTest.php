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
		$this->vault->generateKeys( false );
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
		];

		foreach ( $passwords as $index => $value ) {
			$key = "test_special_key_{$index}";
			$this->vault->seal( $key, $value );
			$retrieved = $this->vault->reveal( $key );
			$this->assertEquals( $value, $retrieved, "Failed for value at index {$index}" );
		}
	}

	public function testSetThrowsOnNonStringValue(): void
	{
		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Vault value must be a string or null' );

		$this->vault->set( 'test_array_key', [ 'nested' => 'value' ] );
	}

	public function testSetThrowsOnBinaryValue(): void
	{
		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Vault value must be valid UTF-8' );

		$this->vault->seal( 'test_binary_key', random_bytes( 512 ) );
	}

	public function testStorePasswordWithControlCharacters(): void
	{
		$password = "line1\nline2\ttab\rcarriage\x01\x02end";

		$this->vault->seal( 'test_control_key', $password );
		$retrieved = $this->vault->reveal( 'test_control_key' );
		$this->assertEquals( $password, $retrieved );
	}

	public function testStoreAndFetchSshPrivateKey(): void
	{
		$sshKey = '-----BEGIN OPENSSH PRIVATE KEY-----' . "\n"
			. 'b3BlbnNzaC1rZXktdjEAAAAABG5vbmUAAAAEbm9uZQAAAAAAAAABAAAAMwAAAAtzc2gtZW'
			. 'QyNTUxOQAAACDmX3gRkFpMmJ0bGVhVjFmYWNrZXJzZGZhc2RmYXNkZmFzZGZhc2Rm'
			. '-----END OPENSSH PRIVATE KEY-----';

		$this->vault->seal( 'test_ssh_key', $sshKey );
		$retrieved = $this->vault->reveal( 'test_ssh_key' );
		$this->assertEquals( $sshKey, $retrieved );
	}

	public function testStoreAndFetchRsaPrivateKey(): void
	{
		$rsaKey = '-----BEGIN RSA PRIVATE KEY-----' . "\n"
			. 'MIIEpAIBAAKCAQEA0Z3VS5JJcds3xfn/ygWyF8PbnGy0AHB7MhgHcTz6sE2I2yPB'
			. 'aFDrBz9vFqU4yLqM5sKjNkRdOxXVbN3wQZ1rTgE4pJmW9fGhYiUoP2lKjS8nRQ=='
			. '-----END RSA PRIVATE KEY-----';

		$this->vault->seal( 'test_rsa_key', $rsaKey );
		$retrieved = $this->vault->reveal( 'test_rsa_key' );
		$this->assertEquals( $rsaKey, $retrieved );
	}
}
