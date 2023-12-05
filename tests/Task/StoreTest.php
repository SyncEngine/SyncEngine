<?php

namespace SyncEngine\Tests\Task;

use SyncEngine\Controller\DefaultController;
use SyncEngine\Entity\Dataset;
use SyncEngine\Model\DatasetModel;
use SyncEngine\Tests\TestCase\TaskTestCase;

class StoreTest extends TaskTestCase
{
	protected string $_task = 'Store';

	protected array $_datasets = [];

	public function setUp(): void
	{
		parent::setUp();

		if ( ! isset( $this->_datasets['default'] ) ) {
			$dataset = new DatasetModel( new Dataset() );

			if ( ! $dataset->getName() ) {
				$dataset->setName( 'Test ' . microtime( false ) );
			}

			$this->resetDataset( $dataset );

			$this->_datasets['default'] = $dataset;
		}
	}

	public function resetDataset( $dataset ) {
		$dataset->setData( [] );
		$dataset->setType( '' );
		$dataset->persist( true );
	}

	public function testStore(): void
	{
		$task = $this->getTask();
		$dataset = $this->_datasets['default'];

		// Reset.
		$this->resetDataset( $dataset );

		$data = [
			'name' => 'Test',
			'price' => 12.34,
			'sku' => 'test1',
		];

		$config = [
			'action' => 'set',
			'key' => '',
			'dataset' => $dataset,
		];

		$task->execute( $config, $this->getContext(), $data );

		$result = $dataset->getData();

		$this->assertEquals( $data, $result );

		/**
		 * Get
		 */

		$config['action'] = 'get';

		$getData = $task->execute( $config, $this->getContext(), [] );

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
			'dataset' => $dataset,
		];

		$task->execute( $config, $this->getContext(), $data );

		$result = $dataset->getData();

		$this->assertEquals( $data['product'], $result );

		/**
		 * Get
		 */

		$config['action'] = 'get';
		$config['key'] = 'newProduct';

		$getData = $task->execute( $config, $this->getContext(), [ 'keepme' => '' ] );

		$this->assertArrayHasKey( 'keepme', $getData );
		$this->assertArrayHasKey( 'newProduct', $getData );
		$this->assertEquals( $data['product'], $getData['newProduct'] );
	}

	public function testStoreFormat(): void
	{
		$task = $this->getTask();
		$dataset = $this->_datasets['default'];

		// Reset.
		$this->resetDataset( $dataset );

		$data = [
			'name' => 'Test',
			'price' => 12.34,
			'sku' => 'test1',
		];

		$config = [
			'action' => 'set',
			'key' => 'sku',
			'dataset' => $dataset,
		];

		$task->execute( $config, $this->getContext(), $data );

		$result = $dataset->getData();

		$this->assertEquals( 'test1', $result );

		/**
		 * Get
		 */

		$config['action'] = 'get';
		$config['key'] = 'newSku';

		$getData = $task->execute( $config, $this->getContext(), [] );

		$this->assertArrayHasKey( 'newSku', $getData );
		$this->assertEquals( 'test1', $getData['newSku'] );
	}

	public function testStorePath(): void
	{
		$task = $this->getTask();
		$dataset = $this->_datasets['default'];

		// Reset.
		$this->resetDataset( $dataset );

		$data = [
			'name' => 'Test',
			'price' => 12.34,
			'sku' => 'test1',
		];

		$config = [
			'action' => 'set',
			'key' => 'sku',
			'dataset' => $dataset,
			'path' => 'foo.bar'
		];


		$task->execute( $config, $this->getContext(), $data );

		$result = $dataset->getData( 'foo' );

		$this->assertArrayHasKey( 'bar', $result );
		$this->assertEquals( 'test1', $result['bar'] );

		/**
		 * Get
		 */

		$config['action'] = 'get';
		$config['key'] = 'newSku';

		$getData = $task->execute( $config, $this->getContext(), [] );

		$this->assertArrayHasKey( 'newSku', $getData );
		$this->assertEquals( 'test1', $getData['newSku'] );
	}
}
