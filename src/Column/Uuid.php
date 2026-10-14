<?php

namespace SyncEngine\Column;

use SyncEngine\Column\Type\TextColumnType;
use SyncEngine\Form\Fields\Collection\FieldCollection;
use SyncEngine\Model\ColumnModel;
use SyncEngine\Service\Format\UuidFormatter;
use SyncEngine\Service\Interface\FormatInterface;

class Uuid extends ColumnModel
{
	public function __construct()
	{
		parent::__construct();

		$this->type        = TextColumnType::TYPE;
		$this->name        = $this->trans( 'UUID' );
		$this->description = $this->trans( 'Universally unique identifier' );
	}

	public function getFields(): FieldCollection
	{
		return new FieldCollection( [
			'fallback' => [
				'label'       => $this->trans( 'Fallback' ),
				'description' => $this->trans( 'Generate UUID if none provided' ),
				'type'        => 'switch',
			],
			'version' => [
				// UUID version to generate if fallback is enabled.
				'label'       => $this->trans( 'Version' ),
				'type'        => 'select',
				'choices'     => [
					'1' => $this->trans( 'Version 1 (time-based)' ),
					'3' => $this->trans( 'Version 3 (namespace name-based)' ),
					'4' => $this->trans( 'Version 4 (random)' ),
					'5' => $this->trans( 'Version 5 (namespace name-based)' ),
					'6' => $this->trans( 'Version 6 (time-ordered)' ),
					'7' => $this->trans( 'Version 7 (Unix Epoch time-ordered)' ),
					'8' => $this->trans( 'Version 8 (custom)' ),
				],
				'conditions'  => [
					'fallback' => true,
				],
			],
			'namespace' => [
				'label'       => $this->trans( 'Namespace' ),
				'type'        => 'text',
				'placeholder' => '00000000-0000-0000-0000-000000000000',
				'conditions'  => [
					'fallback' => true,
					'version'  => [ 'operator' => 'in', 'compare' => [ '3', '5' ] ],
				],
			],
			'name' => [
				'label'       => $this->trans( 'Name' ),
				'type'        => 'text',
				'conditions'  => [
					'fallback' => true,
					'version'  => [ 'operator' => 'in', 'compare' => [ '3', '5' ] ],
				],
			],
		] );
	}

	public function initFormatter( $config = [] ): FormatInterface
	{
		$context = [
			UuidFormatter::FALLBACK  => $config['fallback'] ?? false,
			UuidFormatter::VERSION   => $config['version'] ?? null,
			UuidFormatter::NAMESPACE => $config['namespace'] ?? null,
			UuidFormatter::NAME      => $config['name'] ?? null,
		];

		return new UuidFormatter( $context );
	}
}
