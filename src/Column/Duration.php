<?php

namespace SyncEngine\Column;

use SyncEngine\Column\Type\TextColumnType;
use SyncEngine\Model\ColumnModel;
use SyncEngine\Service\Format\DateTimeFormatter;
use SyncEngine\Service\Format\DurationFormatter;
use SyncEngine\Service\Interface\FormatInterface;

class Duration extends ColumnModel
{
	public function __construct()
	{
		parent::__construct();

		$this->type        = TextColumnType::TYPE;
		$this->name        = $this->trans( 'Duration' );
		$this->description = $this->trans( 'General duration column' );
	}

	public function getFields(): array
	{
		return [
			'format'   => [
				'label'    => $this->trans( 'Format' ),
				'help'     => $this->trans( 'Please check PHP DateInterval documentation' ),
				'type'     => 'text',
				'required' => true,
			],
			'timezone' => [
				'label' => $this->trans( 'Timezone' ),
				'help'  => $this->trans( 'Please check PHP DateTimeZone documentation' ),
				'type'  => 'text',
			],
		];
	}

	public function initFormatter( $config = [] ): FormatInterface
	{
		$context = [
			DurationFormatter::FORMAT   => $config['format'],
			DateTimeFormatter::TIMEZONE => $config['timezone'] ?? null,
		];

		return new DurationFormatter( $context );
	}
}
