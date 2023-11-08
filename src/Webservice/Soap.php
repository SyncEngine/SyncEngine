<?php

namespace App\Webservice;

use App\Model\WebserviceModel;
use App\Webservice\Trait\Http;

class Soap extends WebserviceModel
{
	use Http {
		getClientOptions as private getHttpClientOptions;
	}

	public function __construct()
	{
		parent::__construct();

		$this->type        = 'soap';
		$this->name        = 'Soap';
		$this->description = 'Connect using Soap';
	}

	public function getAuthFields(): array
	{
		return [
			'host'          => [
				'label' => 'Host',
				'type'  => 'text',
			],
			'wdsl_mode'     => [
				'label'    => 'WDSL mode',
				'type'     => 'switch',
				'expanded' => false,
				'help'     => 'Will this connection use wdsl file format?',
			],
			'wdsl_url'      => [
				'label'        => 'WDSL file url',
				'type'         => 'text',
				'help'         => 'Link to wdsl format that will be filled in for this soap connection',
				'conditionals' => [
					'wdsl_mode' => true,
				],
			],
			'soap_initiate' => [
				'label' => 'Soap function from wdsl',
				'type'  => 'text',
			],
			'call_data'     => [
				'label'     => 'Data to fill wdsl to make the call',
				'type'      => 'params',
				'default'   => $defaults['call_data'] ?? null,
				'collapsed' => false,
			],
			'header_url'    => [
				'label' => 'Soap header url (usually for authentication)',
				'type'  => 'text',
			],
			'headers'       => [
				'label'     => 'Soap Headers',
				'type'      => 'params',
				'default'   => $defaults['headers'] ?? null,
				'collapsed' => true,
				/*@Todo Conditional if header url is set
				'conditionals' => [
					'header_url'
				],
				*/
			],
		];
	}

	public function getFields( $defaults = [] ): array
	{
		$fields = [
			'endpoint' => [
				'label' => 'Endpoint',
				'type'  => 'text',
			],
		];

		return array_merge( $fields, parent::getFields( $defaults ) );
	}

	public function getRequestUrl( array $config ): string
	{
		return $config['host'] . ( $config['endpoint'] ?? '' );
	}

	public function retrieve( array $config )
	{
		$wdsl_url   = empty( $config["wdsl_mode"] ) ? null : $config["wdsl_url"];
		$soapClient = new \SoapClient( $wdsl_url, [ "trace" => 1, "exception" => 0 ] );

		$soapClient->__setSoapHeaders( $this->setSoapHeaders( $config ) );
		$result = $soapClient->__soapCall( $config["soap_initiate"], $config["call_data"] );

		return $this->fromFormat( $result );
	}

	public function send( array $config, $data )
	{
		#@todo
	}

	public function setSoapHeaders( array $config )
	{
		$headers = empty( $config['headers'] ) ? null : [];

		foreach ( $config['headers'] as $key => $value ) {
			$headers[] = new \SoapHeader( 'http://soapinterop.org/echoheader/', $key, $value );
		}

		return $headers;
	}
}
