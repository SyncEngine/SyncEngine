<?php

namespace SyncEngine\Column;

use SyncEngine\Column\Type\NumericColumnType;
use SyncEngine\Form\Fields\Interface\FieldConfigInterface;
use SyncEngine\Service\Format\CurrencyFormatter;
use SyncEngine\Service\Format\FloatFormatter;
use SyncEngine\Service\Interface\FormatInterface;

// @link https://documentation.infiniteblue.com/platform/Currency%20formats.htm
// @link https://fastspring.com/blog/how-to-format-30-currencies-from-countries-all-over-the-world/
class Currency extends Numeric
{
	public function __construct()
	{
		parent::__construct();

		$this->type        = NumericColumnType::TYPE;
		$this->name        = $this->trans( 'Currency' );
		$this->description = $this->trans( 'Currency column for money/price based values' );
	}

	public function getFields(): array
	{
		return array_merge( parent::getFields(), [
			'currency_sign' => [
				'label' => $this->trans( 'Currency sign' ),
				'type'  => 'text',
				'conditions'   => [ 'type' => 'format' ],
			],
			'currency_space' => [
				'label' => $this->trans( 'Add space between currency sign and value?' ),
				'type'  => 'checkbox',
				'conditions'   => [ 'type' => 'format' ],
			],
			'currency_after' => [
				'label' => $this->trans( 'Put currency sign after value?' ),
				'type'  => 'checkbox',
				'conditions'   => [ 'type' => 'format' ],
			],
		] );
	}

	public function getInput( array $config = [] ): ?FieldConfigInterface
	{
		$config = $config ?? $this->getConfig();

		$field = parent::getInput( $config );

		$sign = $config['currency_sign'] ?? null;
		if ( $sign ) {
			if ( $config['currency_after'] ?? false ) {
				$field->setProp( 'postfix', $sign,);
			} else {
				$field->setProp( 'prefix', $sign,);
			}
		}

		return $field;
	}

	public function initFormatter( array $config = [] ): FormatInterface
	{
		switch ( $config['type'] ?? '' ) {
			case 'format':
				$context = [
					CurrencyFormatter::DECIMALS            => $config['decimals'] ?? 0,
					CurrencyFormatter::DECIMAL_SEPARATOR   => $config['decimal_separator'] ?? '.',
					CurrencyFormatter::THOUSANDS_SEPARATOR => $config['thousands_separator'] ?? ',',
					CurrencyFormatter::ROUND               => $config['round'] ?? null,
					CurrencyFormatter::CURRENCY_SIGN       => $config['currency_sign'] ?? null,
					CurrencyFormatter::CURRENCY_SPACE      => $config['currency_space'] ?? false,
					CurrencyFormatter::CURRENCY_AFTER      => $config['currency_after'] ?? false,
				];

				return new CurrencyFormatter( $context );
			default:
				$context = [
					FloatFormatter::PRECISION => $config['decimals'] ?? 0,
					FloatFormatter::MODE      => $config['round'] ?? null,
				];

				return new FloatFormatter( $context );
		}
	}
}
