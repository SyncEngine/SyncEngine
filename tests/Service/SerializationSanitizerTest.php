<?php

namespace SyncEngine\Tests\Service;

use SyncEngine\Service\SerializationSanitizer;
use SyncEngine\Tests\TestCase\BaseTestCase;

class SerializationSanitizerTest extends BaseTestCase
{

	// -- sanitize() --------------------------------------------------------------

	public function testSanitizePassesThroughNonStrings(): void
	{
		$sanitizer = new SerializationSanitizer();

		$this->assertSame( 42, $sanitizer->sanitize( 42 ) );
		$this->assertSame( true, $sanitizer->sanitize( true ) );
		$this->assertNull( $sanitizer->sanitize( null ) );
	}

	public function testSanitizeRecursesIntoArray(): void
	{
		$sanitizer = new SerializationSanitizer();
		$data      = [ 'a' => 'hello', 'b' => 'world' ];

		$result = $sanitizer->sanitize( $data );

		$this->assertEquals( [ 'a' => 'hello', 'b' => 'world' ], $result );
	}

	public function testSanitizeRecursesIntoNestedArray(): void
	{
		$sanitizer = new SerializationSanitizer();
		$data    = [ 'level1' => [ 'level2' => [ 'key' => 'value' ] ] ];

		$result = $sanitizer->sanitize( $data );

		$this->assertEquals( 'value', $result['level1']['level2']['key'] );
	}

	public function testSanitizeSkipsUtf8ConversionWhenOptionDisabled(): void
	{
		$sanitizer = new SerializationSanitizer();
		// ISO-8859-1 encoded "café" (é = 0xE9) — not valid UTF-8
		$latin1 = 'caf' . "\xE9";

		$result = $sanitizer->sanitize( [ 'key' => $latin1 ], [ SerializationSanitizer::SANITIZE_UTF8 => false ] );

		$this->assertEquals( $latin1, $result['key'] );
		$this->assertFalse( mb_detect_encoding( $result['key'], 'UTF-8', true ) );
	}

	public function testSanitizeWithIterator(): void
	{
		$sanitizer = new SerializationSanitizer();
		$data      = new \ArrayIterator( [ 'a' => 1, 'b' => 2 ] );

		$result = $sanitizer->sanitize( $data );

		$this->assertEquals( [ 'a' => 1, 'b' => 2 ], iterator_to_array( $result ) );
	}

	public function testSanitizeWithEmptyArray(): void
	{
		$sanitizer = new SerializationSanitizer();

		$result = $sanitizer->sanitize( [] );

		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}

	// -- sanitizeResource() ------------------------------------------------------

	public function testSanitizeSkipsResourceWhenOptionDisabled(): void
	{
		$sanitizer = new SerializationSanitizer();
		$temp    = tmpfile();
		$path    = stream_get_meta_data( $temp )['uri'];
		$resource = fopen( $path, 'r' );

		$result = $sanitizer->sanitize( [ 'data' => $resource ], [ SerializationSanitizer::SANITIZE_RESOURCE => false ] );

		$this->assertSame( $resource, $result['data'] );

		fclose( $temp );
		fclose( $resource );
	}

	public function testSanitizeResourceConvertsResourceToString(): void
	{
		$sanitizer = new SerializationSanitizer();
		$temp      = tmpfile();
		$resource  = fopen( stream_get_meta_data( $temp )['uri'], 'r' );

		$result = $sanitizer->sanitizeResource( $resource );

		$this->assertIsString( $result );
		$this->assertMatchesRegularExpression( '/^resource\(\d+\) of type \(\w+\)$/', $result );

		fclose( $temp );
		fclose( $resource );
	}

	public function testSanitizeResourceReturnsNonResourceUnchanged(): void
	{
		$sanitizer = new SerializationSanitizer();

		$this->assertSame( 'string', $sanitizer->sanitizeResource( 'string' ) );
		$this->assertSame( 42, $sanitizer->sanitizeResource( 42 ) );
	}

	// -- encode_utf8() -----------------------------------------------------------

	public function testEncodeUtf8PassesThroughValidUtf8(): void
	{
		$sanitizer = new SerializationSanitizer();

		$this->assertEquals( 'hello world', $sanitizer->encode_utf8( 'hello world' ) );
		$this->assertEquals( 'こんにちは 🎉', $sanitizer->encode_utf8( 'こんにちは 🎉' ) );
		$this->assertEquals( '', $sanitizer->encode_utf8( '' ) );
	}

	public function testEncodeUtf8DetectsNonUtf8HighBytes(): void
	{
		$sanitizer = new SerializationSanitizer();
		// \xFF\xFE are not valid UTF-8 sequences, mb_detect_encoding returns false
		$data      = "hello\xFF\xFEworld";

		$this->assertFalse( mb_detect_encoding( $data, 'UTF-8', true ) );

		// encode_utf8 calls UTF8Utils::convertToUTF8 which defaults to converting FROM UTF-8
		// so invalid bytes are stripped rather than converted.
		$result = $sanitizer->encode_utf8( $data );

		$this->assertEquals( 'helloworld', $result );
	}

	public function testEncodeUtf8PassesThroughControlCharacters(): void
	{
		$sanitizer = new SerializationSanitizer();
		// mb_detect_encoding with strict mode returns UTF-8 for control characters (false positive)
		$data      = "hello\x01\x02world";

		$result = $sanitizer->encode_utf8( $data );

		$this->assertEquals( "hello\x01\x02world", $result );
	}
}
