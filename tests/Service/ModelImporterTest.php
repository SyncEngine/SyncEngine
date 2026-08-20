<?php

namespace SyncEngine\Tests\Service;

use SyncEngine\Entity\Trace;
use SyncEngine\Model\Enum\TraceStatus;
use SyncEngine\Model\TraceModel;
use SyncEngine\Service\ModelImporter;
use SyncEngine\Tests\TestCase\BaseTestCase;

class ModelImporterTest extends BaseTestCase
{
	private function getImporter(): ModelImporter
	{
		return static::getContainer()->get( ModelImporter::class );
	}

	public function testImportCreatesNewEntities(): void
	{
		$data = [
			'new_routine' => [
				'_entity' => 'Routine',
				'name'    => 'new_import_routine_' . uniqid(),
			],
			'new_storage' => [
				'_entity' => 'Storage',
				'name'    => 'new_import_storage_' . uniqid(),
				'type'    => 'schema',
				'config'  => [
					'type'   => 'schema',
					'schema' => [ 'columns' => [] ],
				],
			],
		];

		$importer = $this->getImporter();
		$result   = $importer->import( $data );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'new_routine', $result );
		$this->assertArrayHasKey( 'new_storage', $result );
		$this->assertGreaterThan( 0, $result['new_routine']->getId() );
		$this->assertGreaterThan( 0, $result['new_storage']->getId() );
		$this->assertEmpty( $importer->getErrors() );
	}

	public function testImportRejectsDotsInRefs(): void
	{
		$data = [
			'bad.ref' => [
				'_entity' => 'Routine',
				'name'    => 'test',
			],
		];

		$importer = $this->getImporter();
		$result   = $importer->import( $data );

		$this->assertFalse( $result );
		$this->assertNotEmpty( $importer->getErrors() );
	}

	public function testImportRefMissingEntityType(): void
	{
		$importer = $this->getImporter();
		$result   = $importer->importRef( 'no_entity', [] );

		$this->assertNull( $result );
		$this->assertNotEmpty( $importer->getErrors() );
	}

	public function testImportRefMissingModelClass(): void
	{
		$data = [
			'nonexistent:1' => [
				'_entity' => 'NonExistentEntity',
				'name'    => 'test',
			],
		];

		$importer = $this->getImporter();
		$importer->import( $data );

		$this->assertNotEmpty( $importer->getErrors() );
	}

	public function testParseSubFieldsResolvesRefStrings(): void
	{
		$ref = 'subfields_' . uniqid();

		$data = [
			$ref . '_storage' => [
				'_entity' => 'Storage',
				'name'    => 'subfields_storage_' . uniqid(),
				'type'    => 'schema',
				'config'  => [
					'type'   => 'schema',
					'schema' => [ 'columns' => [] ],
				],
			],
			$ref . '_routine' => [
				'_entity' => 'Routine',
				'name'    => 'subfields_routine_' . uniqid(),
			],
		];

		$importer = $this->getImporter();
		$result   = $importer->import( $data );

		$fields = [
			'related_storage' => $ref . '_storage',
			'nested'          => [
				'ref_field' => $ref . '_routine',
			],
		];

		$resolved = $importer->parseSubFields( $fields );

		$this->assertEquals( $result[$ref . '_storage']->getId(), $resolved['related_storage'] );
		$this->assertEquals( $result[$ref . '_routine']->getId(), $resolved['nested']['ref_field'] );
	}

	public function testSetMethodValueWithEnum(): void
	{
		$importer = $this->getContainer()->get( ModelImporter::class );

		$entity = new Trace();
		$model  = new TraceModel( $entity );

		$importer->setMethodValue( 'success', 'setStatus', $model, $entity );

		$this->assertEquals( TraceStatus::SUCCESS, $model->getStatus() );
	}

	public function testParseSubFieldsSkipsClassKey(): void
	{
		$ref = 'skips_class_' . uniqid();

		$data = [
			$ref . '_routine' => [
				'_entity' => 'Routine',
				'name'    => 'subfields_skip_' . uniqid(),
			],
		];

		$importer = $this->getImporter();
		$importer->import( $data );

		$fields = [
			'task_config' => [
				'_class' => 'SomeLocator',
			],
		];

		$result = $importer->parseSubFields( $fields );

		// _class key should remain unchanged (not treated as a ref to resolve).
		$this->assertEquals( 'SomeLocator', $result['task_config']['_class'] );
	}

	public function testImportAutomationWithFlowRoutinesAndStorage(): void
	{
		$ref = 'full_import_' . uniqid();

		$data = [
			$ref . '_storage' => [
				'_entity' => 'Storage',
				'name'    => 'full_import_storage_' . uniqid(),
				'type'    => 'schema',
				'config'  => [
					'type'   => 'schema',
					'schema' => [ 'columns' => [ 'input_col' => 'text' ] ],
				],
			],

			$ref . '_routine1' => [
				'_entity' => 'Routine',
				'name'    => 'full_import_routine1_' . uniqid(),
				'config'  => [ 'tasks' => [] ],
			],

			$ref . '_routine2' => [
				'_entity' => 'Routine',
				'name'    => 'full_import_routine2_' . uniqid(),
				'config'  => [
					'tasks' => [
						[
							'_class'  => 'Store',
							'storage' => $ref . '_storage',
						],
					],
				],
			],

			$ref . '_flow' => [
				'_entity' => 'Flow',
				'name'    => 'full_import_flow_' . uniqid(),
				'config'  => [
					'steps' => [ $ref . '_routine1', $ref . '_routine2' ],
				],
			],

			$ref . '_automation' => [
				'_entity' => 'Automation',
				'name'    => 'full_import_automation_' . uniqid(),
				'endpoint' => 'ep_full_import_' . uniqid(),
				'config'  => [
					'actions' => 'flow',
					'flow'    => $ref . '_flow',
				],
			],
		];

		$importer = $this->getImporter();
		$result   = $importer->import( $data );

		$this->assertIsArray( $result );
		$this->assertEmpty( $importer->getErrors() );

		// All entities should be present in the result.
		$this->assertArrayHasKey( $ref . '_storage', $result );
		$this->assertArrayHasKey( $ref . '_routine1', $result );
		$this->assertArrayHasKey( $ref . '_routine2', $result );
		$this->assertArrayHasKey( $ref . '_flow', $result );
		$this->assertArrayHasKey( $ref . '_automation', $result );

		// All should have valid IDs.
		$this->assertGreaterThan( 0, $result[$ref . '_storage']->getId() );
		$this->assertGreaterThan( 0, $result[$ref . '_routine1']->getId() );
		$this->assertGreaterThan( 0, $result[$ref . '_routine2']->getId() );
		$this->assertGreaterThan( 0, $result[$ref . '_flow']->getId() );
		$this->assertGreaterThan( 0, $result[$ref . '_automation']->getId() );

		// Flow config should reference the correct routine IDs.
		$flowConfig = $result[$ref . '_flow']->getConfig();
		$this->assertIsArray( $flowConfig['steps'] );
		$this->assertCount( 2, $flowConfig['steps'] );
		$this->assertEquals( $result[$ref . '_routine1']->getId(), $flowConfig['steps'][0] );
		$this->assertEquals( $result[$ref . '_routine2']->getId(), $flowConfig['steps'][1] );

		// Routine2 task config should reference the correct storage ID.
		$routine2Config = $result[$ref . '_routine2']->getConfig();
		$this->assertIsArray( $routine2Config['tasks'] );
		$this->assertEquals( $result[$ref . '_storage']->getId(), $routine2Config['tasks'][0]['storage'] );

		// Automation config should reference the correct flow ID.
		$automationConfig = $result[$ref . '_automation']->getConfig();
		$this->assertEquals( 'flow', $automationConfig['actions'] );
		$this->assertEquals( $result[$ref . '_flow']->getId(), $automationConfig['flow'] );
	}
}
