<?php

namespace SyncEngine\Tests\Model;


use Doctrine\Persistence\Proxy;
use SyncEngine\Entity\Automation;
use SyncEngine\Model\Abstract\AbstractModel;
use SyncEngine\Model\Abstract\EntityModel;
use SyncEngine\Model\AutomationModel;
use SyncEngine\Tests\TestCase\BaseTestCase;

class AbstractEntityModelTest extends BaseTestCase
{
	public function testUtil(): void
	{
		$em = $this->getContainer()->get( 'doctrine.orm.entity_manager' );

		$proxy = $em->getReference( Automation::class, 1 );

		$this->assertInstanceOf( Proxy::class, $proxy );
		$this->assertInstanceOf( Automation::class, $proxy );

		$modelClass = EntityModel::getEntityModelClass( $proxy );

		$this->assertEquals( AutomationModel::class, $modelClass );

	}
}
