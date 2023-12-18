<?php

namespace SyncEngine\Tests\Model;


use SyncEngine\Model\Abstract\AbstractModel;
use SyncEngine\Tests\TestCase\BaseTestCase;

class AbstractModelTest extends BaseTestCase
{
	public function testUtil(): void
	{
		$model = AbstractModel::getModelClassName( 'automation' );
		$this->assertEquals( 'AutomationModel', $model );

		$model = AbstractModel::getModelClass( 'automation' );
		$automation = new $model();

		$this->assertEquals( 'AutomationModel', $automation->getClassName() );
		$this->assertEquals( 'Automation', $automation->getModelName() );
	}
}
