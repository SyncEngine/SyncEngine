<?php

namespace SyncEngine\Column;

use SyncEngine\Column\Type\TextColumnType;
use SyncEngine\Model\ColumnModel;
use SyncEngine\Service\Format\SlugFormatter;
use SyncEngine\Service\Interface\FormatInterface;

class Slug extends ColumnModel
{
	public function __construct()
	{
		parent::__construct();

		$this->type        = TextColumnType::TYPE;
		$this->name        = $this->trans( 'Slug' );
		$this->description = $this->trans( 'Format as a slug' );
	}

	public function getFields(): array
	{
		return [
			'format'    => [
				'label'   => $this->trans( 'Format' ),
				'type'    => 'select',
				'choices' => [
					'default'  => $this->trans( 'lowercase-dash' ), // kebab case
					//'kebab'    => $this->trans( 'kebab-case' ),
					'snake'    => $this->trans( 'snake_case' ),
					'camel'    => $this->trans( 'camelCase' ),
					'constant' => $this->trans( 'UPPERCASE_UNDERSCORE' ),
					'custom'   => $this->trans( 'Custom' ),
				],
			],
			'case'      => [
				'label'      => $this->trans( 'Case' ),
				'type'       => 'select',
				'choices'    => [
					'lower'   => $this->trans( 'lowercase' ),
					'upper'   => $this->trans( 'UPPERCASE' ),
					'ucwords' => $this->trans( 'Capitalize Words' ),
					'ucfirst' => $this->trans( 'Capitalize first' ),
					'camel'   => $this->trans( 'camel Style' ),
				],
				'conditions' => [ 'format' => 'custom' ],
			],
			'separator' => [
				'label'        => $this->trans( 'Word separator' ),
				'type'         => 'select',
				'customizable' => true, // Allow any kind of user-defined separator.
				'taggable'     => true,
				'default'      => '-',
				'choices'      => [
					'-' => '- { ' . $this->trans( 'dash' ) . ' }',
					'_' => '_ { ' . $this->trans( 'underscore' ) . ' }',
					''  => '{ ' . $this->trans( 'none' ) . ' }',
					' ' => '{ ' . $this->trans( 'space' ) . ' }',
				],
				'conditions'   => [ 'format' => 'custom' ],
			],
		];
	}

	public function initFormatter( $config = [] ): FormatInterface
	{
		$format    = $config['format'] ?? 'slug';
		$case      = $config['case'] ?? '';
		$separator = $config['separator'] ?? '-';

		if ( 'custom' !== $format ) {
			switch ( $format ) {
				case 'constant':
					$case      = 'upper';
					$separator = '_';
				break;
				case 'snake':
					$case      = 'lower';
					$separator = '_';
				break;
				case 'camel':
					$case      = 'camel';
					$separator = '';
				break;
				case 'kebab':
				case 'slug':
				default:
					$case      = 'lower';
					$separator = '-';
				break;
			}
		}

		$context = [
			SlugFormatter::CASE      => $case,
			SlugFormatter::SEPARATOR => $separator,
		];

		return new SlugFormatter( $context );
	}
}
