<?php

namespace App\Tests\Service;


use App\Service\ResourceData;
use PHPUnit\Framework\TestCase;

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
			}
		];

		return new ResourceData( $data );
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
	}
}
