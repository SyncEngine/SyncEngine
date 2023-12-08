<?php

namespace SyncEngine\Tests\Task;

use SyncEngine\Controller\DefaultController;
use SyncEngine\Entity\Dataset;
use SyncEngine\Model\DatasetModel;
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

		$this->execute( $config, $context, $data );

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

		$this->execute( $config, $context, $data );

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

		$this->execute( $config, $context, $data );

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
}
