<?php

namespace App\Model;

use App\Model\Trait\Format;
use App\Model\Trait\ModuleContext;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

abstract class WebserviceModel
{
	use ModuleContext;
	use Format;

	public string $type = '';
	public string $name = '';
	public string $description = '';

	protected HttpClientInterface $client;

	public function __construct()
	{
		// Construct.
	}

	public function getType(): string {
		return $this->type;
	}

	public function getName(): string {
		return $this->name;
	}

	public function getDescription(): string {
		return $this->description;
	}

	public function getClient( array $config = array() ): HttpClientInterface
	{
		$options = [];
		if ( $config ) {
			$options = $this->getClientOptions( $config );
		}

		if ( empty( $this->client ) ) {
			$this->setClient( HttpClient::create( $options ) );
		} else {
			$this->setClient( $this->client->withOptions( $options ) );
		}

		return $this->client;
	}

	public function getClientOptions( array $config = array() ): array
	{
		$options = [];

		if ( ! empty( $config['query'] ) ) {
			$options['query'] = [];
			foreach ( $config['query'] as $query ) {
				$options['query'][ $query['key'] ] = $query['value'];
			}
		}

		if ( ! empty( $config['headers'] ) ) {
			$options['header'] = [];
			foreach ( $config['headers'] as $header ) {
				$options['headers'][ $header['key'] ] = $header['value'];
			}
		}

		return $options;
	}

	public function getArgs(): array
	{
		$props = get_object_vars( $this );
		$props['slug']   = $this->getSlug();
		$props['auth']   = $this->getAuthFields();
		$props['fields'] = $this->getFields();
		if ( $this->isModuleContext() ) {
			$props['module'] = $this->getModule() ? ( new \ReflectionClass( $this->getModule() ) )->getShortName() : '';
		}
		return $props;
	}

	final public function getSlug(): string
	{
		$prefix = '';
		if ( $this->isModuleContext() ) {
			$prefix = ( new \ReflectionClass( $this->getModule() ) )->getShortName() . ':';
		}
		return $prefix . $this->getType();
	}

	abstract public function getRequestUrl( array $config ): string;

	abstract public function getAuthFields(): array;

	public function getFields(): array
	{
		return array_merge( [
			'method' => [
				'label' => 'Request Method',
				'type' => 'select',
				'choices' => [
					'GET'     => 'GET',
					'POST'    => 'POST',
					'PUT'     => 'PUT',
					'PATCH'   => 'PATCH',
					'DELETE'  => 'DELETE',
					'HEAD'    => 'HEAD',
					'CONNECT' => 'CONNECT',
					'OPTIONS' => 'OPTION',
					'TRACE'   => 'TRACE',
				]
			],
			'query' => [
				'label' => 'Request Query',
				'type' => 'params',
			],
			'headers' => [
				'label' => 'Request Headers',
				'type' => 'params',
			],
			// @todo Should this even be available?
			/*'params' => [
				'label' => 'Request Params',
				'type' => 'params',
			],
			'body' => [
				'label' => 'Request Body',
				'type' => 'params',
			],*/
		], $this->getFormatFields() );
	}

	abstract public function retrieve( array $config );

	abstract public function send( array $config, $data );

	public function setClient( $client ): void
	{
		$this->client = $client;
	}

	final static function isWebservice( $class ): bool
	{
		return $class instanceof WebserviceModel;
	}
}
