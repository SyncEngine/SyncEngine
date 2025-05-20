<?php

namespace SyncEngine\Tests\TestCase;

use SyncEngine\Model\ColumnModel;
use SyncEngine\Service\Provider\Columns;

abstract class ColumnTestCase extends ExecuteTestCase
{
	protected string $_column;

	public function getColumn( $column = '' ): ?ColumnModel
	{
		return static::getContainer()->get( Columns::class )->get( $column ?: $this->_column );
	}

	public function testExists() {
		$this->assertInstanceOf( ColumnModel::class, $this->getColumn() );
	}
}
