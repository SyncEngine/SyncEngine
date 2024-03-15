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
			'decimals' => [
				'label' => 'Decimals',
				'type'  => 'number',
			],
			'format' => [
				'label' => 'Format',
				'type'  => 'select',
				'choices' => [
					'9,999.99'  => '1,234.00',
					'9999.99'   => '1234.00',
					'9.999,99'  => '1.234,00',
					'9999,99'   => '1234,00',
					'9 999,99'  => '1 234,00',
					'9\'999.99' => '1\'234.00',
				],
			],
		];
	}

	public function format( $value, array $config, ?FormatInterface $source = null )
	{
		$context = [
			NumberFormatter::DECIMALS => $config['decimals'] ?? 0,
		];

		switch ( $config['format'] ) {
			default:
			case '9,999.99':
				$context[ NumberFormatter::DECIMAL_SEPARATOR ] = ',';
				$context[ NumberFormatter::THOUSANDS_SEPARATOR ] = '.';
			break;
			case '9999.99':
				$context[ NumberFormatter::DECIMAL_SEPARATOR ] = '';
				$context[ NumberFormatter::THOUSANDS_SEPARATOR ] = '.';
			break;
			case '9.999,99':
				$context[ NumberFormatter::DECIMAL_SEPARATOR ] = '.';
				$context[ NumberFormatter::THOUSANDS_SEPARATOR ] = ',';
			break;
			case '9999,99':
				$context[ NumberFormatter::DECIMAL_SEPARATOR ] = '';
				$context[ NumberFormatter::THOUSANDS_SEPARATOR ] = ',';
			break;
			case '9 999,99':
				$context[ NumberFormatter::DECIMAL_SEPARATOR ] = ' ';
				$context[ NumberFormatter::THOUSANDS_SEPARATOR ] = ',';
			break;
			case '9\'999.99':
				$context[ NumberFormatter::DECIMAL_SEPARATOR ] = '\'';
				$context[ NumberFormatter::THOUSANDS_SEPARATOR ] = ',';
			break;
		}

		return $this->getFormatter( $context )->formatFrom( $value, $source );
	}

	public function getFormatter( $context = [] ): FormatInterface
	{
		return new NumberFormatter( $context );
	}
}
