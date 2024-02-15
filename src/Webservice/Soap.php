<?php

namespace SyncEngine\Webservice;

use SyncEngine\Model\WebserviceModel;
use SyncEngine\Webservice\Helper\Result;
use SyncEngine\Webservice\Trait\Http;

class Soap extends WebserviceModel
{
	use Http {
		getClientOptions as private getHttpClientOptions;
	}

	public function __construct()
	{
		parent::__construct();

		$this->type        = 'soap';
		$this->name        = $this->trans( 'SOAP',[],"soap+intl-icu");
		$this->description = $this->trans( 'description',[],"soap+intl-icu");
	}

	public function getAuthFields(): array
	{
		return [
			'host'          => [
				'label' => $this->trans( 'Host',[],"soap+intl-icu" ),
				'type'  => 'text',
			],
			'wsdl_mode'     => [
				'label'    => $this->trans( 'Wsdl_mode',[],"soap+intl-icu" ),
				'type'     => 'switch',
				'expanded' => false,
				'help'     => $this->trans( 'Use_wsdl',[],"soap+intl-icu" ),
			],
			'wsdl_url'      => [
				'label'      => $this->trans( 'Wsdl_file_url',[],"soap+intl-icu" ),
				'type'       => 'text',
				'help'       => $this->trans( 'Wsdl_url_info',[],"soap+intl-icu" ),
				'conditions' => [
					'wsdl_mode' => true,
				],
			],
			'soap_initiate' => [
				'label' => $this->trans( 'Soap_from_wsdl',[],"soap+intl-icu" ),
				'type'  => 'text',
			],
			'call_data'     => [
				'label'     => $this->trans( 'Data_fill_wsdl',[],"soap+intl-icu" ),
				'type'      => 'params',
				'default'   => $defaults['call_data'] ?? null,
				'collapsed' => false,
			],
			'header_url'    => [
				'label' => $this->trans( 'Soap_header_url',[],"soap+intl-icu" ),
				'type'  => 'text',
			],
			'headers'       => [
				'label'     => $this->trans( 'Soap_headers',[],"soap+intl-icu"  ),
				'type'      => 'params',
				'default'   => $defaults['headers'] ?? null,
				'collapsed' => true,
			],
		];
	}

	public function getFields( array $defaults = [] ): array
	{
		$fields = [
			'endpoint' => [
				'label' => $this->trans( 'Endpoint',[],"soap+intl-icu" ),
				'type'  => 'text',
			],
		];

		return array_merge( $fields, parent::getFields( $defaults ) );
	}

	public function getRequestUrl( array $config ): string
	{
		return $config['host'] . ( $config['endpoint'] ?? '' );
	}

	public function retrieve( array $config, $data = null ): Result
	{
		$wsdl_url   = empty( $config['wsdl_mode'] ) ? null : $config['wsdl_url'];
		$soapClient = new \SoapClient( $wsdl_url, [ 'trace' => 1, 'exception' => 0, 'features' => SOAP_SINGLE_ELEMENT_ARRAYS ] );

		$soapClient->__setSoapHeaders( $this->setSoapHeaders( $config ) );
		$result = $soapClient->__soapCall( $config['soap_initiate'], [$config['soap_initiate'] => $config['call_data']] );

		return new Result( (array)$result);
	}

	public function send( array $config, $data ): Result
	{
		// @todo
		return new Result();
	}

	public function setSoapHeaders( array $config ): array|null
	{
		$headers = empty( $config['headers'] ) ? null : [];

		if(!empty($config['headers']))
		{
			foreach ( $config['headers'] as $key => $value ) {
				$headers[] = new \SoapHeader( 'http://soapinterop.org/echoheader/', $key, $value );
			}
		}

		return $headers;
	}
}
