<?php

namespace SyncEngine\Column;

use SyncEngine\Column\Type\TextColumnType;
use SyncEngine\Model\ColumnModel;
use SyncEngine\Service\Format\StringFormatter;
use SyncEngine\Service\Interface\FormatInterface;

class Text extends ColumnModel
{
	public function __construct()
	{
		parent::__construct();

		$this->type        = TextColumnType::TYPE;
		$this->name        = $this->trans( 'Text' );
		$this->description = $this->trans( 'General text column' );
	}

	public function getFields(): array
	{
		return [
			'trim' => [
				'label' => $this->trans( 'Trim text' ),
				'type' => 'checkbox',
			],
		];
	}

	public function getFormatter( $config = [] ): FormatInterface
	{
		$context = [
			StringFormatter::TRIM => ! empty( $config[ 'trim' ] ),
		];
		return new StringFormatter( $context );
	}
}
