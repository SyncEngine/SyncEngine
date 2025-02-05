<?php

namespace SyncEngine\Tests\Service;


use PHPUnit\Framework\TestCase;
use SyncEngine\Service\Data\ResourceData;

class ResourceDataTest extends TestCase
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
			},
		];

		return new ResourceData( $data );
	}

	public function testHas(): void
	{
		$resource = $this->getResource();

		$this->assertTrue( $resource->has( 'foo' ) );
		$this->assertTrue( $resource->has( [ 'array', 'foo' ] ) );
		$this->assertTrue( $resource->has( [ 'objectProp', 'foo' ] ) );
		$this->assertTrue( $resource->has( [ 'objectMethod', 'foo' ] ) );

		$this->assertFalse( $resource->has( 'nope' ) );
		$this->assertFalse( $resource->has( [ 'array', 'nope' ] ) );
		$this->assertFalse( $resource->has( [ 'objectProp', 'nope' ] ) );
		$this->assertFalse( $resource->has( [ 'objectMethod', 'nope' ] ) );

		// Nested keys as string.
		$this->assertTrue( $resource->has( 'array.foo' ) );
		$this->assertTrue( $resource->has( 'objectProp.foo' ) );
		$this->assertTrue( $resource->has( 'objectMethod.foo' ) );

		$this->assertFalse( $resource->has( 'array.nope' ) );
		$this->assertFalse( $resource->has( 'objectProp.nope' ) );
		$this->assertFalse( $resource->has( 'objectMethod.nope' ) );
	}

	public function testGet(): void
	{
		$resource = $this->getResource();

		$testSimple = $resource->get( 'foo' );

		$this->assertEquals( 'bar', $testSimple );

		$testNotSet = $resource->get( 'nope', 'default' );

		$this->assertEquals( 'default', $testNotSet );

		$testArray = $resource->get( [ 'array', 'foo' ] );

		$this->assertEquals( 'bar', $testArray );

		$testObjectProp = $resource->get( [ 'objectProp', 'foo' ] );

		$this->assertEquals( 'bar', $testObjectProp );

		$testObjectMethod = $resource->get( [ 'objectMethod', 'foo' ] );

		$this->assertEquals( 'bar', $testObjectMethod );

		// Nested keys as string.

		$testArray = $resource->get( 'array.foo' );

		$this->assertEquals( 'bar', $testArray );

		$testObjectProp = $resource->get( 'objectProp.foo' );

		$this->assertEquals( 'bar', $testObjectProp );

		$testObjectMethod = $resource->get( 'objectMethod.foo' );

		$this->assertEquals( 'bar', $testObjectMethod );
	}

	public function testSet(): void
	{
		$resource = $this->getResource();

		$original = $resource->getArrayCopy();

		$resource->set( 'newValue', 'foo' );
		$testSimple = $resource->get( 'foo' );

		$this->assertEquals( 'newValue', $testSimple );

		$resource->set( 'newValue', [ 'array', 'foo' ] );
		$testArray = $resource->get( [ 'array', 'foo' ] );

		$this->assertEquals( 'newValue', $testArray );

		$resource->set( 'newValue', [ 'objectProp', 'foo' ] );
		$testObjectProp = $resource->get( [ 'objectProp', 'foo' ] );

		$this->assertEquals( 'newValue', $testObjectProp );

		$resource->set( 'newValue', [ 'objectMethod', 'foo' ] );
		$testObjectMethod = $resource->get( [ 'objectMethod', 'foo' ] );

		$this->assertEquals( 'newValue', $testObjectMethod );

		// Nested keys as string.

		$resource->set( 'newerValue', 'array.foo' );
		$testArray = $resource->get( 'array.foo' );

		$this->assertEquals( 'newerValue', $testArray );

		$resource->set( 'newerValue', 'objectProp.foo' );
		$testObjectProp = $resource->get( 'objectProp.foo' );

		$this->assertEquals( 'newerValue', $testObjectProp );

		$resource->set( 'newerValue', 'objectMethod.foo' );
		$testObjectMethod = $resource->get( 'objectMethod.foo' );

		$this->assertEquals( 'newerValue', $testObjectMethod );

		$expected = $original;
		$expected['foo'] = 'newValue';
		$expected['array']['foo'] = 'newerValue';
		$expected['objectProp']->foo = 'newerValue';
		$expected['objectMethod']->setFoo( 'newerValue' );

		// Validate full resource.
		$this->assertEquals( $expected, $resource->getArrayCopy() );
	}

	public function testEnclosed(): void
	{
		$resource = new ResourceData( [] );

		$resource->set( 'enclosed', 'root."item.with.dots"' );

		$expected = [
			'root' => [
				'item.with.dots' => 'enclosed',
			],
		];

		// Compare array.
		$this->assertEquals( $expected, $resource->getArrayCopy() );

		// Validate Get method.
		$this->assertEquals( 'enclosed', $resource->get( 'root."item.with.dots"' ) );

		/**
		 * Numbers
		 */

		$resource = new ResourceData( [] );

		$resource->set( 'enclosed', 'root."12.593"' );
		$resource->set( 'foobar', 'root."12.789".nested' );

		$expected = [
			'root' => [
				'12.593' => 'enclosed',
				'12.789' => [
					'nested' => 'foobar',
				],
			],
		];

		// Compare array.
		$this->assertEquals( $expected, $resource->getArrayCopy() );

		// Validate Get method.
		$this->assertEquals( 'enclosed', $resource->get( 'root."12.593"' ) );
		$this->assertEquals( 'foobar', $resource->get( 'root."12.789".nested' ) );

		/**
		 * Escaping
		 */

		$expected = [
			'root' => [
				'12"' => 'root12',
				'12.7"' => 'root127',
				'12.7" foo' => 'root127foo',
				'"12' => 'rootp12',
				'"12.7' => 'rootp127',
				'"12.7" foo"' => 'rootw127foo',
			],
			'root2' => [
				'12"' => [ 'nested' => 'root12nest', ],
				'12.7"' => [ 'nested' => 'root127nest', ],
				'12.7" foo' => [ 'nested' => 'root127foonest', ],
				'"12' => [ 'nested' => 'rootp12nest', ],
				'"12.7' => [ 'nested' => 'rootp127nest', ],
				'"12.7" foo"' => [ 'nested' => 'rootw127foonest', ],
			],
		];

		/** Quotes */

		$resource = new ResourceData( [] );
		$resource->set( 'root12', 'root."12""' );
		$resource->set( 'root127', 'root."12.7""' );
		$resource->set( 'root127foo', 'root."12.7" foo"' );
		$resource->set( 'root12nest', 'root2."12"".nested' );
		$resource->set( 'root127nest', 'root2."12.7"".nested' );
		$resource->set( 'root127foonest', 'root2."12.7" foo".nested' );
		$resource->set( 'rootp12', 'root.""12"' );
		$resource->set( 'rootp127', 'root.""12.7"' );
		$resource->set( 'rootw127foo', 'root.""12.7" foo""' );
		$resource->set( 'rootp12nest', 'root2.""12".nested' );
		$resource->set( 'rootp127nest', 'root2.""12.7".nested' );
		$resource->set( 'rootw127foonest', 'root2.""12.7" foo"".nested' );

		// Compare array.
		$this->assertEquals( $expected, $resource->getArrayCopy() );

		// Validate Get method.
		$this->assertEquals( 'root12', $resource->get( 'root."12""' ) );
		$this->assertEquals( 'root127', $resource->get( 'root."12.7""' ) );
		$this->assertEquals( 'root127foo', $resource->get( 'root."12.7" foo"' ) );
		$this->assertEquals( 'root12nest', $resource->get( 'root2."12"".nested' ) );
		$this->assertEquals( 'root127nest', $resource->get( 'root2."12.7"".nested' ) );
		$this->assertEquals( 'root127foonest', $resource->get( 'root2."12.7" foo".nested' ) );
		$this->assertEquals( 'rootp12', $resource->get( 'root.""12"' ) );
		$this->assertEquals( 'rootp127', $resource->get( 'root.""12.7"' ) );
		$this->assertEquals( 'rootw127foo', $resource->get( 'root.""12.7" foo""' ) );
		$this->assertEquals( 'rootp12nest', $resource->get( 'root2.""12".nested' ) );
		$this->assertEquals( 'rootp127nest', $resource->get( 'root2.""12.7".nested' ) );
		$this->assertEquals( 'rootw127foonest', $resource->get( 'root2.""12.7" foo"".nested' ) );

		/** Brackets */

		$resource = new ResourceData( [] );
		$resource->encloseBrackets = true;

		$resource->set( 'root12', 'root.[12"]' );
		$resource->set( 'root127', 'root.[12.7"]' );
		$resource->set( 'root127foo', 'root.[12.7" foo]' );
		$resource->set( 'root12nest', 'root2.[12"].nested' );
		$resource->set( 'root127nest', 'root2.[12.7"].nested' );
		$resource->set( 'root127foonest', 'root2.[12.7" foo].nested' );
		$resource->set( 'rootp12', 'root.["12]' );
		$resource->set( 'rootp127', 'root.["12.7]' );
		$resource->set( 'rootw127foo', 'root.["12.7" foo"]' );
		$resource->set( 'rootp12nest', 'root2.["12].nested' );
		$resource->set( 'rootp127nest', 'root2.["12.7].nested' );
		$resource->set( 'rootw127foonest', 'root2.["12.7" foo"].nested' );

		// Compare array.
		$this->assertEquals( $expected, $resource->getArrayCopy() );

		// Validate Get method.
		$this->assertEquals( 'root12', $resource->get( 'root.[12"]' ) );
		$this->assertEquals( 'root127', $resource->get( 'root.[12.7"]' ) );
		$this->assertEquals( 'root127foo', $resource->get( 'root.[12.7" foo]' ) );
		$this->assertEquals( 'root12nest', $resource->get( 'root2.[12"].nested' ) );
		$this->assertEquals( 'root127nest', $resource->get( 'root2.[12.7"].nested' ) );
		$this->assertEquals( 'root127foonest', $resource->get( 'root2.[12.7" foo].nested' ) );
		$this->assertEquals( 'rootp12', $resource->get( 'root.["12]' ) );
		$this->assertEquals( 'rootp127', $resource->get( 'root.["12.7]' ) );
		$this->assertEquals( 'rootw127foo', $resource->get( 'root.["12.7" foo"]' ) );
		$this->assertEquals( 'rootp12nest', $resource->get( 'root2.["12].nested' ) );
		$this->assertEquals( 'rootp127nest', $resource->get( 'root2.["12.7].nested' ) );
		$this->assertEquals( 'rootw127foonest', $resource->get( 'root2.["12.7" foo"].nested' ) );

		/**
		 * Combinations
		 */

		$resource = new ResourceData( [] );
		$resource->encloseBrackets = true;

		$resource->set( 'enclosed', 'root.tag."56"".[v@al"ue].chi@ld[12"]' );

		$expected = [
			'root' => [
				'tag' => [
					'56"' => [
						'v@al"ue' => [
							'chi@ld' => [
								'12"' => 'enclosed',
							]
						]
					]
				],
			],
		];

		// Compare array.
		$this->assertEquals( $expected, $resource->getArrayCopy() );

		// Validate Get method.
		$this->assertEquals( 'enclosed', $resource->get( 'root.tag."56"".[v@al"ue].chi@ld[12"]' ) );
	}

	public function testLoop(): void
	{
		$resource = new ResourceData( [
			'products' => [
				[
					'id' => 1,
					'name' => 'One',
				],
				[
					'id' => 2,
					'name' => 'Two',
				],
				[
					'id' => 3,
					'name' => 'Three',
				],
				[
					'id' => 4,
					'name' => 'Four',
				],
			],
		] );

		$expected = [
			'One',
			'Two',
			'Three',
			'Four',
		];

		$this->assertEquals( $expected, $resource->get( 'products.[].name' ) );

		// Set value.

		$resource->set( 'visible', 'products.[].status' );

		$expected = [
			[
				'id' => 1,
				'name' => 'One',
				'status' => 'visible',
			],
			[
				'id' => 2,
				'name' => 'Two',
				'status' => 'visible',
			],
			[
				'id' => 3,
				'name' => 'Three',
				'status' => 'visible',
			],
			[
				'id' => 4,
				'name' => 'Four',
				'status' => 'visible',
			],
		];

		$this->assertEquals( $expected, $resource->get( 'products' ) );
	}

	public function testAppend()
	{
		$data = new ResourceData( [
			[
				'id' => 1,
				'name' => 'One',
			],
			[
				'id' => 2,
				'name' => 'Two',
			],
			[
				'id' => 3,
				'name' => 'Three',
			],
			[
				'id' => 4,
				'name' => 'Four',
			],
		] );

		$append = new ResourceData( [
			[
				'id' => 1,
				'name' => 'One',
			],
			[
				'id' => 2,
				'name' => 'Two',
			],
			[
				'id' => 3,
				'name' => 'Three',
			],
			[
				'id' => 4,
				'name' => 'Four',
			],
		] );

		$data->append( $append );

		$expected = [
			[
				'id' => 1,
				'name' => 'One',
			],
			[
				'id' => 2,
				'name' => 'Two',
			],
			[
				'id' => 3,
				'name' => 'Three',
			],
			[
				'id' => 4,
				'name' => 'Four',
			],
			[
				'id' => 1,
				'name' => 'One',
			],
			[
				'id' => 2,
				'name' => 'Two',
			],
			[
				'id' => 3,
				'name' => 'Three',
			],
			[
				'id' => 4,
				'name' => 'Four',
			],
		];

		$this->assertEquals( $expected, $data->get() );
	}

	public function testInsert()
	{
		$data = new ResourceData( [
			'_1' => [
				'id' => 1,
				'name' => 'One',
			],
			'_2' => [
				'id' => 2,
				'name' => 'Two',
			],
			'_3' => [
				'id' => 3,
				'name' => 'Three',
			],
			'_4' => [
				'id' => 4,
				'name' => 'Four',
			],
		] );

		$new = new ResourceData( [
			'_1' => [
				'id' => 10,
				'name' => 'Ten',
			],
			'_2' => [
				'id' => 20,
				'name' => 'Twenty',
			],
			'_3' => [
				'id' => 30,
				'name' => 'Thirty',
			],
			'_4' => [
				'id' => 40,
				'name' => 'Forty',
			],
		] );

		// Does not override anything.
		$this->assertEquals( $data->get(), $data->insert( $new )->get() );

		$data = $data->list();
		$new  = $data->list();

		// In case if lists the values will be appended.
		$this->assertEquals( array_merge( $data->get(), $new->get() ), $data->insert( $new )->get() );
		// @todo Should ResourceData be immutable?
		$this->assertEquals( 8, $data->count() );

		$data = new ResourceData( [
			'foo' => 'bar',
			'one' => 'two',
			'test' => 'case',
		] );

		$new = new ResourceData( [
			'foo' => 'BAR',
			'one' => null,
			'case' => 'test',
		] );

		$expected = [
			'foo' => 'bar',
			'one' => 'two',
			'test' => 'case',
			'case' => 'test',
		];

		$this->assertEquals( $expected, $data->insert( $new )->get() );
	}

	public function testMerge()
	{
		$data = new ResourceData( [
			'_1' => [
				'id' => 1,
				'name' => 'One',
			],
			'_2' => [
				'id' => 2,
				'name' => 'Two',
			],
			'_3' => [
				'id' => 3,
				'name' => 'Three',
			],
			'_4' => [
				'id' => 4,
				'name' => 'Four',
			],
		] );

		$new = new ResourceData( [
			'_1' => [
				'id' => 10,
				'name' => 'Ten',
			],
			'_2' => [
				'id' => 20,
				'name' => 'Twenty',
			],
			'_3' => [
				'id' => 30,
				'name' => 'Thirty',
			],
			'_4' => [
				'id' => 40,
				'name' => 'Forty',
			],
		] );

		// Overrides because the keys are the same.
		$this->assertEquals( $new->get(), $data->merge( $new )->get() );

		$data = $data->list();
		$new  = $data->list();

		// In case if lists the values will be appended.
		$this->assertEquals( array_merge( $data->get(), $new->get() ), $data->insert( $new )->get() );
		// @todo Should ResourceData be immutable?
		$this->assertEquals( 8, $data->count() );

		$data = new ResourceData( [
			'foo' => 'bar',
			'one' => 'two',
			'test' => 'case',
		] );

		$new = new ResourceData( [
			'foo' => 'BAR',
			'one' => null,
			'case' => 'test',
		] );

		$expected = [
			'foo' => 'BAR',
			'one' => 'two',
			'test' => 'case',
			'case' => 'test',
		];

		$this->assertEquals( $expected, $data->merge( $new )->get() );
	}

	public function testReplace()
	{

		$data = new ResourceData( [
			'foo' => 'bar',
			'one' => 'two',
			'test' => 'case',
		] );

		$new = new ResourceData( [
			'foo' => 'BAR',
			'one' => null,
			'case' => 'test',
		] );

		$expected = [
			'foo' => 'BAR',
			'one' => null,
			'test' => 'case',
			'case' => 'test',
		];

		$this->assertEquals( $expected, $data->replace( $new )->get() );
	}
}
