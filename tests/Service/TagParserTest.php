<?php

namespace App\Tests\Service;


use App\Service\TagParser;
use PHPUnit\Framework\TestCase;

class TagParserTest extends TestCase
{
	public function getResource()
	{
		$data = [
			'foo' => 'bar',
			'array' => [
				'foo' => 'bar',
			],
			'objectProp' => new class {
				public $foo = 'bar';
			},
			'objectMethod' => new class {
				private $foo = 'bar';
				public function getFoo() {
					return $this->foo;
				}
				public function setFoo( $value ) {
					$this->foo = $value;
				}
			}
		];

		return $data;
	}

	public function getTagParser( $resource = null ): TagParser
	{
		return new TagParser( $resource ?? $this->getResource() );
	}

	public function testGetString(): void
	{
		$tagParser = $this->getTagParser();

		$testSimple = $tagParser->parseTagString( '{{ foo }}' );

		$this->assertEquals( 'bar', $testSimple );

		$testNotSet = $tagParser->parseTagString( '{{ nope }}' );

		$this->assertEquals( '', $testNotSet );

		$testArray = $tagParser->parseTagString( '{{ array.foo }}' );

		$this->assertEquals( 'bar', $testArray );

		$testObjectProp = $tagParser->parseTagString( '{{ objectProp.foo }}' );

		$this->assertEquals( 'bar', $testObjectProp );

		$testObjectMethod = $tagParser->parseTagString( '{{ objectMethod.foo }}' );

		$this->assertEquals( 'bar', $testObjectMethod );
	}
}
