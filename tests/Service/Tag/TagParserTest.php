<?php

namespace SyncEngine\Tests\Service\Tag;

use SyncEngine\Entity\Storage;
use SyncEngine\Model\StorageModel;
use SyncEngine\Service\Tag\TagParser;
use SyncEngine\Tests\TestCase\BaseTestCase;

class TagParserTest extends BaseTestCase
{
	public function getResource()
	{
		$data = [
			'#' => '!',
			'foo' => 'bar',
			'array' => [
				'foo' => 'bar',
				'nest' => [
					'bar' => 'foo',
				],
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
			},
			'recursive' => [
				'tag' => '{{ foo }}',
			],
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

		$testSimple = $tagParser->parseTagString( '{{ # }}' );

		$this->assertEquals( '!', $testSimple );

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

		$tagParser->setCleanMode( false );

		$expected = [
			'bar',
			'{{ nope }}',
			'arrays' => [
				'key' => 'bar',
			],
			'objects' => [
				'prop' => 'bar',
				'method' => 'bar',
			],
			'strings' => [
				'May the bar be with you',
				'May the {{ nope }} be with you',
			],
		];

		$result = $tagParser->parseTagArray( $nestedArray );

		$this->assertEquals( $expected, $result );
	}

	public function testStorage(): void
	{
		$storage = new StorageModel( new Storage() );

		$storage->setType( 'mapper' );
		$storage->setData( [
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
			],
		] );

		$tagParser = new TagParser( $storage );

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

	public function testWhitelist()
	{
		$tagParser = $this->getTagParser();

		$data = [
			'{{ foo }}',
			'{{ nope }}',
			'{{ array.bar }}', // Doesn't exist.
			'{{ key.bar }}', // Doesn't exist.
			'{{ nest.bar }}',  // Doesn't exist.
		];

		$whitelist = [
			'array', // Whitelist recursive.
			'key' => '', // Whitelist recursive.
			'nest' => [ // Whitelist only nest.one and nest.two recursively.
				'one',
				'two',
			],
		];

		$expected = [
			'bar',
			'',
			'{{ array.bar }}',
			'{{ key.bar }}',
			'', // nest.bar is not one of nest.one or nest.two.
		];

		$result = $tagParser->setCleanMode( $whitelist )->parseTagArray( $data );

		$this->assertEquals( $expected, $result );

		$data = [
			'{{ foo }}',
			'{{ array.bar }}', // Doesn't exist, not whitelisted
			'{{ array.nest.foo }}', // Doesn't exist, whitelisted.
		];

		$whitelist = [
			'array' => [ 'nest' ],
		];

		$expected = [
			'bar',
			'',
			'{{ array.nest.foo }}',
		];

		$result = $tagParser->setCleanMode( $whitelist )->parseTagArray( $data );

		$this->assertEquals( $expected, $result );
	}

	public function testResursive(): void
	{
		$tagParser = $this->getTagParser();

		$result = $tagParser->parseTagString( '{{ recursive.tag }}' );

		$this->assertEquals( 'bar', $result );

		/**
		 * Disable recurse to reveal tag.
		 */

		$tagParser = new TagParser( $this->getResource(), recurse: false );

		$result = $tagParser->parseTagString( '{{ recursive.tag }}' );

		$this->assertEquals( '{{ foo }}', $result );
	}

	public function testFallback(): void
	{
		$tagParser = $this->getTagParser();

		$result = $tagParser->parseTagString( '{{ nop.tag ?? recursive.tag }}' );

		$this->assertEquals( 'bar', $result );

		/**
		 * Fixed value.
		 */

		$result = $tagParser->parseTagString( '{{ nop.tag ?? "fallback" }}' );

		$this->assertEquals( 'fallback', $result );

		/**
		 * Same assertions but without spaces.
		 */

		$result = $tagParser->parseTagString( '{{nop.tag??recursive.tag}}' );
		$this->assertEquals( 'bar', $result );
		$result = $tagParser->parseTagString( '{{nop.tag??"fallback"}}' );
		$this->assertEquals( 'fallback', $result );
	}
}
