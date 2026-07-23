<?php

namespace SyncEngine\Webservice;

use SyncEngine\Exception\InvalidConfigException;
use SyncEngine\Form\Fields\Collection\FieldCollection;
use SyncEngine\Model\ConnectionModel;
use SyncEngine\Model\WebserviceModel;
use SyncEngine\Webservice\Helper\Result;
use SyncEngine\Webservice\Type\SoapWebserviceType;
use Symfony\Component\Filesystem\Filesystem;

class Soap extends WebserviceModel
{
	public function __construct()
	{
		parent::__construct();

		$this->type        = SoapWebserviceType::TYPE;
		$this->icon        = 'webservice-soap';
		$this->name        = $this->trans( 'SOAP' );
		$this->description = $this->trans( 'Connect using SOAP' );
	}

	public function getAuthFields(): FieldCollection
	{
		if ( ! class_exists( 'SoapClient' ) ) {
			return new FieldCollection(
				[
					'warning' => [
						'type' => 'html',
						'html' => '<div class="alert alert-warning">' . $this->trans( 'SOAP extension is not available' ) . '</div>',
					]
				]
			);
		}

		return new FieldCollection( [
			'host' => [
				'label' => $this->trans( 'Host' ),
				'type'  => 'text',
			],
		] );
	}

	protected function getSoapFields( array $defaults = [] ): FieldCollection
	{
		return new FieldCollection( [
			'endpoint'          => [
				'label' => $this->trans( 'Endpoint' ),
				'type'  => 'text',
			],
			'wsdl_mode'         => [
				'label'    => $this->trans( 'WSDL mode' ),
				'type'     => 'switch',
				'expanded' => false,
				'help'     => $this->trans( 'Will this connection use WSDL file format?' ),
			],
			'wsdl_url'          => [
				'label'      => $this->trans( 'WSDL file url' ),
				'type'       => 'text',
				'help'       => $this->trans( 'Link to WSDL format that will be filled in for this soap connection' ),
				'conditions' => [
					'wsdl_mode' => true,
				],
			],
			'uri'               => [
				'label'      => $this->trans( 'SOAP service namespace' ),
				'type'       => 'text',
				'help'       => $this->trans( 'Target namespace of the SOAP service. Required when WSDL mode is disabled.' ),
				'conditions' => [
					'wsdl_mode' => [ 'operator' => '!=', 'compare' => true ],
				],
			],
			'soap_version'      => [
				'label'    => $this->trans( 'SOAP version' ),
				'type'     => 'select',
				'choices'  => [
					''  => $this->trans( 'Default (PHP default)' ),
					'1.1' => 'SOAP 1.1',
					'1.2' => 'SOAP 1.2',
				],
				'help'     => $this->trans( 'SOAP protocol version. Leave empty for PHP default.' ),
			],
			'soap_action'       => [
				'label'    => $this->trans( 'SOAPAction header' ),
				'type'     => 'text',
				'help'     => $this->trans( 'Optional SOAPAction HTTP header value. Required by some SOAP servers.' ),
			],
			'compression'       => [
				'label'    => $this->trans( 'Compression' ),
				'type'     => 'select',
				'choices'  => [
					''       => $this->trans( 'None' ),
					'gzip'    => 'gzip',
					'deflate' => 'deflate',
				],
				'help'     => $this->trans( 'Compress SOAP requests and accept compressed responses.' ),
			],
			'connection_timeout' => [
				'label'     => $this->trans( 'Connection timeout' ),
				'type'      => 'number',
				'placeholder' => ini_get( 'default_socket_timeout' ),
				'postfix'   => 'seconds',
				'help'      => $this->trans( 'Maximum time in seconds for the connection to be established.' ),
			],
			'soap_initiate'     => [
				'label' => $this->trans( 'Soap function from WSDL' ),
				'type'  => 'text',
			],
			'call_data'         => [
				'label'     => $this->trans( 'Data to fill WSDL to make the call' ),
				'type'      => 'params',
				'default'   => $defaults['call_data'] ?? null,
				'collapsed' => false,
			],
			'headers'           => [
				'label'     => $this->trans( 'Soap headers' ),
				'type'      => 'grid',
				'columns'   => [
					'url'   => [ 'label' => "url", 'help' => "Not required" ],
					'key'   => [ 'label' => "key" ],
					'value' => [ 'label' => "value" ],
				],
				'default'   => $defaults['headers'] ?? null,
				'collapsed' => true,
			],
		] );
	}

	public function getFields( array $defaults = [] ): FieldCollection
	{
		return $this->getSoapFields( $defaults )->merge( parent::getFields( $defaults ) );
	}

	public function getRetrieveFields( array $defaults = [] ): FieldCollection
	{
		return $this->getFields( $defaults );
	}

	public function getSendFields( array $defaults = [] ): FieldCollection
	{
		return $this->getFields( $defaults );
	}

	public function getRequestUrl( array $config ): string
	{
		$location = $this->getLocation( $config );
		if ( $location !== null ) {
			return $location;
		}

		if ( ! empty( $config['wsdl_mode'] ) ) {
			return $config['wsdl_url'] ?? $config['host'] ?? '';
		}

		return $config['host'] . ( $config['endpoint'] ?? '' );
	}

	/**
	 * Build SoapClient constructor options from config.
	 */
	protected function getSoapClientOptions( array $config ): array
	{
		$options = [
			'trace'      => true,
			'exceptions' => true,
			'features'   => SOAP_SINGLE_ELEMENT_ARRAYS,
		];

		// SOAP version.
		if ( ! empty( $config['soap_version'] ) ) {
			$versionMap = [
				'1.1' => 'SOAP_1_1',
				'1.2' => 'SOAP_1_2',
			];
			if ( isset( $versionMap[ $config['soap_version'] ] ) && defined( $versionMap[ $config['soap_version'] ] ) ) {
				$options['soap_version'] = constant( $versionMap[ $config['soap_version'] ] );
			}
		}
		// Connection timeout.
		if ( ! empty( $config['connection_timeout'] ) ) {
			$options['connection_timeout'] = (int) $config['connection_timeout'];
		}

		if ( ! empty( $config['wsdl_mode'] ) ) {
			$options['cache_wsdl'] = WSDL_CACHE_DISK;
		}

		if ( empty( $config['wsdl_mode'] ) && ! empty( $config['uri'] ) ) {
			$options['uri'] = $config['uri'];
		}

		// Compression support.
		if ( ! empty( $config['compression'] ) ) {
			switch ( strtolower( $config['compression'] ) ) {
				case 'gzip':
					$options['compression'] = SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP | 9;
				break;
				case 'deflate':
					$options['compression'] = SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_DEFLATE | 9;
				break;
			}
		}

		return $options;
	}

	protected function createSoapClient( array $config ): \SoapClient
	{
		$wsdlUrl = $this->getWsdlUrl( $config );
		$options = $this->getSoapClientOptions( $config );
		$location = $this->getLocation( $config );

		if ( ! empty( $config['wsdl_mode'] ) && empty( $wsdlUrl ) ) {
			throw new InvalidConfigException( 'A WSDL URL is required in WSDL mode.' );
		}

		if ( empty( $config['wsdl_mode'] ) && empty( $options['uri'] ) ) {
			throw new InvalidConfigException( 'A SOAP service namespace is required in non-WSDL mode.' );
		}

		if ( empty( $config['wsdl_mode'] ) && empty( $location ) ) {
			throw new InvalidConfigException( 'A SOAP endpoint is required in non-WSDL mode.' );
		}

		if ( $location !== null ) {
			$options['location'] = $location;
		}

		if ( empty( $config['wsdl_mode'] ) ) {
			return new \SoapClient( $wsdlUrl, $options );
		}

		$cacheDirectory = $this->getWsdlCacheDirectory( $config, $wsdlUrl );
		$this->prepareWsdlCacheDirectory( $cacheDirectory );

		$previousCacheDirectory = ini_get( 'soap.wsdl_cache_dir' );
		$previousCacheEnabled = ini_get( 'soap.wsdl_cache_enabled' );
		if (
			ini_set( 'soap.wsdl_cache_dir', $cacheDirectory ) === false
			|| ini_set( 'soap.wsdl_cache_enabled', '1' ) === false
		) {
			ini_set( 'soap.wsdl_cache_dir', (string) $previousCacheDirectory );
			ini_set( 'soap.wsdl_cache_enabled', (string) $previousCacheEnabled );
			$options['cache_wsdl'] = WSDL_CACHE_NONE;
			return new \SoapClient( $wsdlUrl, $options );
		}

		try {
			return new \SoapClient( $wsdlUrl, $options );
		} finally {
			ini_set( 'soap.wsdl_cache_dir', (string) $previousCacheDirectory );
			ini_set( 'soap.wsdl_cache_enabled', (string) $previousCacheEnabled );
		}
	}

	protected function getWsdlCacheDirectory( array $config, string $wsdlUrl ): string
	{
		$connection = $config['connection'] ?? null;
		$connectionId = $connection instanceof ConnectionModel ? $connection->getEntity()?->getId() : null;
		$cacheKey = $connectionId === null
			? 'wsdl-' . hash( 'sha256', $wsdlUrl )
			: 'connection-' . $connectionId;

		return $this->getParameter( 'dir.var' )
			. DIRECTORY_SEPARATOR . 'wsdl-cache'
			. DIRECTORY_SEPARATOR . $cacheKey
			. DIRECTORY_SEPARATOR . 'php-' . PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;
	}

	protected function prepareWsdlCacheDirectory( string $cacheDirectory ): void
	{
		$filesystem = new Filesystem();
		$filesystem->mkdir( $cacheDirectory, 0775 );

		$ttl = max( 0, (int) ini_get( 'soap.wsdl_cache_ttl' ) );
		$expiresBefore = time() - $ttl;
		$cacheRoot = dirname( $cacheDirectory, 2 );
		$iterator = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator( $cacheRoot, \FilesystemIterator::SKIP_DOTS ),
			\RecursiveIteratorIterator::LEAVES_ONLY
		);

		foreach ( $iterator as $file ) {
			if ( $file->isFile() && ! $file->isLink() && $file->getMTime() < $expiresBefore ) {
				$filesystem->remove( $file->getPathname() );
			}
		}
	}

	/**
	 * Get the WSDL URL or null for non-WSDL mode.
	 */
	protected function getWsdlUrl( array $config ): ?string
	{
		return empty( $config['wsdl_mode'] ) ? null : ( $config['wsdl_url'] ?? null );
	}

	/**
	 * Get the SOAP client location.
	 */
	protected function getLocation( array $config ): ?string
	{
		if ( ! empty( $config['url'] ) ) {
			return $config['url'];
		}

		if ( ! empty( $config['wsdl_mode'] ) ) {
			return null;
		}

		return ( $config['host'] ?? '' ) . ( $config['endpoint'] ?? '' );
	}

	public function setSoapHeaders( array $config ): array
	{
		$headers = [];

		if ( ! empty( $config['headers'] ) ) {
			foreach ( $config['headers'] as $header ) {
				if ( empty( $header['key'] ) ) {
					continue;
				}

				$headers[] = new \SoapHeader(
					! empty( $header['url'] ) ? $header['url'] : 'http://soapinterop.org/echoheader/',
					$header['key'],
					$header['value'] ?? null
				);
			}
		}

		return $headers;
	}

	protected function getSoapCallOptions( array $config ): array
	{
		$options = [];

		if ( ! empty( $config['soap_action'] ) ) {
			$options['soapaction'] = $config['soap_action'];
		}

		return $options;
	}

	public function getRequestFields( array $defaults = [] ): FieldCollection
	{
		return new FieldCollection( [
			'headers' => [
				'label'     => $this->trans( 'Soap headers' ),
				'type'      => 'grid',
				'columns'   => [
					'url'   => [ 'label' => "url", 'help' => "Not required" ],
					'key'   => [ 'label' => "key" ],
					'value' => [ 'label' => "value" ],
				],
				'default'   => $defaults['headers'] ?? null,
				'collapsed' => true,
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

	public function retrieve( array $config, $data = null ): Result
	{
		$method = $config['soap_initiate'] ?? '';

		return $this->executeRequest( $config, [ $method => $config['call_data'] ?? [] ] );
	}

	public function send( array $config, $data ): Result
	{
		$method = $config['soap_initiate'] ?? '';

		// If body is configured, encode $data using the request format codec.
		// This allows dynamic data from automation rows to be passed into the SOAP call.
		if ( ! empty( $config['request']['body'] ) && $data !== null ) {
			$format = $config['request']['format'] ?? null;
			if ( $format ) {
				$encoded = $this->encodeFormat( $format, $data, $config['request'] );
				$args    = [ $method => $encoded ];
			} else {
				$args = [ $method => $data ];
			}
		} else {
			// Fall back to static call_data config.
			$args = [ $method => $config['call_data'] ?? [] ];
		}

		return $this->executeRequest( $config, $args );
	}

	protected function executeRequest( array $config, array $args ): Result
	{
		$soapClient = null;

		try {
			if ( empty( $config['soap_initiate'] ) ) {
				throw new InvalidConfigException( 'A SOAP operation is required.' );
			}

			$soapClient = $this->createSoapClient( $config );
			$soapClient->__setSoapHeaders( $this->setSoapHeaders( $config ) );

			return $this->request( $soapClient, $config, $args );
		} catch ( \Throwable $e ) {
			return new Result( false, $e, [
				'SoapRequest'  => $soapClient?->__getLastRequest(),
				'SoapResponse' => $soapClient?->__getLastResponse(),
				'Config'       => $config,
			] );
		}
	}

	protected function request( \SoapClient $soapClient, array $config, array $args ): Result
	{
		try {
			$method = $config['soap_initiate'] ?? '';
			$result = $soapClient->__soapCall( $method, $args, $this->getSoapCallOptions( $config ) );

			// Apply response format decoding if configured.
			// The raw SOAP response XML is always available via __getLastResponse().
			$decodedResult = $result;
			if ( ! empty( $config['response']['format'] ) ) {
				$rawResponse = $soapClient->__getLastResponse();
				if ( ! empty( $rawResponse ) ) {
					$codec = ( new \SyncEngine\Service\DataFormatter() )->getEncoder(
						$config['response']['format'],
						$config['response']
					);
					if ( $codec ) {
						$decodedResult = $this->decodeFormat( $codec, $rawResponse, $config['response'] );
					}
				}
			}

			return new Result( $decodedResult, true, [
				'SoapRequest'  => $soapClient->__getLastRequest(),
				'SoapResponse' => $soapClient->__getLastResponse(),
			] );
		} catch ( \Throwable $e ) {
			return new Result( false, $e, [
				'SoapRequest'  => $soapClient->__getLastRequest(),
				'SoapResponse' => $soapClient->__getLastResponse(),
				'Config'       => $config,
			] );
		}
	}
}
