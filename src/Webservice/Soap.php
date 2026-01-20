<?php

namespace SyncEngine\Webservice;

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

	public function getAuthFields(): array
	{
		return [
			'host' => [
				'label' => $this->trans( 'Host' ),
				'type'  => 'text',
			],
		];
	}

	public function getRetrieveFields( array $defaults = [] ): array
	{
		$fields = [
			'endpoint'      => [
				'label' => $this->trans( 'Endpoint' ),
				'type'  => 'text',
			],
			'wsdl_mode'     => [
				'label'    => $this->trans( 'WSDL mode' ),
				'type'     => 'switch',
				'expanded' => false,
				'help'     => $this->trans( 'Will this connection use WSDL file format?' ),
			],
			'wsdl_url'      => [
				'label'      => $this->trans( 'WSDL file url' ),
				'type'       => 'text',
				'help'       => $this->trans( 'Link to WSDL format that will be filled in for this soap connection' ),
				'conditions' => [
					'wsdl_mode' => true,
				],
			],
			'soap_initiate' => [
				'label' => $this->trans( 'Soap function from WSDL' ),
				'type'  => 'text',
			],
			'call_data'     => [
				'label'     => $this->trans( 'Data to fill WSDL to make the call' ),
				'type'      => 'params',
				'default'   => $defaults['call_data'] ?? null,
				'collapsed' => false,
			],
			'headers'       => [
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
		];

		return $fields;
	}

	public function getRequestUrl( array $config ): string
	{
		return $config['host'] . ( $config['endpoint'] ?? '' );
	}

	public function setSoapHeaders( array $config ): array|null
	{
		$headers = empty( $config['headers'] ) ? null : [];

		if ( ! empty( $config['headers'] ) ) {
			foreach ( $config['headers'] as $header ) {
				$headers[] = new \SoapHeader(
					$header['url'] ?? 'http://soapinterop.org/echoheader/', $header['key'], $header['value']
				);
			}
		}

		return $headers;
	}

	public function getRequestFields( $defaults = [] ): array
	{
		return [
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
		];
	}

	public function retrieve( array $config, $data = null ): Result
	{
		$wsdl_url   = empty( $config['wsdl_mode'] ) ? null : $config['wsdl_url'];
		$soapClient = new \SoapClient(
			$wsdl_url, [ 'trace' => 1, 'exception' => 0, 'features' => SOAP_SINGLE_ELEMENT_ARRAYS ]
		);

		$soapClient->__setSoapHeaders( $this->setSoapHeaders( $config ) );
		$result = $soapClient->__soapCall(
			$config['soap_initiate'],
			[ $config['soap_initiate'] => $config['call_data'] ]
		);

		return new Result( (array) $result );
	}

	public function send( array $config, $data ): Result
	{
		$wsdl_url   = empty( $config['wsdl_mode'] ) ? null : $config['wsdl_url'];
		$soapClient = new \SoapClient(
			$wsdl_url, [ 'trace' => 1, 'exception' => 0, 'features' => SOAP_SINGLE_ELEMENT_ARRAYS ]
		);

		$soapClient->__setSoapHeaders( $this->setSoapHeaders( $config ) );
		$result = $soapClient->__soapCall(
			$config['soap_initiate'],
			[ $config['soap_initiate'] => $config['call_data'] ]
		);

		return new Result( (array) $result );
	}
}
