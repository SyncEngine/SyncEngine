<?php

namespace SyncEngine\Tests\Service;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SyncEngine\Entity\User;
use SyncEngine\Repository\UserRepository;
use SyncEngine\Service\Preferences;
use Symfony\Bundle\SecurityBundle\Security;

class PreferencesValidationTest extends TestCase
{
	#[DataProvider( 'nullByteKeyProvider' )]
	public function testSetThrowsOnNullByteInKey( string $key ): void
	{
		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Preference key contains invalid characters' );

		$preferences = $this->createPreferencesMock();
		$preferences->set( $key, 'value' );
	}

	public static function nullByteKeyProvider(): array
	{
		return [
			[ "key\x00suffix" ],
			[ "key\x00" ],
			[ "\x00key" ],
		];
	}

	#[DataProvider( 'invalidKeyProvider' )]
	public function testSetThrowsOnInvalidCharactersInKey( string $key ): void
	{
		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Preference key contains invalid characters' );

		$preferences = $this->createPreferencesMock();
		$preferences->set( $key, 'value' );
	}

	public static function invalidKeyProvider(): array
	{
		return [
			[ 'key with spaces' ],
			[ 'key@with@at' ],
			[ 'key#with#hash' ],
			[ 'key&with&ampersand' ],
			[ "key\nwith\nnewline" ],
			[ 'café' ],
			[ '日本語' ],
			[ "key\x00suffix" ],
			[ "key\x00" ],
			[ "\x00key" ],
		];
	}

	public function testSetThrowsOnExcessivelyLongKey(): void
	{
		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Preference key exceeds maximum length of 255 characters' );

		$preferences = $this->createPreferencesMock();
		$preferences->set( str_repeat( 'a', 256 ), 'value' );
	}

	public function testSetThrowsOnObjectValue(): void
	{
		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Preference value must be scalar or a plain array of scalars' );

		$preferences = $this->createPreferencesMock();
		$preferences->set( 'key', new \stdClass() );
	}

	public function testSetThrowsOnResourceValue(): void
	{
		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Preference value must be scalar or a plain array of scalars' );

		$temp = tmpfile();
		$preferences = $this->createPreferencesMock();
		$preferences->set( 'key', $temp );
		fclose( $temp );
	}

	public function testSetThrowsOnNestedObjectInArray(): void
	{
		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Preference value must be scalar or a plain array of scalars' );

		$preferences = $this->createPreferencesMock();
		$preferences->set( 'key', [ 'nested' => new \stdClass() ] );
	}

	public function testSetThrowsOnDeepNestedObjectInArray(): void
	{
		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Preference value must be scalar or a plain array of scalars' );

		$preferences = $this->createPreferencesMock();
		$preferences->set( 'key', [ 'a' => [ 'b' => [ 'c' => new \stdClass() ] ] ] );
	}

	public function testSetAllowsValidKeyAndScalarValues(): void
	{
		$preferences = $this->createPreferencesMock();

		// Should not throw.
		$preferences->set( 'valid.key-1_test', 'value' );
		$preferences->set( 'another-key', 42 );
		$preferences->set( 'numeric_key', null );
		$preferences->set( 'float_key', 3.14 );
		$preferences->set( 'bool_key', true );
		$preferences->set( 'string_key', '' );
		$preferences->set( 'a-b.c_d0', 'value' );
		$preferences->set( 'key/with/slashes', 'value' );

		$this->assertTrue( true, 'Valid key/scalar combinations should not throw' );
	}

	public function testSetAllowsPlainArrayOfScalars(): void
	{
		$preferences = $this->createPreferencesMock();

		// Should not throw.
		$preferences->set( 'list', [ 'a', 1, true, null, 3.14 ] );
		$preferences->set( 'empty_array', [] );
		$preferences->set( 'nested_plain', [ 'a' => [ 'b' => [ 'c' => 'value' ] ] ] );

		$this->assertTrue( true, 'Plain arrays of scalars should not throw' );
	}

	private function createPreferencesMock(): Preferences
	{
		$user = $this->createMock( User::class );
		$user->method( 'getUserIdentifier' )->willReturn( 'test@example.com' );
		$user->expects( $this->any() )
			->method( 'setSetting' )
			->willReturn( $user );

		$repository = $this->createMock( UserRepository::class );
		$repository->expects( $this->any() )
			->method( 'save' )
			->willReturnCallback( fn() => true );

		$security = $this->createMock( Security::class );
		$security->expects( $this->any() )
			->method( 'getUser' )
			->willReturn( $user );

		return new Preferences( $security, $repository );
	}
}
