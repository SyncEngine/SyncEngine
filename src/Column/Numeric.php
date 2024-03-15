<?php

namespace SyncEngine\Column;

use SyncEngine\Column\Type\NumericColumnType;
use SyncEngine\Model\ColumnModel;
use SyncEngine\Service\Format\NumberFormatter;
use SyncEngine\Service\Interface\FormatInterface;

class Numeric extends ColumnModel
{
	public function __construct()
	{
		parent::__construct();

		$this->type        = NumericColumnType::TYPE;
		$this->name        = $this->trans( 'Number' );
		$this->description = $this->trans( 'Numeric column' );
	}

	public function getFields(): array
	{
		return [
			'decimals'            => [
				'label' => 'Decimals',
				'type'  => 'number',
			],
			'decimal_separator'   => [
				'label'        => 'Format',
				'type'         => 'select',
				'customizable' => true,
				'choices'      => [
					'.' => '.',
					',' => ',',
				],
			],
			'thousands_separator' => [
				'label'        => 'Format',
				'type'         => 'select',
				'customizable' => true,
				'choices'      => [
					','  => ',',
					'.'  => '.',
					''   => '',
					' '  => ' ',
					'\'' => '\'',
				],
			],
		];
	}

	public function format( $value, array $config, ?FormatInterface $source = null )
	{
		$context = [
			NumberFormatter::DECIMALS            => $config['decimals'] ?? 0,
			NumberFormatter::DECIMAL_SEPARATOR   => $config['decimal_separator'] ?? '.',
			NumberFormatter::THOUSANDS_SEPARATOR => $config['thousands_separator'] ?? ',',
		];

		return $this->getFormatter( $context )->formatFrom( $value, $source );
	}

	public function getFormatter( $context = [] ): FormatInterface
	{
		return new NumberFormatter( $context );
	}
}
