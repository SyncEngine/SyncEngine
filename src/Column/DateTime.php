<?php

namespace SyncEngine\Column;

use SyncEngine\Column\Type\TextColumnType;
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

	public function getFields(): array
	{
		return [
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
		];
	}

	public function format( $value, array $config, ?FormatInterface $source = null )
	{
		parent::format( $value, $config, $source );
	}

	public function initFormatter( $config = [] ): FormatInterface
	{
		$context = [
			DateTimeFormatter::FORMAT   => $config['format'],
			DateTimeFormatter::TIMEZONE => $config['timezone'],
		];

		return new DateTimeFormatter( $context );
	}
}
