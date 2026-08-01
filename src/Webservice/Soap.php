<?php

namespace SyncEngine\Webservice;

use SyncEngine\Exception\InvalidConfigException;
use SyncEngine\Exception\InvalidException;
use SyncEngine\Form\Fields\Collection\FieldCollection;
use SyncEngine\Model\WebserviceModel;
use SyncEngine\Webservice\Helper\Result;
use SyncEngine\Webservice\Trait\ClientSoap;
use SyncEngine\Webservice\Type\SoapWebserviceType;

class Soap extends WebserviceModel
{
	use ClientSoap;

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
						'html' => '<div class="alert alert-warning">' . $this->trans( '{type} extension is not available', [ 'type' => 'SOAP (ext-soap)' ] ) . '</div>',
					],
				]
			);
		}

		return ( new FieldCollection( [
			'host' => [
				'label' => $this->trans( 'Host' ),
				'type'  => 'text',
			],
		] ) )->merge( $this->getSoapClientFields() );
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

	/**
	 * Not used in SOAP Clients.
	 * This will return the WSDL url if provided and otherwise the non-WSDL URL.
	 */
	public function getRequestUrl( array $config ): string
	{
		if ( ! empty( $config['wsdl_mode'] ) ) {
			return $config['wsdl_url'] ?? '';
		}

		return $this->getLocation( $config );
	}

	public function retrieve( array $config, $data = null ): Result
	{
		$operation = $this->getSoapOperation( $config );
		$body      = $this->getSoapTransport( $config, $data );

		return $this->request( $config, [ $operation => $body ] );
	}

	public function send( array $config, $data ): Result
	{
		$operation = $this->getSoapOperation( $config );
		$body      = $this->getSoapTransport( $config, $data );

		return $this->request( $config, [ $operation => $body ] );
	}

	/**
	 * @param  array        $config
	 * @param  array        $args
	 * @param  \SoapClient|null  $soapClient
	 *
	 * @return Result
	 */
	protected function request( array $config, array $args, ?object $soapClient = null ): Result
	{
		if ( ! class_exists( \SoapClient::class ) ) {
			throw new InvalidException( "SOAP extension is not available." );
		}

		try {
			if ( ! $soapClient instanceof \SoapClient ) {
				$soapClient = $this->getClient( $config );
			}

			$operation = $this->getSoapOperation( $config );
			if ( empty( $operation ) ) {
				throw new InvalidConfigException( 'A SOAP operation name is required.' );
			}

			$result = $soapClient->__soapCall( $operation, $args, $this->getSoapCallOptions( $config ) );

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
				'SoapRequest'  => $soapClient?->__getLastRequest(),
				'SoapResponse' => $soapClient?->__getLastResponse(),
				'Config'       => $config,
			] );
		}
	}
}
