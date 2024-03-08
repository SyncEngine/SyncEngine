<?php

namespace SyncEngine\Tests\Task;

use SyncEngine\Entity\Storage;
use SyncEngine\Model\StorageModel;
use SyncEngine\Tests\TestCase\TaskTestCase;

class StoreTest extends TaskTestCase
{
	protected string $_task = 'Store';

	protected array $_storages = [];

	public function setUp(): void
	{
		parent::setUp();

		if ( ! isset( $this->_storages['default'] ) ) {
			$storage = new StorageModel( new Storage() );

			if ( ! $storage->getName() ) {
				$storage->setName( 'Test ' . microtime( false ) );
			}

			$this->resetStorage( $storage );

			$this->_storages['default'] = $storage;
		}
	}

	public function resetStorage( $storage ) {
		$storage->setData( [] );
		$storage->setType( '' );
		$storage->save( true );
	}

	public function testStore(): void
	{
		$storage = $this->_storages['default'];

		// Reset.
		$this->resetStorage( $storage );

		$data = [
			'name' => 'Test',
			'price' => 12.34,
			'sku' => 'test1',
		];

		$config = [
			'action' => 'set',
			'key' => '',
			'storage' => $storage,
		];

		$this->execute( $config, $this->getContext(), $data );

		$result = $storage->getData();

		$this->assertEquals( $data, $result );

		/**
		 * Get
		 */

		$config['action'] = 'get';

		$getData = $this->execute( $config, $this->getContext(), [] );

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
			'storage' => $storage,
		];

		$this->execute( $config, $this->getContext(), $data );

		$result = $storage->getData();

		$this->assertEquals( $data['product'], $result );

		/**
		 * Get
		 */

		$config['action'] = 'get';
		$config['key'] = 'newProduct';

		$getData = $this->execute( $config, $this->getContext(), [ 'keepme' => '' ] );

		$this->assertArrayHasKey( 'keepme', $getData );
		$this->assertArrayHasKey( 'newProduct', $getData );
		$this->assertEquals( $data['product'], $getData['newProduct'] );
	}

	public function testStoreFormat(): void
	{
		$storage = $this->_storages['default'];

		// Reset.
		$this->resetStorage( $storage );

		$data = [
			'name' => 'Test',
			'price' => 12.34,
			'sku' => 'test1',
		];

		$config = [
			'action' => 'set',
			'key' => 'sku',
			'storage' => $storage,
		];

		$this->execute( $config, $this->getContext(), $data );

		$result = $storage->getData();

		$this->assertEquals( 'test1', $result );

		/**
		 * Get
		 */

		$config['action'] = 'get';
		$config['key'] = 'newSku';

		$getData = $this->execute( $config, $this->getContext(), [] );

		$this->assertArrayHasKey( 'newSku', $getData );
		$this->assertEquals( 'test1', $getData['newSku'] );
	}

	public function testStorePath(): void
	{
		$storage = $this->_storages['default'];

		// Reset.
		$this->resetStorage( $storage );

		$data = [
			'name' => 'Test',
			'price' => 12.34,
			'sku' => 'test1',
		];

		$config = [
			'action' => 'set',
			'key' => 'sku',
			'storage' => $storage,
			'path' => 'foo.bar'
		];


		$this->execute( $config, $this->getContext(), $data );

		$result = $storage->getData( 'foo' );

		$this->assertArrayHasKey( 'bar', $result );
		$this->assertEquals( 'test1', $result['bar'] );

		/**
		 * Get
		 */

		$config['action'] = 'get';
		$config['key'] = 'newSku';

		$getData = $this->execute( $config, $this->getContext(), [] );

		$this->assertArrayHasKey( 'newSku', $getData );
		$this->assertEquals( 'test1', $getData['newSku'] );
	}
}
