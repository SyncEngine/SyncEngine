<?php

namespace SyncEngine\Tests\Service;

use SyncEngine\Entity\Trace;
use SyncEngine\Model\Enum\TraceStatus;
use SyncEngine\Model\TraceModel;
use SyncEngine\Service\ModelImporter;
use SyncEngine\Tests\TestCase\BaseTestCase;

class ModelImporterTest extends BaseTestCase
{

	public function testSetMethodValue(): void
	{
		$importer = $this->getContainer()->get( ModelImporter::class );

		$entity = new Trace();
		$model  = new TraceModel( $entity );

		$importer->setMethodValue( 'success', 'setStatus', $model, $entity );

		$this->assertEquals( TraceStatus::SUCCESS, $model->getStatus() );
		$this->assertEquals( 'success', $entity->getStatus() );
	}
}
