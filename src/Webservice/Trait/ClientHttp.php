<?php

namespace SyncEngine\Webservice\Trait;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\RetryableHttpClient;
use Symfony\Component\Mime\MimeTypes;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use SyncEngine\Codec\File;
use SyncEngine\Form\Fields\Collection\FieldCollection;
use SyncEngine\Model\Trait\Format;
use SyncEngine\Service\BlobStore;
use SyncEngine\Structure\Data\ResourceData;
use SyncEngine\Structure\ValueObject\Blob;

trait ClientHttp
{
	use Format;

	protected HttpClientInterface $client;

	public function getClient( array $config = [] ): HttpClientInterface
	{
		$options = [];
		if ( $config ) {
			$options = $this->getClientOptions( $config );
		}

		if ( empty( $this->client ) ) {
			$this->setClient( new RetryableHttpClient( HttpClient::create( $options ) ) );
		} elseif ( $options ) {
			$this->setClient( $this->client->withOptions( $options ) );
		}

		return $this->client;
	}

	public function setClient( $client ): void
	{
		$this->client = $client;
	}

	/**
	 * @param  array{ query: array, headers: array, body: array, format: string|array }  $config
	 *
	 * @return array
	 */
	public function getClientOptions( array $config = [] ): array
	{
		$options = [];

		if ( ! empty( $config['query'] ) ) {
			$options['query'] = $this->parseRequestParams( $config['query'], $config['format'] ?? '' );
		}

		if ( ! empty( $config['headers'] ) ) {
			$options['headers'] = $this->parseRequestParams( $config['headers'], $config['format'] ?? '' );
		}

		if ( ! empty( $config['body'] ) ) {
			$options['body'] = $this->parseRequestParams( $config['body'], $config['format'] ?? '' );
		}

		// Auto-set content type.
		if ( ! isset( $options['headers']['Content-Type'] ) && ! empty( $config['format'] ) ) {
			$formatContentType = $this->getFormatContentType( $config['format'], $config );
			if ( $formatContentType ) {
				$options['headers']['Content-Type'] = $formatContentType;
			}
		}

		if ( ! empty( $config['timeout'] ) ) {
			$options['timeout'] = $config['timeout'];
		}

		return $options;
	}

	public function parseRequestParams( $config, $format = '' ): mixed
	{
		if ( is_string( $config ) && ! empty( $format ) ) {
			$config = $this->decodeFormat( $format, $config );
		}

		if ( ! is_array( $config ) ) {
			return $config;
		}

		$params = new ResourceData( [] );

		foreach ( $config as $key => $value ) {
			if ( is_iterable( $value ) ) {
				$value = ResourceData::create( $value )->normalize();
			}

			if ( isset( $value['key'] ) ) {
				$key   = $value['key'];
				$value = $value['value'];
			}

			// @todo Move this to ResourceData?
			$append = str_ends_with( $key, '[]' );
			if ( $append ) {
				$key          = substr( $key, 0, - 2 );
				$collection   = (array) ( $params[ $key ] ?? [] );
				$collection[] = $value;
				$value        = $collection;
			}

			$params[ $key ] = $value;
		}

		return $params->get();
	}

	protected function retrieveUpload( ResponseInterface $response, File $codec ): array
	{
		// Auto-detect filename and mime type from response headers
		$filename  = null;
		$mimeType  = null;
		$extension = null;

		$headers = $response->getHeaders();

		if ( isset( $headers['content-type'][0] ) ) {
			$mimeType = $headers['content-type'][0];
		}

		if ( isset( $headers['content-disposition'][0] ) ) {
			// Extract filename from Content-Disposition: attachment; filename="file.ext"
			if ( preg_match(
				'/filename\*?=(?:UTF-8\'\')?["\']?([^;"\']+)["\']?/i',
				$headers['content-disposition'][0],
				$matches
			) ) {
				$filename = urldecode( trim( $matches[1] ) );
			}
		}

		if ( $filename ) {
			$info      = pathinfo( $filename );
			$filename  = $info['filename'];
			$extension = $info['extension'] ?? null;
		} else {
			$info = pathinfo( $response?->getInfo( 'url' ) );
			if ( isset( $info['extension'] )
			     && in_array(
				     $info['extension'],
				     ( new MimeTypes() )->getExtensions( $mimeType )
			     ) ) {
				$filename  = $info['basename'];
				$extension = $info['extension'];
			}
		}

		$blob = $codec->decode(
			$response->getContent(),
			'file',
			[
				'filename'  => $filename ?? 'downloaded',
				'extension' => $extension,
				'mimeType'  => $mimeType,
			]
		);

		BlobStore::getInstance()->register( $blob );

		return $blob->normalize();
	}

	/**
	 * File uploads support a single file or multiple files.
	 * It does not allow recursive Blobs in nested arrays, as that would require a more complex multipart structure.
	 */
	protected function prepareUpload( array $options, $data ): array
	{
		// Make sure we rehydrate Blobs from their normalized marker array.
		$data = ResourceData::create( $data )->get();

		// Single file: Send as raw stream
		if ( $data instanceof Blob ) {
			$options['headers']['Content-Type'] = $data->getMimeType() ?? 'application/octet-stream';
			$options['body']                    = $data->getResource();
		} elseif ( is_string( $data ) ) {
			$options['headers']['Content-Type'] = 'application/octet-stream';
			$options['body']                    = $data;
		} // Multiple files: Send as MultipartStream (flat only)
		elseif ( is_iterable( $data ) ) {
			$parts = [];
			foreach ( $data as $key => $value ) {
				if ( $value instanceof Blob ) {
					$parts[ $key ] = new DataPart(
						$value->getResource(), $value->getFilename(), $value->getMimeType()
					);
				} else {
					$parts[ $key ] = $value;
				}
			}
			$options['body']    = new FormDataPart( $parts );
			$options['headers'] = array_merge( $options['headers'], $options['body']->getPreparedHeaders()->toArray() );
		}

		return $options;
	}

	public function getRequestFields( $defaults = [] ): FieldCollection
	{
		return new FieldCollection( [
			'method'  => [
				'label'   => $this->trans( 'Request method' ),
				'type'    => 'select',
				'name'    => 'method',
				'default' => $defaults['method'] ?? null,
				'choices' => [
					'GET'     => 'GET',
					'POST'    => 'POST',
					'PUT'     => 'PUT',
					'PATCH'   => 'PATCH',
					'DELETE'  => 'DELETE',
					'HEAD'    => 'HEAD',
					'CONNECT' => 'CONNECT',
					'OPTIONS' => 'OPTION',
					'TRACE'   => 'TRACE',
				],
			],
			'timeout' => [
				'label'       => $this->trans( 'Request timeout' ),
				'type'        => 'number',
				'default'     => $defaults['timeout'] ?? null,
				// @see https://symfony.com/doc/current/http_client.html#dealing-with-network-timeouts
				'placeholder' => ini_get( 'default_socket_timeout' ),
				'postfix'     => 'seconds',
			],
			'query'   => [
				'label'     => $this->trans( 'Request query' ),
				'type'      => 'params',
				'default'   => $defaults['query'] ?? null,
				'collapsed' => true,
				'taggable'  => true,
			],
			'headers' => [
				'label'     => $this->trans( 'Request header' ),
				'type'      => 'params',
				'default'   => $defaults['headers'] ?? null,
				'collapsed' => true,
				'taggable'  => true,
			],
			'body'    => [
				'label'        => $this->trans( 'Request body' ),
				'type'         => 'params',
				'formats'      => $this->getFormatEncodeField(),
				'default'      => $defaults['body'] ?? null,
				'collapsed'    => true,
				'customizable' => true,
				'taggable'     => true,
			],
		] );
	}
}
