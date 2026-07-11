<?php

namespace SyncEngine\Tests\Service;

use Symfony\Component\Filesystem\Filesystem;
use SyncEngine\Model\TraceModel;
use SyncEngine\Service\BlobStore;
use SyncEngine\Structure\Data\ResourceData;
use SyncEngine\Structure\ValueObject\Blob;
use SyncEngine\Tests\TestCase\BaseTestCase;

class BlobStoreTest extends BaseTestCase
{
	private TraceModel $traceModel;
	private string $tempDir;

	public function setUp(): void
	{
		parent::setUp();
		$this->traceModel = TraceModel::create();
		$this->tempDir = $this->traceModel->getTraceDir() . '/test_blobs_' . uniqid();
		BlobStore::setRuntimeInstance( null );
	}

	protected function tearDown(): void
	{
		parent::tearDown();
		( new Filesystem() )->remove( $this->traceModel->getTraceDir() );
		BlobStore::setRuntimeInstance( null );
	}

	private function createBlob( string $content, string $filename ): Blob
	{
		return new Blob( $content, $filename, 'txt', 'text/plain' );
	}

	// -- BlobStore core -----------------------------------------------------------

	public function testRegisterWritesToDiskAndSetsRef(): void
	{
		$store = new BlobStore( $this->tempDir );
		$blob  = $this->createBlob( 'hello world', 'test.txt' );

		$this->assertFalse( $blob->isStored() );

		$ref = $store->register( $blob );

		$this->assertNotEmpty( $ref );
		$this->assertTrue( $blob->isStored() );
		$this->assertEquals( $ref, $blob->getRef() );
		$this->assertTrue( $store->exists( $ref ) );
	}

	public function testRehydrateReturnsBlobWithCorrectContent(): void
	{
		$store = new BlobStore( $this->tempDir );
		$content = 'binary-like content: ' . str_repeat( 'ABCD', 100 );
		$blob    = $this->createBlob( $content, 'data.txt' );
		$ref     = $store->register( $blob );

		$this->assertNotEmpty( $ref );

		$rehydrated = $store->rehydrate( [ '_blob' => true, '_ref' => $ref, 'filename' => 'data.txt' ] );

		$this->assertInstanceOf( Blob::class, $rehydrated );
		$this->assertEquals( $content, $rehydrated->getContents() );
	}

	public function testRehydrateReturnsNullForMissingRef(): void
	{
		$store = new BlobStore( $this->tempDir );
		$this->assertNull( $store->rehydrate( [ '_blob' => true, '_ref' => 'missing' ] ) );
	}

	public function testRehydrateReturnsNullForInvalidMarker(): void
	{
		$store = new BlobStore( $this->tempDir );
		$this->assertNull( $store->rehydrate( [ '_blob' => true ] ) ); // no _ref
		$this->assertNull( $store->rehydrate( [] ) );
	}

	public function testGetPathReturnsDiskLocation(): void
	{
		$store = new BlobStore( $this->tempDir );
		$blob  = $this->createBlob( 'path test', 'file.txt' );
		$ref   = $store->register( $blob );

		$path = $store->getPath( $ref );
		$this->assertNotNull( $path );
		$this->assertEquals( $store->getDirectory() . $ref, $path );
	}

	public function testDeleteRemovesFile(): void
	{
		$store = new BlobStore( $this->tempDir );
		$blob  = $this->createBlob( 'delete me', 'file.txt' );
		$ref   = $store->register( $blob );

		$this->assertTrue( $store->exists( $ref ) );
		$store->delete( $ref );
		$this->assertFalse( $store->exists( $ref ) );

		// Silent on missing.
		$store->delete( 'nonexistent' ); // no exception
	}

	public function testStaticInstanceManagement(): void
	{
		BlobStore::setRuntimeInstance( null );
		$this->assertNull( BlobStore::getRuntimeInstance() );

		$store = new BlobStore( $this->tempDir );
		BlobStore::setRuntimeInstance( $store );
		$this->assertSame( $store, BlobStore::getRuntimeInstance() );
	}

	// -- ResourceData integration -------------------------------------------------

	public function testSetBlobStoresMarkerToDisk(): void
	{
		$store = new BlobStore( $this->tempDir );
		BlobStore::setRuntimeInstance( $store );

		$blob  = $this->createBlob( 'attachment content', 'doc.txt' );
		$data  = ResourceData::create( [ 'name' => 'test' ] );
		$data->set( $blob, 'file' );

		$raw = $data->getArrayCopy();
		$marker = $raw['file'];
		$this->assertIsArray( $marker );
		$this->assertTrue( $marker['_blob'] );
		$this->assertNotEmpty( $marker['_ref'] );
		$this->assertTrue( $store->exists( $marker['_ref'] ) );

		// get() rehydrates the marker back to a Blob.
		$rehydrated = $data->get( 'file' );
		$this->assertInstanceOf( Blob::class, $rehydrated );
		$this->assertEquals( 'attachment content', $rehydrated->getContents() );
	}

	public function testGetBlobRehydratesAutomatically(): void
	{
		$store = new BlobStore( $this->tempDir );
		BlobStore::setRuntimeInstance( $store );

		$content = 'rehydration content';
		$blob    = $this->createBlob( $content, 'data.txt' );
		$data    = ResourceData::create( [] );
		$data->set( $blob, 'file' );

		$result = $data->get( 'file' );
		$this->assertInstanceOf( Blob::class, $result );
		$this->assertEquals( $content, $result->getSplFile()->fread( $result->getSize() ) );
	}

	public function testNestedBlobMarkersInArrayStructure(): void
	{
		$store = new BlobStore( $this->tempDir );
		BlobStore::setRuntimeInstance( $store );

		$blob1 = $this->createBlob( 'first file', 'a.txt' );
		$blob2 = $this->createBlob( 'second file', 'b.txt' );

		$data = ResourceData::create( [
			'documents' => [
				[ 'name' => 'doc1', 'file' => $blob1 ],
				[ 'name' => 'doc2', 'file' => $blob2 ],
			],
		] );

		// Set blobs at nested paths.
		foreach ( [ 0, 1 ] as $i ) {
			$data->set( $i === 0 ? $blob1 : $blob2, "documents.{$i}.file" );
		}

		// Both stored as markers on disk (check raw data, get() rehydrates).
		$raw = $data->getArrayCopy();
		$m1 = $raw['documents'][0]['file'];
		$m2 = $raw['documents'][1]['file'];
		$this->assertIsArray( $m1 );
		$this->assertTrue( $m1['_blob'] );
		$this->assertNotEmpty( $m1['_ref'] );
		$this->assertIsArray( $m2 );
		$this->assertTrue( $m2['_blob'] );
		$this->assertNotEmpty( $m2['_ref'] );
		$this->assertNotEquals( $m1['_ref'], $m2['_ref'] );

		// Both rehydrate correctly with distinct content.
		$r1 = $data->get( 'documents.0.file' );
		$r2 = $data->get( 'documents.1.file' );
		$this->assertInstanceOf( Blob::class, $r1 );
		$this->assertInstanceOf( Blob::class, $r2 );
		$this->assertEquals( 'first file', $r1->getSplFile()->fread( $r1->getSize() ) );
		$this->assertEquals( 'second file', $r2->getSplFile()->fread( $r2->getSize() ) );
	}

	public function testDeeplyNestedBlobRehydration(): void
	{
		$store = new BlobStore( $this->tempDir );
		BlobStore::setRuntimeInstance( $store );

		$content = 'deep nested content';
		$blob    = $this->createBlob( $content, 'deep.txt' );

		$data = ResourceData::create( [ 'a' => [ 'b' => [ 'c' => [ 'd' => [] ] ] ] ] );
		$data->set( $blob, 'a.b.c.d.file' );

		$result = $data->get( 'a.b.c.d.file' );
		$this->assertInstanceOf( Blob::class, $result );
		$this->assertEquals( $content, $result->getSplFile()->fread( $result->getSize() ) );
	}

	public function testMixedBlobAndScalarInSameStructure(): void
	{
		$store = new BlobStore( $this->tempDir );
		BlobStore::setRuntimeInstance( $store );

		$blob  = $this->createBlob( 'file data', 'file.txt' );
		$data  = ResourceData::create( [
			'name'     => 'test',
			'count'    => 42,
			'nested'   => [ 'flag' => true ],
		] );
		$data->set( $blob, 'attachment' );

		// Scalars pass through unchanged.
		$this->assertEquals( 'test', $data->get( 'name' ) );
		$this->assertEquals( 42, $data->get( 'count' ) );
		$this->assertTrue( $data->get( 'nested.flag' ) );

		// Blob rehydrates.
		$result = $data->get( 'attachment' );
		$this->assertInstanceOf( Blob::class, $result );
	}

	public function testMultipleBlobsSameStore(): void
	{
		$store = new BlobStore( $this->tempDir );
		BlobStore::setRuntimeInstance( $store );

		$blobs = [];
		for ( $i = 0; $i < 5; $i++ ) {
			$blob    = $this->createBlob( "content {$i}", "file{$i}.txt" );
			$data    = ResourceData::create( [] );
			$data->set( $blob, 'file' );
			// Store the raw marker array, not the rehydrated Blob.
			$raw = $data->getArrayCopy();
			$blobs[] = [ 'marker' => $raw['file'], 'expected' => "content {$i}" ];
		}

		// Each rehydrates to its own distinct content.
		foreach ( $blobs as $idx => $item ) {
			$data   = ResourceData::create( [ 'file' => $item['marker'] ] );
			$result = $data->get( 'file' );
			$this->assertInstanceOf( Blob::class, $result );
			$this->assertEquals( $item['expected'], $result->getSplFile()->fread( $result->getSize() ) );
		}
	}

	public function testNormalizePreservesBlobMarker(): void
	{
		$store = new BlobStore( $this->tempDir );
		BlobStore::setRuntimeInstance( $store );

		$blob  = $this->createBlob( 'data', 'file.txt' );
		$data  = ResourceData::create( [ 'name' => 'test', 'attachment' => $blob ] );

		$normalized = $data->normalize();
		$this->assertEquals( 'test', $normalized['name'] );
		$this->assertTrue( $normalized['attachment']['_blob'] );
		$this->assertNotEmpty( $normalized['attachment']['_ref'] );
	}
}
