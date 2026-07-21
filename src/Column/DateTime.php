<?php

namespace SyncEngine\Column;

use SyncEngine\Column\Type\TextColumnType;
use SyncEngine\Form\Fields\Collection\FieldCollection;
use SyncEngine\Form\Fields\InputFieldType;
use SyncEngine\Form\Fields\Interface\FieldConfigInterface;
use SyncEngine\Model\ColumnModel;
use SyncEngine\Service\Format\DateTimeFormatter;
use SyncEngine\Service\Interface\FormatInterface;

class DateTime extends ColumnModel
{
	public function __construct()
	{
		parent::__construct();

		$this->type        = TextColumnType::TYPE;
		$this->name        = $this->trans( 'Date' );
		$this->description = $this->trans( 'General date and time column' );
	}

	public function getFields(): FieldCollection
	{
		$wildcardChoices = [
			''          => $this->trans( 'Unchanged' ),
			'{*null*}'  => $this->trans( 'Null' ),
			'{*false*}' => $this->trans( 'False' ),
			'{*empty*}' => $this->trans( 'Empty string' ),
			'{*now*}'   => $this->trans( 'Current timestamp as format' ),
		];

		return new FieldCollection( [
			'format'   => [
				'label'    => $this->trans( 'Format' ),
				'help'     => $this->trans( 'Please check PHP date documentation' ),
				'type'     => 'text',
				'required' => true,
			],
			'timezone' => [
				'label' => $this->trans( 'Timezone' ),
				'help'  => $this->trans( 'Please check PHP DateTimeZone documentation' ),
				'type'  => 'text',
			],
			'empty'    => [
				'label'        => $this->trans( 'Empty value' ),
				'type'         => 'select',
				'customizable' => true,
				'choices'      => $wildcardChoices,
			],
			'fallback' => [
				'label'        => $this->trans( 'Fallback value' ),
				'type'         => 'select',
				'customizable' => true,
				'choices'      => $wildcardChoices,
			]
		] );
	}

	public function getInput( array $config = [] ): ?FieldConfigInterface
	{
		$config = $config ?? $this->getConfig();

		$field = [
			'type' => 'text',
		];

		return new InputFieldType( $field );
	}

	public function format( $value, ?array $config = null, ?ColumnModel $source = null )
	{
		if ( empty( $value ) ) {
			switch ( $config['empty'] ?? '' ) {
				case '{*null*}':
					return null;
				case '{*false*}':
					return false;
				case '{*empty*}':
					return '';
				case '{*now*}':
					$value = time();
				break;
				default:
					if ( ! empty( $config['empty'] ) ) {
						return $config['empty'];
					}

					return $value;
			}
		}

		try {

			// Format using the DateTimeFormatter.
			$parsed = parent::format( $value, $config, $source );

		} catch ( \Exception $e ) {
			return match ( $config['fallback'] ?? '' ) {
				'{*null*}'  => null,
				'{*false*}' => false,
				'{*empty*}' => '',
				default     => ! empty( $config['fallback'] ) ? $config['fallback'] : $value,
			};
		}

		// @todo Handle errors in the DateTimeFormatter and return fallback values if necessary.
		/*if ( $parsed instanceof \DateTimeImmutable ) {
			$errors = $parsed->getLastErrors();
			if ( $errors['error_count'] > 0 ) {
				switch ( $config['fallback'] ?? '' ) {
					case '{*null*}':
						return null;
					case '{*false*}':
						return false;
					case '{*empty*}':
						return '';
				}
			}
		}*/

		return $parsed;
	}

	public function initFormatter( $config = [] ): FormatInterface
	{
		$context = [
			DateTimeFormatter::FORMAT   => $config['format'],
			DateTimeFormatter::TIMEZONE => $config['timezone'] ?? null,
			DateTimeFormatter::FALLBACK => match ( $config['fallback'] ?? '' ) {
				'{*now*}' => DateTimeFormatter::FALLBACK_NOW,
				default => null,
			},
		];

		return new DateTimeFormatter( $context );
	}
}
