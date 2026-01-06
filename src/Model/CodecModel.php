<?php

namespace SyncEngine\Model;

use SyncEngine\Model\Abstract\ServiceModel;
use SyncEngine\Model\Interface\Configurable;
use SyncEngine\Model\Trait\Config;
use SyncEngine\Model\Trait\Fields;
use SyncEngine\Service\Interface\CodecInterface;

abstract class CodecModel extends ServiceModel implements Configurable
{
	use Config;
	use Fields;

	const SERVICE = 'Codecs';

	/**
	 * The type of codec, can be used for categorizing.
	 *
	 * @var string
	 */
	public string $type = '';

	/**
	 * Human-readable name used in the interface.
	 *
	 * @var string
	 */
	public string $name = '';

	/**
	 * Human-readable description used in the interface.
	 *
	 * @var string
	 */
	public string $description = '';

	private CodecInterface $codec;

	public function getType(): string
	{
		return $this->type;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getDescription(): string
	{
		return $this->description;
	}

	/**
	 * @return string[]
	 */
	abstract public function getFormats(): array;

	abstract public function getContentType( array $config = [], string $format = '' ): string;
	abstract public function initEncoder( array $config = [] ): CodecInterface;

	public function setEncoder( array $config = [] ): static
	{
		$this->codec = $this->initEncoder( $config );

		return $this;
	}

	public function getEncoder( ?array $config = null ): ?CodecInterface
	{
		if ( is_array( $config ) ) {
			return $this->initEncoder( $config );
		}

		if ( ! isset( $this->codec ) && $this->getConfig() ) {
			$this->setEncoder( $this->getConfig() );
		}

		return $this->codec; // Maybe: throw new \ErrorException( 'Encoder is not initialized.' );
	}

	public function encode( $value, string $format, ?array $config = null ): string
	{
		return $this->getEncoder( $config )->encode( $value, $format );
	}

	public function decode( string $value, string $format, ?array $config = null ): mixed
	{
		return $this->getEncoder( $config )->decode( $value, $format );
	}

	public function getFields( array $defaults = [], array $filters = [] ): array
	{
		$fields = $this->getCodecFields( $defaults );

		if ( $filters ) {
			$fields = $this->filterFieldsBy( $filters, $fields, clean: true );
		}

		return $fields;
	}

	public function getEncodeFields( array $defaults = [], array $filters = [] ): array
	{
		return $this->getFields( $defaults, [ ...$filters, '_direction' => 'encode' ] );
	}

	public function getDecodeFields( array $defaults = [], array $filters = [] ): array
	{
		return $this->getFields( $defaults, [ ...$filters, '_direction' => 'decode' ] );
	}

	abstract function getCodecFields( array $defaults = [] ): array;

	public function normalize(): array
	{
		$props = [
			'_class'      => $this->getClassLocator(),
			'type'        => $this->getType(),
			'name'        => $this->getName(),
			'description' => $this->getDescription(),
			'fields'      => $this->getFields(),
		];

		if ( $this->isFromModule() ) {
			$props['module'] = $this->getModule()->getName();
		}

		return $props;
	}
}
