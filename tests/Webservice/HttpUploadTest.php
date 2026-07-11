<?php

namespace SyncEngine\Tests\Webservice;

use Symfony\Component\Mime\Part\Multipart\FormDataPart;
use SyncEngine\Model\WebserviceModel;
use SyncEngine\Runtime\ExecuteData;
use SyncEngine\Structure\Data\ResourceData;
use SyncEngine\Structure\ValueObject\Blob;
use SyncEngine\Tests\Mock\Webservice\MockHttp;
use SyncEngine\Tests\TestCase\BaseTestCase;
use SyncEngine\Webservice\Helper\Result;

class HttpUploadTest extends BaseTestCase
{
	public function setUp(): void
	{
		parent::setUp();
		MockHttp::resetMockState();
	}

	/**
	 * @return MockHttp
	 */
	protected function getMockHttp(): MockHttp
	{
		return WebserviceModel::get( 'MockHttp' );
	}

	// ── Send (upload) ──────────────────────────────────────────────────────────

	public function testSendSingleBlobAsRawStream(): void
	{
		$mock = $this->getMockHttp();
		$mock::primeMockResponses( [
			[ 'body' => [ 'received' => true ], 'status' => 200 ],
		] );

		$blob = new Blob( 'hello world', 'test.txt', 'txt', 'text/plain' );

		$config = [
			'host'     => 'https://example.com',
			'endpoint' => '/upload',
			'request'  => [
				'method'   => 'POST',
				'format'   => 'file',
				'transport' => 'body',
			],
		];

		$result = $mock->send( $config, $blob );

		$this->assertTrue( $result->isSuccess() );

		$requests = MockHttp::getMockRequests();
		$this->assertCount( 1, $requests );
		$this->assertEquals( 'POST', $requests[0]['method'] );
		$this->assertEquals( 'https://example.com/upload', $requests[0]['url'] );

		$options = $requests[0]['options'];
		$this->assertEquals( 'text/plain', $options['headers']['Content-Type'] );

		// body is a resource (fopen of temp file), read it back
		rewind( $options['body'] );
		$this->assertEquals( 'hello world', stream_get_contents( $options['body'] ) );
	}

	public function testSendSingleBlobWithNullMimeType(): void
	{
		$mock = $this->getMockHttp();
		$mock::primeMockResponses( [
			[ 'body' => [ 'received' => true ], 'status' => 200 ],
		] );

		$blob = new Blob( 'binary data', 'unknown', '' );

		$config = [
			'host'     => 'https://example.com',
			'endpoint' => '/upload',
			'request'  => [
				'method'   => 'POST',
				'format'   => 'file',
				'transport' => 'body',
			],
		];

		$mock->send( $config, $blob );

		$requests = MockHttp::getMockRequests();
		$this->assertEquals( 'application/octet-stream', $requests[0]['options']['headers']['Content-Type'] );
	}

	public function testSendMultipleBlobsAsMultipart(): void
	{
		$mock = $this->getMockHttp();
		$mock::primeMockResponses( [
			[ 'body' => [ 'received' => true ], 'status' => 200 ],
		] );

		$blobA = new Blob( 'content A', 'fileA.txt', 'txt', 'text/plain' );
		$blobB = new Blob( 'content B', 'fileB.pdf', 'pdf', 'application/pdf' );

		$data = ExecuteData::create( [
			'fileA' => $blobA,
			'fileB' => $blobB,
		] );

		$config = [
			'host'     => 'https://example.com',
			'endpoint' => '/upload-multiple',
			'request'  => [
				'method'   => 'POST',
				'format'   => 'file',
				'transport' => 'body',
			],
		];

		$result = $mock->send( $config, $data );

		$this->assertTrue( $result->isSuccess() );

		$requests = MockHttp::getMockRequests();
		$this->assertCount( 1, $requests );

		$options = $requests[0]['options'];
		$body = $options['body'];
		$this->assertInstanceOf( FormDataPart::class, $body );
	}

	public function testSendMixedBlobAndString(): void
	{
		$mock = $this->getMockHttp();
		$mock::primeMockResponses( [
			[ 'body' => [ 'received' => true ], 'status' => 200 ],
		] );

		$blob = new Blob( 'file content', 'data.csv', 'csv', 'text/csv' );

		$data = ExecuteData::create( [
			'file'  => $blob,
			'name'  => 'test',
		] );

		$config = [
			'host'     => 'https://example.com',
			'endpoint' => '/upload-mixed',
			'request'  => [
				'method'   => 'POST',
				'format'   => 'file',
				'transport' => 'body',
			],
		];

		$mock->send( $config, $data );

		$requests = MockHttp::getMockRequests();
		$body = $requests[0]['options']['body'];
		$this->assertInstanceOf( FormDataPart::class, $body );
	}

	public function testSendFlatDataWithBlob(): void
	{
		$mock = $this->getMockHttp();
		$mock::primeMockResponses( [
			[ 'body' => [ 'received' => true ], 'status' => 200 ],
		] );

		$blob = new Blob( 'flat content', 'flat.txt', 'txt', 'text/plain' );

		// Flat data with a Blob (mimics what ExecuteData::create() produces)
		$data = ExecuteData::create( [
			'file'  => $blob,
			'name'  => 'test',
		] );

		$config = [
			'host'     => 'https://example.com',
			'endpoint' => '/upload-flat',
			'request'  => [
				'method'   => 'POST',
				'format'   => 'file',
				'transport' => 'body',
			],
		];

		$mock->send( $config, $data );

		$requests = MockHttp::getMockRequests();
		$body = $requests[0]['options']['body'];
		$this->assertInstanceOf( FormDataPart::class, $body );
	}

	public function testSendPlainStringBypassesFileLogic(): void
	{
		$mock = $this->getMockHttp();
		$mock::primeMockResponses( [
			[ 'body' => [ 'received' => true ], 'status' => 200 ],
		] );

		$config = [
			'host'     => 'https://example.com',
			'endpoint' => '/upload',
			'request'  => [
				'method'   => 'POST',
				'format'   => 'file',
				'transport' => 'body',
			],
		];

		$mock->send( $config, 'plain text body' );

		$requests = MockHttp::getMockRequests();
		// No Blob detected → body stays as the raw data
		$this->assertEquals( 'plain text body', $requests[0]['options']['data'] );
	}

	// ── Retrieve (download) ────────────────────────────────────────────────────

	public function testRetrieveSingleFileWithFilenameHeader(): void
	{
		$mock = $this->getMockHttp();
		$mock::primeMockResponses( [
			[
				'body' => 'file contents here',
				'status' => 200,
				'headers' => [
					'content-disposition' => 'attachment; filename="report.pdf"',
					'content-type'        => 'application/pdf',
				],
			],
		] );

		$config = [
			'host'     => 'https://example.com',
			'endpoint' => '/download',
			'request'  => [
				'method' => 'GET',
			],
			'response' => [
				'format' => 'file',
			],
		];

		$result = $mock->retrieve( $config );

		$blob = ResourceData::create( $result->getData() )->get();
		$this->assertInstanceOf( Blob::class, $blob );
		$this->assertEquals( 'file contents here', $blob->getContents() );
		$this->assertEquals( 'report.pdf', $blob->getFilename() );
		$this->assertEquals( 'application/pdf', $blob->getMimeType() );
	}

	public function testRetrieveFileWithUtf8Filename(): void
	{
		$mock = $this->getMockHttp();
		$mock::primeMockResponses( [
			[
				'body' => 'utf8 content',
				'status' => 200,
				'headers' => [
					'content-disposition' => "attachment; filename*=UTF-8''%E6%96%87%E4%BB%B6.pdf",
					'content-type'        => 'application/pdf',
				],
			],
		] );

		$config = [
			'host'     => 'https://example.com',
			'endpoint' => '/download',
			'request'  => [
				'method' => 'GET',
			],
			'response' => [
				'format' => 'file',
			],
		];

		$result = $mock->retrieve( $config );

		$blob = ResourceData::create( $result->getData() )->get();
		$this->assertInstanceOf( Blob::class, $blob );
		$this->assertEquals( '文件.pdf', $blob->getFilename() );
	}

	public function testRetrieveFileWithQuotedFilename(): void
	{
		$mock = $this->getMockHttp();
		$mock::primeMockResponses( [
			[
				'body' => 'quoted filename content',
				'status' => 200,
				'headers' => [
					'content-disposition' => 'attachment; filename="my report (final).pdf"',
					'content-type'        => 'application/pdf',
				],
			],
		] );

		$config = [
			'host'     => 'https://example.com',
			'endpoint' => '/download',
			'request'  => [
				'method' => 'GET',
			],
			'response' => [
				'format' => 'file',
			],
		];

		$result = $mock->retrieve( $config );

		$blob = ResourceData::create( $result->getData() )->get();
		$this->assertInstanceOf( Blob::class, $blob );
		$this->assertEquals( 'my report (final).pdf', $blob->getFilename() );
	}

	public function testRetrieveFileWithoutFilenameHeader(): void
	{
		$mock = $this->getMockHttp();
		$mock::primeMockResponses( [
			[
				'body' => 'no filename',
				'status' => 200,
				'headers' => [
					'content-type' => 'application/octet-stream',
				],
			],
		] );

		$config = [
			'host'     => 'https://example.com',
			'endpoint' => '/download',
			'request'  => [
				'method' => 'GET',
			],
			'response' => [
				'format' => 'file',
			],
		];

		$result = $mock->retrieve( $config );

		$blob = ResourceData::create( $result->getData() )->get();
		$this->assertInstanceOf( Blob::class, $blob );
		$this->assertEquals( 'downloaded', $blob->getFilename() );
	}

	public function testRetrieveWithoutFileFormatReturnsRawContent(): void
	{
		$mock = $this->getMockHttp();
		$mock::primeMockResponses( [
			[
				'body' => [ 'key' => 'value' ],
				'status' => 200,
			],
		] );

		$config = [
			'host'     => 'https://example.com',
			'endpoint' => '/json',
			'request'  => [
				'method' => 'GET',
			],
			'response' => [
				'format' => 'json',
			],
		];

		$result = $mock->retrieve( $config );
		$blob = ResourceData::create( $result->getData() )->get();

		// Without file format, returns the raw body (decoded JSON)
		$this->assertNotInstanceOf( Blob::class, $blob );
	}

	// ── Edge cases ─────────────────────────────────────────────────────────────

	public function testSendEmptyBlob(): void
	{
		$mock = $this->getMockHttp();
		$mock::primeMockResponses( [
			[ 'body' => [ 'received' => true ], 'status' => 200 ],
		] );

		$blob = new Blob( '', 'empty.txt', 'txt', 'text/plain' );

		$config = [
			'host'     => 'https://example.com',
			'endpoint' => '/upload',
			'request'  => [
				'method'   => 'POST',
				'format'   => 'file',
				'transport' => 'body',
			],
		];

		$mock->send( $config, $blob );

		$requests = MockHttp::getMockRequests();
		$this->assertEquals( 'text/plain', $requests[0]['options']['headers']['Content-Type'] );

		rewind( $requests[0]['options']['body'] );
		$this->assertEquals( '', stream_get_contents( $requests[0]['options']['body'] ) );
	}

	public function testSendLargeBlobContentPreserved(): void
	{
		$mock = $this->getMockHttp();
		$mock::primeMockResponses( [
			[ 'body' => [ 'received' => true ], 'status' => 200 ],
		] );

		$largeContent = str_repeat( 'ABCD', 1000 );
		$blob = new Blob( $largeContent, 'large.bin', 'bin', 'application/octet-stream' );

		$config = [
			'host'     => 'https://example.com',
			'endpoint' => '/upload',
			'request'  => [
				'method'   => 'POST',
				'format'   => 'file',
				'transport' => 'body',
			],
		];

		$mock->send( $config, $blob );

		$requests = MockHttp::getMockRequests();
		rewind( $requests[0]['options']['body'] );
		$this->assertEquals( $largeContent, stream_get_contents( $requests[0]['options']['body'] ) );
	}

	public function testRetrieveFileWithGetContents(): void
	{
		$mock = $this->getMockHttp();
		$mock::primeMockResponses( [
			[
				'body' => 'getContents test data',
				'status' => 200,
				'headers' => [
					'content-disposition' => 'attachment; filename="data.txt"',
					'content-type'        => 'text/plain',
				],
			],
		] );

		$config = [
			'host'     => 'https://example.com',
			'endpoint' => '/download',
			'request'  => [
				'method' => 'GET',
			],
			'response' => [
				'format' => 'file',
			],
		];

		$result = $mock->retrieve( $config );
		$blob = ResourceData::create( $result->getData() )->get();

		$this->assertInstanceOf( Blob::class, $blob );
		$this->assertEquals( 'getContents test data', $blob->getContents() );
	}

	public function testSendBlobDetectedViaIterableCheck(): void
	{
		$mock = $this->getMockHttp();
		$mock::primeMockResponses( [
			[ 'body' => [ 'received' => true ], 'status' => 200 ],
		] );

		$blob = new Blob( 'test', 'test.txt', 'txt', 'text/plain' );

		// Send as iterable (array) — should detect Blob and use multipart
		$config = [
			'host'     => 'https://example.com',
			'endpoint' => '/upload',
			'request'  => [
				'method'   => 'POST',
				'format'   => 'file',
				'transport' => 'body',
			],
		];

		$mock->send( $config, [ 'file' => $blob ] );

		$requests = MockHttp::getMockRequests();
		$body = $requests[0]['options']['body'];
		$this->assertInstanceOf( FormDataPart::class, $body );
	}

	public function testSendBlobSingleInstanceDetected(): void
	{
		$mock = $this->getMockHttp();
		$mock::primeMockResponses( [
			[ 'body' => [ 'received' => true ], 'status' => 200 ],
		] );

		$blob = new Blob( 'single blob', 'single.txt', 'txt', 'text/plain' );

		$config = [
			'host'     => 'https://example.com',
			'endpoint' => '/upload',
			'request'  => [
				'method'   => 'POST',
				'format'   => 'file',
				'transport' => 'body',
			],
		];

		$mock->send( $config, $blob );

		$requests = MockHttp::getMockRequests();
		// Single Blob → raw stream resource
		$this->assertTrue( is_resource( $requests[0]['options']['body'] ) );
		rewind( $requests[0]['options']['body'] );
		$this->assertEquals( 'single blob', stream_get_contents( $requests[0]['options']['body'] ) );
	}
}
