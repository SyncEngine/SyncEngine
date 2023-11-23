<?php

namespace App\Tests\Task;

use App\Controller\DefaultController;
use App\Entity\Dataset;
use App\Model\DatasetModel;
use App\Tests\TestCase\TaskTestCase;

class CacheTest extends TaskTestCase
{
	protected string $_task = 'Cache';

	public function testCache(): void
	{
		$task = $this->getTask();
		$context = $this->getContext();

		$data = [
			'name' => 'Test',
			'price' => 12.34,
			'sku' => 'test1',
		];

		$config = [
			'action' => 'set',
			'key' => '',
			'tag' => 'simple',
		];

		$task->execute( $config, $context, $data );

		$result = $context->getCacheTag( 'simple' );

		$this->assertEquals( $data, $result );

		/**
		 * Get
		 */

		$config['action'] = 'get';

		$getData = $task->execute( $config, $context, [] );

		$this->assertEquals( $data, $getData );

		/**
		 * Array type
		 */

		$data = [
			'product' => [
				'name' => 'Test',
				'price' => 12.34,
				'sku' => 'test1',
			],
		];

		$config = [
			'action' => 'set',
			'key' => 'product',
			'tag' => 'array',
		];

		$task->execute( $config, $context, $data );

		$result = $context->getCacheTag( 'array' );

		$this->assertEquals( $data['product'], $result );

		/**
		 * Get
		 */

		$config['action'] = 'get';
		$config['key'] = 'newProduct';

		$getData = $task->execute( $config, $context, [ 'keepme' => '' ] );

		$this->assertArrayHasKey( 'keepme', $getData );
		$this->assertArrayHasKey( 'newProduct', $getData );
		$this->assertEquals( $data['product'], $getData['newProduct'] );
	}
}
