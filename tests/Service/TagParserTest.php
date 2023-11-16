<?php

namespace App\Tests\Service;


use App\Entity\Dataset;
use App\Model\DatasetModel;
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

		// Tags are part of string.

		$testWithinString = $tagParser->parseTagString( 'May the {{ foo }} be with you' );

		$this->assertEquals( 'May the bar be with you', $testWithinString );

		$testEmptyWithinString = $tagParser->parseTagString( 'May the {{ nope }} be with you' );

		$this->assertEquals( 'May the  be with you', $testEmptyWithinString );

		$tagParser->setCleanMode( false );

		$testEmptyWithinStringNoCleanup = $tagParser->parseTagString( 'May the {{ cheese }} be with you' );

		$this->assertEquals( 'May the {{ cheese }} be with you', $testEmptyWithinStringNoCleanup );
	}

	public function testGetArray(): void
	{
		$tagParser = $this->getTagParser();

		$array = [
			'{{ foo }}',
			'{{ nope }}',
			'{{ array.foo }}',
			'{{ objectProp.foo }}',
			'{{ objectMethod.foo }}',
		];

		$expected = [
			'bar',
			'',
			'bar',
			'bar',
			'bar',
		];

		$result = $tagParser->parseTagArray( $array );

		$this->assertEquals( $expected, $result );

		$nestedArray = [
			'{{ foo }}',
			'{{ nope }}',
			'arrays' => [
				'key' => '{{ array.foo }}',
			],
			'objects' => [
				'prop' => '{{ objectProp.foo }}',
				'method' => '{{ objectMethod.foo }}',
			],
			'strings' => [
				'May the {{ foo }} be with you',
				'May the {{ nope }} be with you',
			],
		];

		$expected = [
			'bar',
			'',
			'arrays' => [
				'key' => 'bar',
			],
			'objects' => [
				'prop' => 'bar',
				'method' => 'bar',
			],
			'strings' => [
				'May the bar be with you',
				'May the  be with you',
			],
		];

		$result = $tagParser->parseTagArray( $nestedArray );

		$this->assertEquals( $expected, $result );
	}

	public function testDataset(): void
	{
		$dataset = new DatasetModel( new Dataset() );

		$dataset->setType( 'mapper' );
		$dataset->setData( [
			[
				'source' => 'foo',
				'target' => 'oof',
			],
			[
				'source' => 'bar',
				'target' => 'rab',
			],
			[
				'source' => 'id',
				'target' => 'id',
			]
		] );

		$tagParser = new TagParser( $dataset );

		$tag = '{{ dataKeys }}';

		$expected = [ 'foo', 'bar', 'id' ];

		$result = $tagParser->parseTagString( $tag );

		$this->assertEquals( $expected, $result );

		// String filter.

		$tag = '{{ dataKeys|String }}';

		$expected = 'foo,bar,id';

		$result = $tagParser->parseTagString( $tag );

		$this->assertEquals( $expected, $result );
	}
}
