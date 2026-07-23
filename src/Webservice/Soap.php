<?php

namespace SyncEngine\Webservice;

use SyncEngine\Form\Fields\Collection\FieldCollection;
use SyncEngine\Model\WebserviceModel;
use SyncEngine\Webservice\Helper\Result;
use SyncEngine\Webservice\Type\SoapWebserviceType;

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

	public function getRetrieveFields( array $defaults = [] ): FieldCollection
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
					'both'    => 'gzip + deflate',
				],
				'help'     => $this->trans( 'Enable response compression for smaller payloads.' ),
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

	public function getRequestUrl( array $config ): string
	{
		// When using WSDL, the endpoint is embedded in the WSDL URL.
		// Return the WSDL URL for meaningful debug output.
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
			'trace'        => 1,
			'exception'    => 1,
			'features'     => SOAP_SINGLE_ELEMENT_ARRAYS,
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

		// Compression support.
		if ( ! empty( $config['compression'] ) ) {
			switch ( strtolower( $config['compression'] ) ) {
				case 'gzip':
					$options['compression'] = SOAP_COMPRESSION_GZIP;
				break;
				case 'deflate':
					$options['compression'] = SOAP_COMPRESSION_DEFLATE;
				break;
				case 'both':
				case 'gzip+deflate':
					$options['compression'] = SOAP_COMPRESSION_GZIP | SOAP_COMPRESSION_DEFLATE;
				break;
			}
		}
		return $options;
	}

	/**
	 * Get the WSDL URL or null for non-WSDL mode.
	 */
	protected function getWsdlUrl( array $config ): ?string
	{
		return empty( $config['wsdl_mode'] ) ? null : ( $config['wsdl_url'] ?? null );
	}

	/**
	 * Get the SOAP client location (non-WSDL mode).
	 */
	protected function getLocation( array $config ): ?string
	{
		if ( ! empty( $config['wsdl_mode'] ) ) {
			return null;
		}

		return $config['host'] . ( $config['endpoint'] ?? '' );
	}

	public function setSoapHeaders( array $config ): array
	{
		$headers = [];

		if ( ! empty( $config['headers'] ) ) {
			foreach ( $config['headers'] as $header ) {
				$headers[] = new \SoapHeader(
					$header['url'] ?? 'http://soapinterop.org/echoheader/', $header['key'], $header['value']
				);
			}
		}

		return $headers;
	}

	public function getRequestFields( $defaults = [] ): FieldCollection
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
		$wsdl_url = $this->getWsdlUrl( $config );
		$location = $this->getLocation( $config );
		$options  = $this->getSoapClientOptions( $config );

		// For non-WSDL mode, pass location as first argument.
		if ( $wsdl_url === null ) {
			$options['location'] = $location;
			$soapClient = new \SoapClient( null, $options );
		} else {
			$soapClient = new \SoapClient( $wsdl_url, $options );
		}

		// Build all SOAP headers together (multiple __setSoapHeaders calls replace, not append).
		$headers = $this->setSoapHeaders( $config );
		if ( ! empty( $config['soap_action'] ) ) {
			$headers[] = new \SoapHeader(
				'http://schemas.xmlsoap.org/soap/envelope/',
				'SOAPAction',
				$config['soap_action']
			);
		}
		$soapClient->__setSoapHeaders( $headers );

		// Build the SOAP method call arguments.
		$method = $config['soap_initiate'] ?? '';
		$args = [ $method => $config['call_data'] ?? [] ];

		return $this->request( $soapClient, $config, $args );
	}

	public function send( array $config, $data ): Result
	{
		$wsdl_url = $this->getWsdlUrl( $config );
		$location = $this->getLocation( $config );
		$options  = $this->getSoapClientOptions( $config );

		// For non-WSDL mode, pass location as first argument.
		if ( $wsdl_url === null ) {
			$options['location'] = $location;
			$soapClient = new \SoapClient( null, $options );
		} else {
			$soapClient = new \SoapClient( $wsdl_url, $options );
		}

		// Build all SOAP headers together (multiple __setSoapHeaders calls replace, not append).
		$headers = $this->setSoapHeaders( $config );
		if ( ! empty( $config['soap_action'] ) ) {
			$headers[] = new \SoapHeader( '', 'SOAPAction', $config['soap_action'] );
		}
		$soapClient->__setSoapHeaders( $headers );

		// Build the SOAP method call arguments.
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

		return $this->request( $soapClient, $config, $args );
	}

	protected function request( \SoapClient $soapClient, $config, $args )
	{
		// Build the SOAP method call arguments.
		$method = $config['soap_initiate'] ?? '';

		try {
			$result = $soapClient->__soapCall( $method, $args );

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

			return new Result( $decodedResult, $soapClient, [
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
