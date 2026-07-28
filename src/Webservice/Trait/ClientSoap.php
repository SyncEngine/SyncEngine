<?php

namespace SyncEngine\Webservice\Trait;

use SyncEngine\Exception\InvalidConfigException;
use SyncEngine\Form\Fields\Collection\FieldCollection;
use SyncEngine\Model\ConnectionModel;
use SyncEngine\Model\Trait\Format;

trait ClientSoap
{
	use Format;

	protected \SoapClient $client;

	public function getClient( array $config = [] ): \SoapClient
	{
		// @todo Reuse existing client if WSDL is the same?
		$this->client = $this->createSoapClient( $config );
		$this->client->__setSoapHeaders( $this->setSoapHeaders( $config ) );
		return $this->client;
	}

	public function setClient( $client ): void
	{
		$this->client = $client;
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

		return new \SoapClient( $wsdlUrl, $options );
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
		if ( ! empty( $config['wsdl_mode'] ) ) {
			return null;
		}

		return ( $config['host'] ?? '' ) . ( $config['endpoint'] ?? '' );
	}

	public function setSoapHeaders( array $config ): array
	{
		$headers = [];

		$configHeaders = array_merge( $config['headers'] ?? [], $config['request']['headers'] ?? []  );

		if ( ! empty( $configHeaders ) ) {
			foreach ( $configHeaders as $header ) {
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

	/**
	 * Build SoapClient constructor options from config.
	 */
	protected function getSoapClientOptions( array $config ): array
	{
		$options = [
			'trace'      => true,
			'exceptions' => true,
			'features'   => \SOAP_SINGLE_ELEMENT_ARRAYS,
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

		if ( ! empty( $config['wsdl_mode'] ) && ! empty( $config['wsdl_cache'] ) ) {
			$cacheConstant = match( $config['wsdl_cache'] ) {
				'disk', 'file' => 'WSDL_CACHE_DISK',
				'memory' => 'WSDL_CACHE_MEMORY',
				'both' => 'WSDL_CACHE_BOTH',
				default => 'WSDL_CACHE_NONE', // 'none'
			};
			if ( isset( $cacheConstant ) && defined( $cacheConstant ) ) {
				$options['cache_wsdl'] = constant( $cacheConstant );
			}

			// @todo Find a method to place WSDL cache in a custom directory. See bottom of this file for a proposed method.
			//$cacheDirectory = $this->getWsdlCacheDirectory( $config, $wsdlUrl );
			// WSDL caching options.
			// NOTE: WSDL caching strategy is still under consideration.
			// Options under evaluation:
			//   1. Connection data — store WSDL in $connection->getData() (DB bloat, no file-based cache benefit)
			//   2. PHP temp — sys_get_temp_dir() (shared, collision risk, no per-connection isolation)
			//   3. Trace dir — ephemeral, defeats caching purpose
			//   4. Dedicated project cache dir (var/wsdl-cache/{connection_id}/) (persistent, per-connection, needs cleanup)
			//   5. Custom storage entity — future: pluggable file or db-table storage layer
			// Default to WSDL_CACHE_NONE since SyncEngine manages WSDL URLs via the wsdl_url field.
			// Directory creation and cleanup for custom cache_wsdl_dir is deferred until strategy is decided.
		}

		if ( empty( $config['wsdl_mode'] ) && ! empty( $config['uri'] ) ) {
			$options['uri'] = $config['uri'];
		}

		// Compression support.
		if ( ! empty( $config['compression'] ) ) {
			switch ( strtolower( $config['compression'] ) ) {
				case 'gzip':
					$options['compression'] = \SOAP_COMPRESSION_ACCEPT | \SOAP_COMPRESSION_GZIP | 9;
				break;
				case 'deflate':
					$options['compression'] = \SOAP_COMPRESSION_ACCEPT | \SOAP_COMPRESSION_DEFLATE | 9;
				break;
			}
		}

		return $options;
	}

	protected function getSoapCallOptions( array $config ): array
	{
		$options = [];

		if ( ! empty( $config['soap_action'] ) ) {
			$options['soapaction'] = $config['soap_action'];
		}

		return $options;
	}

	protected function getSoapOperation( array $config ): string
	{
		return $config['request']['soap_operation'] ?? $config['soap_operation'] ?? '';
	}

	protected function getSoapTransport( array $config, $data = null ): null|iterable|string
	{
		if ( ! empty( $config['request']['data_transport'] ) ) {
			$body = is_array( $data ) ? $data : null;
		} else {
			$body = $config['request']['body'] ?? $config['body'] ?? null;
		}

		if ( empty( $body ) && ! is_array( $body ) ) {
			return null;
		}

		$format = $config['request']['format'] ?? null;
		if ( $format ) {
			$body = $this->encodeFormat( $format, $body, $config['request'] );
		} else {
			$body = is_array( $body ) ? $body : null;
		}

		return $body;
	}

	protected function getSoapClientFields( array $defaults = [] ): FieldCollection
	{
		return new FieldCollection( [
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
				'fields'   => [
					'wsdl_url'          => [
						'label'      => $this->trans( 'WSDL file url' ),
						'type'       => 'text',
						'help'       => $this->trans( 'Link to WSDL format that will be filled in for this soap connection' ),
						'conditions' => [
							'wsdl_mode' => true,
						],
					],
					'wsdl_cache'        => [
						'label'      => $this->trans( 'WSDL cache' ),
						'type'       => 'select',
						'choices'    => [
							''       => $this->trans( 'Default (PHP default)' ),
							'none'   => $this->trans( 'No cache' ),
							'memory' => $this->trans( 'Memory cache' ),
							'disk'   => $this->trans( 'Disk/File cache' ),
							'both'   => $this->trans( 'Both' ),
						],
						'conditions' => [
							'wsdl_mode' => true,
						],
					],
					'wsdl_cache_warning' => [
						'label'      => $this->trans( 'WSDL cache warning' ),
						'type'       => 'html',
						'html'       => '<div class="alert alert-warning">' . $this->trans( 'Filebased WSDL cache is enabled. This may cause issues with outdated WSDL files and security due to shared access on the server.' ) . '</div>',
						'conditions' => [
							'wsdl_mode' => true,
							'wsdl_cache' => ['disk', 'both'],
						],
					],
					/*'cache_wsdl_dir'    => [
						'label'    => $this->trans( 'WSDL cache directory' ),
						'type'     => 'text',
						'help'     => $this->trans( 'Directory for WSDL file cache. User-managed, directory creation and cleanup deferred.' ),
						'conditions' => [
							'wsdl_mode' => true,
							'cache_wsdl' => [ 'disk', 'both' ],
						],
						'collapsed' => true,
					],*/
				],
			],
			'uri'               => [
				'label'      => $this->trans( 'SOAP service namespace' ),
				'type'       => 'text',
				'help'       => $this->trans( 'Target namespace of the SOAP service. Required when WSDL mode is disabled.' ),
				'conditions' => [
					'wsdl_mode' => false,
				],
			],
			'soap_action'       => [
				'label'    => $this->trans( 'SOAPAction header' ),
				'type'     => 'text',
				'help'     => $this->trans( 'Optional SOAPAction HTTP header value. Required by some SOAP servers.' ),
			],
			'_client_overrides' => [
				'label'  => $this->trans( 'Advanced' ),
				'description'   => $this->trans( 'Override options for the SOAP connection.' ),
				'collapsed' => true,
				'fields' => $this->getSoapClientFields(),
			],
		] );
	}

	public function getRequestFields( array $defaults = [] ): FieldCollection
	{
		return new FieldCollection( [
			'headers' => [
				'label'     => $this->trans( 'SOAP headers' ),
				'type'      => 'grid',
				'columns'   => [
					'url'   => [ 'label' => "URL", 'help' => "Not required" ],
					'key'   => [ 'label' => "Key" ],
					'value' => [ 'label' => "Value" ],
				],
				'default'   => $defaults['headers'] ?? null,
				'collapsed' => true,
			],
			'soap_operation'     => [
				'label'    => $this->trans( 'SOAP operation name' ),
				'help'     => $this->trans( 'The name of the SOAP operation to call. This is required for both WSDL and non-WSDL mode.' ),
				'type'     => 'text',
				'required' => true,
			],
			'data_transport' => [
				'label'        => $this->trans( 'Use data transport as body?' ),
				'type'         => 'toggle',
			],
			'body'    => [
				'label'        => $this->trans( 'SOAP body' ),
				'type'         => 'params',
				'formats'      => $this->getFormatEncodeField(),
				'default'      => $defaults['body'] ?? null,
				'collapsed'    => true,
				'customizable' => true,
				'taggable'     => true,
				'conditions'   => [ 'data_transport' => false ],
			],
		] );
	}

	// @todo Implement custom cache dir?
	/*protected function getWsdlCacheDirectory( array $config, string $wsdlUrl ): string
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
	}*/
}
