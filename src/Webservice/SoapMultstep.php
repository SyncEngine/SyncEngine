<?php

namespace SyncEngine\Webservice;

use SyncEngine\Model\ConnectionModel;
use SyncEngine\Webservice\Helper\Result;
use SyncEngine\Webservice\Trait\MultistepAuth;
use SyncEngine\Webservice\Type\SoapWebserviceType;

class SoapMultstep extends Soap
{
	use MultistepAuth {
		getAuthFields as getAuthMultistepFields;
	}

	public function __construct()
	{
		parent::__construct();
		$this->type        = SoapWebserviceType::TYPE;
		$this->icon        = 'webservice-soap-multistep';
		$this->name        = $this->trans( 'SOAP multistep server' );
		$this->description = $this->trans( 'Connect to a SOAP server using multistep setup' );
	}

	public function getAuthFields(): array
	{
		return array_merge(
			[
				'host' => [
					'label' => $this->trans( 'Host' ),
					'type'  => 'text',
				],
			],
			$this->getAuthMultistepFields(),
		);
	}

	public function getAuthStepRequestFields(): array
	{
		return array_merge(
			[
				'url' => [
					'label'    => $this->trans( 'Url' ),
					'help'     => $this->trans( 'The URL for this authentication step' ),
					'type'     => 'text',
					'taggable' => true,
				],
			],
			$this->getRetrieveFields()
		);
	}

	public function authorizeStep( $authConfig, $connection ): Result
	{
		if ( ! $connection instanceof ConnectionModel ) {
			$connection = ConnectionModel::get( $connection );
		}

		$authConfigRequest = $authConfig['request'] ?? [];

		$wsdl_url   = empty( $authConfigRequest['wsdl_mode'] ) ? null : $authConfigRequest['wsdl_url'];
		$soapClient = new \SoapClient(
			$wsdl_url, [ 'trace' => 1, 'exception' => 0, 'features' => SOAP_SINGLE_ELEMENT_ARRAYS ]
		);

		$soapClient->__setSoapHeaders( $this->setSoapHeaders( $authConfigRequest ) );
		try {
			$result = $soapClient->__soapCall(
				$authConfigRequest['soap_initiate'],
				[ $authConfigRequest['soap_initiate'] => $authConfigRequest['call_data'] ]
			);

			$this->parseAuthStepResponse( $result, $authConfig, $connection );

			$data = [
				'Content' => $result,
			];

			return new Result( true, (array) $result, $data );
		} catch ( \Throwable $e ) {

			$data = [
				'Config' => $authConfig,
			];

			return new Result( false, $e, $data );
		}
	}

	public function parseAuthStepResponseType( $response, $tagConfig )
	{
		return $response;
	}
}
