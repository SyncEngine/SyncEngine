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
				'bar' => 'sub',
				'nest' => [
					'bar' => 'foo',
				],
				'sub' => 'subtag',
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

		$testSimple = $tagParser->parseString( '{{ # }}' );

		$this->assertEquals( '!', $testSimple );

		$testSimple = $tagParser->parseString( '{{ foo }}' );

		$this->assertEquals( 'bar', $testSimple );

		$testNotSet = $tagParser->parseString( '{{ nope }}' );

		$this->assertEquals( '', $testNotSet );

		$testArray = $tagParser->parseString( '{{ array.foo }}' );

		$this->assertEquals( 'bar', $testArray );

		$testObjectProp = $tagParser->parseString( '{{ objectProp.foo }}' );

		$this->assertEquals( 'bar', $testObjectProp );

		$testObjectMethod = $tagParser->parseString( '{{ objectMethod.foo }}' );

		$this->assertEquals( 'bar', $testObjectMethod );

		// Tags are part of string.

		$testWithinString = $tagParser->parseString( 'May the {{ foo }} be with you' );

		$this->assertEquals( 'May the bar be with you', $testWithinString );

		$testEmptyWithinString = $tagParser->parseString( 'May the {{ nope }} be with you' );

		$this->assertEquals( 'May the  be with you', $testEmptyWithinString );

		$tagParser->setCleanMode( false );

		$testEmptyWithinStringNoCleanup = $tagParser->parseString( 'May the {{ cheese }} be with you' );

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

		$result = $tagParser->parseArray( $array );

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

		$result = $tagParser->parseArray( $nestedArray );

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

		$result = $tagParser->parseArray( $nestedArray );

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

		$result = $tagParser->parseString( $tag );

		$this->assertEquals( $expected, $result );

		// String filter.

		$tag = '{{ dataKeys|String }}';

		$expected = 'foo,bar,id';

		$result = $tagParser->parseString( $tag );

		$this->assertEquals( $expected, $result );
	}

	public function testWhitelist()
	{
		$tagParser = $this->getTagParser();

		$data = [
			'{{ foo }}',
			'{{ nope }}',
			'{{ array.nope }}', // Doesn't exist.
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
			'{{ array.nope }}',
			'{{ key.bar }}',
			'', // nest.bar is not one of nest.one or nest.two.
		];

		$result = $tagParser->setCleanMode( $whitelist )->parseArray( $data );

		$this->assertEquals( $expected, $result );

		$data = [
			'{{ foo }}',
			'{{ array.nope }}', // Doesn't exist, not whitelisted.
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

		$result = $tagParser->setCleanMode( $whitelist )->parseArray( $data );

		$this->assertEquals( $expected, $result );
	}

	public function testResursive(): void
	{
		$tagParser = $this->getTagParser();

		$result = $tagParser->parseString( '{{ recursive.tag }}' );

		$this->assertEquals( 'bar', $result );

		/**
		 * Disable recurse to reveal tag.
		 */

		$tagParser = new TagParser( $this->getResource(), recurse: false );

		$result = $tagParser->parseString( '{{ recursive.tag }}' );

		$this->assertEquals( '{{ foo }}', $result );
	}

	public function testFallback(): void
	{
		$tagParser = $this->getTagParser();

		$result = $tagParser->parseString( '{{ nop.tag ?? recursive.tag }}' );

		$this->assertEquals( 'bar', $result );

		/**
		 * Fixed value.
		 */

		$result = $tagParser->parseString( '{{ nop.tag ?? "fallback" }}' );

		$this->assertEquals( 'fallback', $result );

		/**
		 * Same assertions but without spaces.
		 */

		$result = $tagParser->parseString( '{{nop.tag??recursive.tag}}' );
		$this->assertEquals( 'bar', $result );
		$result = $tagParser->parseString( '{{nop.tag??"fallback"}}' );
		$this->assertEquals( 'fallback', $result );
	}

	public function testSubTag(): void
	{
		$tagParser = $this->getTagParser();

		/**
		 * foo > bar
		 * array.nest.bar > foo
		 */
		$result = $tagParser->parseString( '{{ array.nest.<{ foo }> }}' );

		$this->assertEquals( 'foo', $result );

		/**
		 * Nested subtags.
		 *
		 * foo > bar
		 * array.bar > sub
		 * array.sub > subtag
		 */
		$result = $tagParser->parseString( '{{ array.<{ array.<{ foo }> }> }}' );

		$this->assertEquals( 'subtag', $result );

	}

	public function testUnsetFilter(): void
	{
		$parser = new TagParser( [] );

		/**
		 * The count filter returns 0 if data is set.
		 * It should not return a value if the data is not set at all.
		 * This test will prevent reintroduction of such a bug.
		 */
		$result = $parser->parseString( '{{ data|Count }}' );

		$this->assertEquals( null, $result );
	}
}
