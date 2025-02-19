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
					'default'  => $this->trans( 'lowercase-dash' ),
					'constant' => $this->trans( 'UPPERCASE_UNDERSCORE' ),
					'snake'    => $this->trans( 'snake_case' ),
					'camel'    => $this->trans( 'camelCase' ),
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
				'customizable' => true,
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
		$config['format'] = $config['format'] ?? 'slug';
		
		if ( 'custom' !== $config['format'] ) {
			switch ( $config['format'] ) {
				case 'constant':
					$config['case']      = 'upper';
					$config['separator'] = '_';
				break;
				case 'snake':
					$config['case']      = 'lower';
					$config['separator'] = '_';
				break;
				case 'camel':
					$config['case']      = 'camel';
					$config['separator'] = '';
				break;
				default:
				case 'slug':
					$config['case']      = 'lower';
					$config['separator'] = '-';
				break;
			}
		}

		$context = [
			SlugFormatter::CASE      => $config['case'] ?? '',
			SlugFormatter::SEPARATOR => $config['separator'] ?? '-',
		];

		return new SlugFormatter( $context );
	}
}
