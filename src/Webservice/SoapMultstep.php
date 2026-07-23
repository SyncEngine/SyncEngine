<?php

namespace SyncEngine\Webservice;

use SyncEngine\Form\Fields\Collection\FieldCollection;
use SyncEngine\Model\ConnectionModel;
use SyncEngine\Service\Serializer\XmlEncoder;
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

		return ( new FieldCollection(
			[
				'host' => [
					'label' => $this->trans( 'Host' ),
					'type'  => 'text',
				],
			]
		) )->merge(
			$this->getAuthMultistepFields(),
		);
	}

	public function getAuthStepRequestFields(): FieldCollection
	{
		return ( new FieldCollection(
			[
				'url' => [
					'label'    => $this->trans( 'Url' ),
					'help'     => $this->trans( 'The URL for this authentication step' ),
					'type'     => 'text',
					'taggable' => true,
				],
			]
		) )->merge(
			$this->getSoapFields()
		)->merge(
			$this->getRequestFields()
		);
	}

	public function authorizeStep( $authConfig, $connection ): Result
	{
		if ( ! $connection instanceof ConnectionModel ) {
			$connection = ConnectionModel::get( $connection );
		}

		$authConfigRequest = $authConfig['request'] ?? [];
		$authConfigRequest['connection'] = $connection;

		$method = $authConfigRequest['soap_initiate'] ?? '';
		$args   = [ $method => $authConfigRequest['call_data'] ?? [] ];
		$soapClient = null;

		try {
			$soapClient = $this->createSoapClient( $authConfigRequest );
			$soapClient->__setSoapHeaders( $this->setSoapHeaders( $authConfigRequest ) );
			$result = $soapClient->__soapCall( $method, $args, $this->getSoapCallOptions( $authConfigRequest ) );

			$this->parseAuthStepResponse( $result, $authConfig, $connection );

			$data = [
				'Content' => $result,
			];

			return new Result( true, true, $data );
		} catch ( \Throwable $e ) {
			$data = [
				'SoapRequest'  => $soapClient?->__getLastRequest(),
				'SoapResponse' => $soapClient?->__getLastResponse(),
				'Config'       => $authConfigRequest,
			];

			return new Result( false, $e, $data );
		}
	}

	/**
	 * Parse SOAP response
	 *
	 * @param  mixed  $response  The raw SOAP response (may be object, array, or string)
	 * @param  array  $tagConfig  Unused for now.
	 * @return mixed  Extracted value or the original response if no extraction matched
	 */
	public function parseAuthStepResponseType( $response, $tagConfig = [] )
	{
		if ( is_object( $response ) || is_array( $response ) ) {
			return $response;
		}

		if ( is_string( $response ) && str_starts_with( $response, '<' ) ) {
			// Try to parse as XML string.
			try {
				$response = ( new XmlEncoder() )->decode( $response, 'xml' );
			} catch ( \Throwable $e ) {
				return [];
			}

			if ( isset( $response['soap:Body'] ) ) {
				$response = $response['soap:Body'];
			}
		}

		return $response;
	}
}
