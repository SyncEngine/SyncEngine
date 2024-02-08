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
		$this->name        = $this->trans( 'Soap' );
		$this->description = $this->trans( 'Connect using Soap' );
	}

	public function getAuthFields(): array
	{
		return [
			'host'          => [
				'label' => $this->trans( 'Host' ),
				'type'  => 'text',
			],
			'wdsl_mode'     => [
				'label'    => $this->trans( 'WDSL mode' ),
				'type'     => 'switch',
				'expanded' => false,
				'help'     => $this->trans( 'Will this connection use wdsl file format?' ),
			],
			'wdsl_url'      => [
				'label'      => $this->trans( 'WDSL file url' ),
				'type'       => 'text',
				'help'       => $this->trans( 'Link to wdsl format that will be filled in for this soap connection' ),
				'conditions' => [
					'wdsl_mode' => true,
				],
			],
			'soap_initiate' => [
				'label' => $this->trans( 'Soap function from wdsl' ),
				'type'  => 'text',
			],
			'call_data'     => [
				'label'     => $this->trans( 'Data to fill wdsl to make the call' ),
				'type'      => 'params',
				'default'   => $defaults['call_data'] ?? null,
				'collapsed' => false,
			],
			'header_url'    => [
				'label' => $this->trans( 'Soap header url' ),
				'type'  => 'text',
			],
			'headers'       => [
				'label'     => $this->trans( 'Soap Headers' ),
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
				'label' => $this->trans( 'Endpoint' ),
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
		$wdsl_url   = empty( $config['wdsl_mode'] ) ? null : $config['wdsl_url'];
		$soapClient = new \SoapClient( $wdsl_url, [ 'trace' => 1, 'exception' => 0, 'features' => SOAP_SINGLE_ELEMENT_ARRAYS ] );

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
