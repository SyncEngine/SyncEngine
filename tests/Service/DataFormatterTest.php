<?php

namespace SyncEngine\Tests\Service;

use SyncEngine\Exception\CodecException;
use SyncEngine\Model\CodecModel;
use SyncEngine\Service\DataFormatter;
use SyncEngine\Tests\TestCase\BaseTestCase;

class DataFormatterTest extends BaseTestCase
{

	// -- encode() -----------------------------------------------------------------

	public function testEncodeWithStringFormat(): void
	{
		$formatter = new DataFormatter();
		$data      = [ 'name' => 'test', 'value' => 42 ];

		$result = $formatter->encode( 'json', $data );

		$this->assertIsString( $result );
		$decoded = json_decode( $result, true );
		$this->assertEquals( 'test', $decoded['name'] );
		$this->assertEquals( 42, $decoded['value'] );
	}

	public function testEncodeWithArrayConfigFormat(): void
	{
		$formatter = new DataFormatter();
		$data      = [ 'key' => 'val' ];

		$result = $formatter->encode( [ 'format' => 'json' ], $data );

		$this->assertIsString( $result );
		$decoded = json_decode( $result, true );
		$this->assertEquals( 'val', $decoded['key'] );
	}

	public function testEncodeWithUnknownFormatReturnsDataUnchanged(): void
	{
		$formatter = new DataFormatter();
		$data      = [ 'original' => true ];

		$result = $formatter->encode( 'nonexistent_format', $data );

		$this->assertSame( $data, $result );
	}

	public function testEncodeWithEmptyConfigArray(): void
	{
		$formatter = new DataFormatter();
		$data    = [ 'key' => 'val' ];

		$result = $formatter->encode( 'json', $data, [] );

		$this->assertIsString( $result );
	}

	// -- decode() -----------------------------------------------------------------

	public function testDecodeWithStringFormat(): void
	{
		$formatter = new DataFormatter();
		$json      = '{"name":"test","value":42}';

		$result = $formatter->decode( 'json', $json );

		$this->assertIsArray( $result );
		$this->assertEquals( 'test', $result['name'] );
		$this->assertEquals( 42, $result['value'] );
	}

	public function testDecodeWithArrayConfigFormat(): void
	{
		$formatter = new DataFormatter();
		$json      = '{"a":1,"b":2}';

		$result = $formatter->decode( [ 'format' => 'json' ], $json );

		$this->assertIsArray( $result );
		$this->assertEquals( 1, $result['a'] );
	}

	public function testDecodeWithUnknownFormatReturnsStringUnchanged(): void
	{
		$formatter = new DataFormatter();
		$data    = 'raw string content';

		$result = $formatter->decode( 'nonexistent_format', $data );

		$this->assertEquals( 'raw string content', $result );
	}

	public function testDecodeThrowsCodecExceptionOnError(): void
	{
		$formatter = new DataFormatter();

		$this->expectException( CodecException::class );

		$formatter->decode( 'json', '{invalid json}' );
	}

	// -- getContentType() ---------------------------------------------------------

	public function testGetContentTypeForJson(): void
	{
		$formatter = new DataFormatter();

		$contentType = $formatter->getContentType( 'json' );

		$this->assertEquals( 'application/json', $contentType );
	}

	public function testGetContentTypeForCsv(): void
	{
		$formatter = new DataFormatter();

		$contentType = $formatter->getContentType( 'csv' );

		$this->assertEquals( 'text/csv', $contentType );
	}

	public function testGetContentTypeForYaml(): void
	{
		$formatter = new DataFormatter();

		$contentType = $formatter->getContentType( 'yaml' );

		$this->assertEquals( 'text/yaml', $contentType );
	}

	public function testGetContentTypeForXml(): void
	{
		$formatter = new DataFormatter();

		$contentType = $formatter->getContentType( 'xml' );

		$this->assertEquals( 'application/xml', $contentType );
	}

	public function testGetContentTypeForUnknownFormatReturnsEmptyString(): void
	{
		$formatter = new DataFormatter();

		$contentType = $formatter->getContentType( 'nonexistent_format' );

		$this->assertEquals( '', $contentType );
	}

	public function testGetContentTypeWithArrayConfig(): void
	{
		$formatter = new DataFormatter();

		$contentType = $formatter->getContentType( [ 'format' => 'json' ] );

		$this->assertEquals( 'application/json', $contentType );
	}

	// -- getEncoder() -------------------------------------------------------------

	public function testGetEncoderWithStringFormat(): void
	{
		$formatter = new DataFormatter();

		$encoder = $formatter->getEncoder( 'json' );

		$this->assertInstanceOf( CodecModel::class, $encoder );
	}

	public function testGetEncoderWithArrayConfig(): void
	{
		$formatter = new DataFormatter();

		$encoder = $formatter->getEncoder( [ 'format' => 'csv' ] );

		$this->assertInstanceOf( CodecModel::class, $encoder );
	}

	public function testGetEncoderWithUnknownFormatReturnsNull(): void
	{
		$formatter = new DataFormatter();

		$encoder = $formatter->getEncoder( 'nonexistent_format' );

		$this->assertNull( $encoder );
	}

	// -- Prefix config inheritance -----------------------------------------------

	public function testPrefixConfigInheritance(): void
	{
		$formatter = new DataFormatter();
		$data      = [ [ 'a' => 1, 'b' => 2 ] ];

		$result = $formatter->encode( 'csv', $data, [ 'csv_delimiter' => ';', 'csv_enclosure' => "'" ] );

		$this->assertIsString( $result );
		$this->assertStringContainsString( 'a;b', $result );
	}

	public function testPrefixConfigDoesNotOverrideLocal(): void
	{
		$formatter = new DataFormatter();
		$data      = [ [ 'x' => 1 ] ];

		$result = $formatter->encode( [ 'format' => 'csv', 'delimiter' => '|' ], $data, [ 'csv_delimiter' => ';' ] );

		$this->assertIsString( $result );
		$lines = explode( "\n", trim( $result ) );
		$this->assertEquals( 'x', $lines[0] );
	}

	public function testGetEncoderPrefixConfigMerges(): void
	{
		$formatter = new DataFormatter();

		$encoder = $formatter->getEncoder( [ 'format' => 'csv' ], [ 'csv_delimiter' => ';' ] );

		$this->assertInstanceOf( CodecModel::class, $encoder );
	}

	// -- Syntax error handling ---------------------------------------------------

	public function testSyntaxErrorIncludesExceptionClass(): void
	{
		$formatter = new DataFormatter();

		try {
			$formatter->decode( 'json', '{invalid json}' );
			$this->fail( 'Expected CodecException' );
		} catch ( CodecException $e ) {
			// Symfony wraps syntax errors with class prefix
			$this->assertMatchesRegularExpression( '/^[\w\\\\]+: Syntax error$/', $e->getMessage() );
		}
	}
}
