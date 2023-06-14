<?php

namespace App\Model\Trait;

use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\YamlEncoder;

trait Format
{
	public function getFormatEncoder( $format )
	{
		switch ( $format ) {
			case 'json':
				return new JsonEncoder();
			case 'csv':
				return new CsvEncoder();
			case 'xml':
				return new XmlEncoder();
			case 'yml':
				return new YamlEncoder();
		}
		return null;
	}

	public function getFormatFields(): array
	{
		return [
			'format' => [
				'label' => 'Format',
				'type'  => 'select',
				'choices' => [
					'json' => 'JSON',
					'csv'  => 'CSV',
					'xml'  => 'XML',
					'yaml' => 'YAML',
				],
			],
		];
	}

	public function toFormat( string $format, array $data, array $context = [] ): string
	{
		return $this->getFormatEncoder( $format )->encode( $data, $format, $context );
	}

	public function fromFormat( string $format, string $data, array $context = [] ): array
	{
		return $this->getFormatEncoder( $format )->decode( $data, $format, $context );
	}
}
