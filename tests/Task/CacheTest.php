<?php

namespace SyncEngine\Tests\Task;

use SyncEngine\Tests\TestCase\TaskTestCase;

class CacheTest extends TaskTestCase
{
	protected string $_task = 'Cache';

	public function testCache(): void
	{
		$context = $this->getContext();

		$data = [
			'name'  => 'Test',
			'price' => 12.34,
			'sku'   => 'test1',
		];

		$config = [
			'action' => 'set',
			'key'    => '',
			'tag'    => 'simple',
		];

		$returnData = $this->execute( $config, $context, $data );

		// Make sure the tasks didn't modify the original data.
		$this->assertEquals( $returnData, $data );

		$result = $context->getCacheTag( 'simple' );

		$this->assertEquals( $data, $result );

		/**
		 * Get
		 */

		$config['action'] = 'get';

		$getData = $this->execute( $config, $context, [] );

		$this->assertEquals( $data, $getData );

		/**
		 * Array type
		 */

		$data = [
			'product' => [
				'name'  => 'Test',
				'price' => 12.34,
				'sku'   => 'test1',
			],
		];

		$config = [
			'action' => 'set',
			'key'    => 'product',
			'tag'    => 'array',
		];

		$returnData = $this->execute( $config, $context, $data );

		// Make sure the tasks didn't modify the original data.
		$this->assertEquals( $returnData, $data );

		$result = $context->getCacheTag( 'array' );

		$this->assertEquals( $data['product'], $result );

		/**
		 * Get
		 */

		$config['action'] = 'get';
		$config['key']    = 'newProduct';

		$getData = $this->execute( $config, $context, [ 'keepme' => '' ] );

		$this->assertArrayHasKey( 'keepme', $getData );
		$this->assertArrayHasKey( 'newProduct', $getData );
		$this->assertEquals( $data['product'], $getData['newProduct'] );
	}

	public function testCachePath(): void
	{
		$context = $this->getContext();

		$data = [
			'name'  => 'Test',
			'price' => 12.34,
			'sku'   => 'test1',
		];

		$config = [
			'action' => 'set',
			'key'    => 'sku',
			'tag'    => 'foo.bar',
		];

		$returnData = $this->execute( $config, $context, $data );

		// Make sure the tasks didn't modify the original data.
		$this->assertEquals( $returnData, $data );

		$result = $context->getCacheTag( 'foo' );
		$this->assertArrayHasKey( 'bar', $result );

		$result = $context->getCacheTag( 'foo.bar' );
		$this->assertEquals( 'test1', $result );

		/**
		 * Get
		 */

		$config['action'] = 'get';
		$config['key']    = 'newSku';

		$result = $this->execute( $config, $context, [] );

		$this->assertArrayHasKey( 'newSku', $result );
		$this->assertEquals( 'test1', $result['newSku'] );
	}

	public function testCacheEnclosed(): void
	{
		$context = $this->getContext();

		$data = [
			[
				'title' => 'Test 1.',
				'sku'   => 'ABCD.001/23456',
			],
			[
				'title' => 'Test 2.',
				'sku'   => '23456.ABCD.001',
			],
		];

		$config = [
			'action' => 'set',
			'key'    => 'sku',
			'tag'    => 'skus."{{ data.sku }}"',
		];

		$returnData = [];
		foreach ( $data as $i => $d ) {
			$returnData[ $i ] = $this->execute( $config, $context, $d );
		}

		// Make sure the tasks didn't modify the original data.
		$this->assertEquals( $returnData, $data );

		$result = $context->getCacheTag( 'skus' );

		//$this->assertArrayHasKey( 'bar', $result );
		$this->assertEquals( [ "ABCD.001/23456" => "ABCD.001/23456", "23456.ABCD.001" => "23456.ABCD.001" ], $result );
	}

	public function testCacheManual()
	{
		$context = $this->getContext();

		$config = [
			'action' => 'set',
			'source' => 'manual',
			'manual' => 'Foo Bar',
			'tag'    => 'manual_sku',
		];

		$this->execute( $config, $context, [] );
		$this->assertEquals( 'Foo Bar', $context->getCacheTag( 'manual_sku' ) );

		$data = [
			[
				'title' => 'Test 1.',
				'sku'   => 'ABCD.001/23456',
			],
			[
				'title' => 'Test 2.',
				'sku'   => '23456.ABCD.001',
			],
		];

		$config = [
			'action' => 'set',
			'source' => 'manual',
			'manual' => '{{ data.0.sku }}',
			'tag'    => 'manual_sku',
		];

		$this->execute( $config, $context, $data );
		$this->assertEquals( 'ABCD.001/23456', $context->getCacheTag( 'manual_sku' ) );

		$config = [
			'action' => 'set',
			'source' => 'manual',
			'manual' => '{{ cache.manual_sku }}',
			'tag'    => 'custom_sku',
		];

		$this->execute( $config, $context, $data );
		$this->assertEquals( 'ABCD.001/23456', $context->getCacheTag( 'custom_sku' ) );

		$config = [
			'action' => 'set',
			'source' => 'manual',
			'manual' => '{{ data|count }}',
			'tag'    => 'count_data',
		];

		$this->execute( $config, $context, $data );
		$this->assertEquals( 2, $context->getCacheTag( 'count_data' ) );
	}
}
