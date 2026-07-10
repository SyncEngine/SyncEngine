<?php

namespace SyncEngine\Model;

use SyncEngine\Form\Fields\Collection\FieldCollection;
use SyncEngine\Model\Abstract\ServiceModel;
use SyncEngine\Model\Interface\Configurable;
use SyncEngine\Model\Trait\Config;
use SyncEngine\Model\Trait\Fields;
use SyncEngine\Service\Interface\CodecInterface;
use SyncEngine\Service\Locator\Codecs;

abstract class CodecModel extends ServiceModel implements Configurable
{
	use Config;
	use Fields;

	const SERVICE = Codecs::class;

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

	private ?CodecInterface $codec = null;

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
	abstract public function initEncoder( array $config = [] ): ?CodecInterface;

	public function getFormat( ?iterable $config = null ): string
	{
		$formats = $this->getFormats();
		return reset( $formats );
	}

	public function setEncoder( array $config = [] ): static
	{
		$this->codec = $this->initEncoder( $config );

		return $this;
	}

	public function getEncoder( ?array $config = null ): ?CodecInterface
	{
		if ( isset( $config ) ) {
			return $this->initEncoder( $config );
		}

		if ( ! isset( $this->codec ) && $this->getConfig() ) {
			$this->setEncoder( $this->getConfig() );
		}

		return $this->codec;
	}

	public function encode( $value, ?string $format = null, ?array $config = null ): string|iterable
	{
		$encoder = $this->getEncoder( $config );
		if ( $encoder !== null ) {
			return $encoder->encode( $value, $format ?? $this->getFormat( $config ), $config );
		}

		throw new \RuntimeException( 'Encoder not implemented for format: ' . ( $format ?? 'unknown' ) );
	}

	public function decode( string $value, ?string $format = null, ?array $config = null ): mixed
	{
		$encoder = $this->getEncoder( $config );
		if ( $encoder !== null ) {
			return $encoder->decode( $value, $format ?? $this->getFormat( $config ), $config );
		}

		throw new \RuntimeException( 'Decoder not implemented for format: ' . ( $format ?? 'unknown' ) );
	}

	public function getFields( array $defaults = [], array $filters = [] ): FieldCollection
	{
		$fields = $this->getCodecFields( $defaults );

		if ( $filters ) {
			$fields = $this->filterFieldsBy( $filters, $fields, clean: true );
		}

		return $fields;
	}

	public function getEncodeFields( array $defaults = [], array $filters = [] ): FieldCollection
	{
		return $this->getFields( $defaults, [ ...$filters, '_direction' => 'encode' ] );
	}

	public function getDecodeFields( array $defaults = [], array $filters = [] ): FieldCollection
	{
		return $this->getFields( $defaults, [ ...$filters, '_direction' => 'decode' ] );
	}

	abstract function getCodecFields( array $defaults = [] ): FieldCollection;

	public function normalize(): array
	{
		$props = [
			'_class'      => $this->getClassLocator(),
			'type'        => $this->getType(),
			'name'        => $this->getName(),
			'description' => $this->getDescription(),
			'fields'      => $this->getFields()->normalize(),
		];

		if ( $this->isFromModule() ) {
			$props['module'] = $this->getModule()->getName();
		}

		return $props;
	}
}
