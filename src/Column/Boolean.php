<?php

namespace SyncEngine\Column;

use SyncEngine\Column\Type\BooleanColumnType;
use SyncEngine\Model\ColumnModel;
use SyncEngine\Service\Format\BooleanFormatter;
use SyncEngine\Service\Interface\FormatInterface;

class Boolean extends ColumnModel
{
	public function __construct()
	{
		parent::__construct();

		$this->type        = BooleanColumnType::TYPE;
		$this->name        = $this->trans( 'Boolean' );
		$this->description = $this->trans( 'Format as true or false' );
	}

	public function initFormatter( $config = [] ): FormatInterface
	{
		return new BooleanFormatter();
	}
}
