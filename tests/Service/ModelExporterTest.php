<?php

namespace SyncEngine\Tests\Service;

use DateTimeImmutable;
use SyncEngine\Form\Fields\Collection\FieldCollection;
use SyncEngine\Model\Enum\TraceStatus;
use SyncEngine\Model\FlowModel;
use SyncEngine\Model\RoutineModel;
use SyncEngine\Model\StorageModel;
use SyncEngine\Service\ModelExporter;
use SyncEngine\Tests\TestCase\BaseTestCase;

class ModelExporterTest extends BaseTestCase
{
	private function getExporter(): ModelExporter
	{
		return static::getContainer()->get( ModelExporter::class );
	}

	public function testExportNullReturnsEmptyArray(): void
	{
		$exporter = $this->getExporter();
		$result   = $exporter->export( null );

		$this->assertEmpty( $result );
	}

	public function testExportRoutineIncludesAllProperties(): void
	{
		$routine = $this->createRoutine( 'export_routine_' . uniqid() );

		$exporter = $this->getExporter();
		$result   = $exporter->export( $routine );

		$this->assertArrayHasKey( $routine->getRef(), $result );
		$this->assertEquals( 'Routine', $result[ $routine->getRef() ]['_entity'] );
		$this->assertEquals( $routine->getName(), $result[ $routine->getRef() ]['name'] );
	}

	public function testExportFlowWithDependencies(): void
	{
		$routine = $this->createRoutine( 'export_dep_routine_' . uniqid() );

		$flow = FlowModel::create();
		$flow->setName( 'export_flow_' . uniqid() );
		$flow->setConfig( [ 'steps' => [ $routine->getId() ] ] );
		$flow->save( true );

		$exporter = $this->getExporter();
		$result   = $exporter->export( $flow );

		$this->assertArrayHasKey( $flow->getRef(), $result );
		$this->assertArrayHasKey( $routine->getRef(), $result );
	}

	public function testParsePropertyValueWithDateTime(): void
	{
		$routine = RoutineModel::create();
		$routine->setName( 'datetime_routine_' . uniqid() );
		$routine->setConfig( [ 'tasks' => [] ] );
		$routine->save( true );

		$exporter = $this->getExporter();
		$date     = new DateTimeImmutable( '2024-06-15T10:30:00' );
		$result   = $exporter->parsePropertyValue( $date, $routine );

		$this->assertEquals( '2024-06-15T10:30:00+00:00', $result );
	}

	public function testParsePropertyValueWithEnum(): void
	{
		$routine = RoutineModel::create();
		$routine->setName( 'enum_routine_' . uniqid() );
		$routine->setConfig( [ 'tasks' => [] ] );
		$routine->save( true );

		$exporter = $this->getExporter();
		$result   = $exporter->parsePropertyValue( TraceStatus::SUCCESS, $routine );

		$this->assertEquals( 'success', $result );
	}

	public function testParseConfigFieldsResolvesEntityField(): void
	{
		$routine = $this->createRoutine( 'config_entity_routine_' . uniqid() );

		$fields = new FieldCollection(
			[
				'r' => [
					'name'   => 'routine',
					'type'   => 'entity',
					'entity' => 'routine',
				],
			]
		);

		$config = [ 'routine' => $routine->getId() ];

		$exporter = $this->getExporter();
		$result   = $exporter->parseConfigFields( $config, $fields );

		$this->assertEquals( $routine->getRef(), $result['routine'] );
	}

	public function testParseConfigFieldsEmptyFieldsReturnsConfig(): void
	{
		$exporter = $this->getExporter();
		$config   = [ 'key' => 'value', 'other' => 123 ];

		$result = $exporter->parseConfigFields( $config, [] );

		$this->assertEquals( $config, $result );
	}

	public function testParseConfigTagsExtractsStorageRefs(): void
	{
		$storage = $this->createStorage( 'tag_export_storage_' . uniqid(), 'schema', [ 'col' => 'text' ] );

		$config = [
			'data'        => '{{ storage.' . $storage->getId() . ' }}',
			'data_quoted' => '{{ storage."' . $storage->getId() . '" }}',
		];

		$exporter = $this->getExporter();
		$result   = $exporter->parseConfigTags( $config );

		$this->assertStringContainsString( $storage->getRef(), $result['data'] );
		$this->assertStringContainsString( $storage->getRef(), $result['data_quoted'] );
	}

	public function testExportStorageWithSchema(): void
	{
		$storage = $this->createStorage(
			'export_storage_' . uniqid(),
			'schema',
			[ 'col1' => 'text', 'col2' => 'integer' ]
		);

		$exporter = $this->getExporter();
		$result   = $exporter->export( $storage );

		$this->assertArrayHasKey( $storage->getRef(), $result );
		$this->assertEquals( 'Storage', $result[ $storage->getRef() ]['_entity'] );
		$this->assertEquals( 'schema', $result[ $storage->getRef() ]['config']['type'] );
		$this->assertArrayHasKey( 'schema', $result[ $storage->getRef() ]['config'] );
		$this->assertArrayHasKey( 'columns', $result[ $storage->getRef() ]['config']['schema'] );
		$this->assertArrayHasKey( 'col1', $result[ $storage->getRef() ]['config']['schema']['columns'] );
		$this->assertArrayHasKey( 'col2', $result[ $storage->getRef() ]['config']['schema']['columns'] );
	}

	public function testExportAutomationWithFlowAndRoutinesAndStorage(): void
	{
		$storage = $this->createStorage(
			'automation_test_storage_' . uniqid(),
			'schema',
			[ 'input_col' => 'text' ]
		);

		$routine1 = RoutineModel::create();
		$routine1->setName( 'automation_routine1_' . uniqid() );
		$routine1->setConfig( [ 'tasks' => [] ] );
		$routine1->save( true );

		$routine2 = RoutineModel::create();
		$routine2->setName( 'automation_routine2_' . uniqid() );
		$routine2->setConfig( [ 'tasks' => [
			[
				'_class' => 'Store',
				'storage' => $storage->getId(),
			]
		] ] );
		$routine2->save( true );

		$flow = FlowModel::create();
		$flow->setName( 'automation_flow_' . uniqid() );
		$flow->setConfig( [ 'steps' => [ $routine1->getId(), $routine2->getId() ] ] );
		$flow->save( true );

		$automation = \SyncEngine\Model\AutomationModel::create();
		$automation->setName( 'automation_test_' . uniqid() );
		$automation->setEndpoint( 'ep_automation_' . uniqid() );
		$automation->setConfig( [ 'actions' => 'flow', 'flow' => $flow->getId() ] );
		$automation->save( true );

		$exporter = $this->getExporter();
		$result   = $exporter->export( $automation );

		// Automation itself should be exported.
		$this->assertArrayHasKey( $automation->getRef(), $result );
		$this->assertEquals( 'Automation', $result[ $automation->getRef() ]['_entity'] );

		// Flow dependency should be resolved to ref.
		$this->assertArrayHasKey( $flow->getRef(), $result );
		$this->assertEquals( 'Flow', $result[ $flow->getRef() ]['_entity'] );

		// Both routine dependencies should be present.
		$this->assertArrayHasKey( $routine1->getRef(), $result );
		$this->assertArrayHasKey( $routine2->getRef(), $result );
		$this->assertEquals( 'Routine', $result[ $routine1->getRef() ]['_entity'] );
		$this->assertEquals( 'Routine', $result[ $routine2->getRef() ]['_entity'] );

		// Flow config should contain the flow steps.
		$flowConfig = $result[ $flow->getRef() ]['config'];
		$this->assertArrayHasKey( 'steps', $flowConfig );
		$this->assertIsArray( $flowConfig['steps'] );
		$this->assertCount( 2, $flowConfig['steps'] );

		// Storage dependency should be present.
		$this->assertArrayHasKey( $storage->getRef(), $result );
		$this->assertEquals( 'Storage', $result[ $storage->getRef() ]['_entity'] );
		$this->assertEquals( 'schema', $result[ $storage->getRef() ]['config']['type'] );

		// Automation config should reference the flow ref.
		$automationConfig = $result[ $automation->getRef() ]['config'];
		$this->assertEquals( 'flow', $automationConfig['actions'] );
		$this->assertEquals( $flow->getRef(), $automationConfig['flow'] );

		// Flow config steps should contain the correct numeric IDs (exporter preserves them).
		$flowConfig = $result[ $flow->getRef() ]['config'];
		$this->assertIsArray( $flowConfig['steps'] );
		$this->assertEquals( $routine1->getId(), $flowConfig['steps'][0] );
		$this->assertEquals( $routine2->getId(), $flowConfig['steps'][1] );

		// Verify dependencies through the normalizer on the exported flow.
		$normalizer = static::getContainer()->get( \SyncEngine\Service\ModelNormalizer::class );
		$depResult  = $normalizer->normalize( $flow, true );

		$this->assertArrayHasKey( '_dependencies', $depResult );
		$this->assertIsArray( $depResult['_dependencies'] );
		$this->assertArrayHasKey( 'routine:' . $routine1->getId(), $depResult['_dependencies'] );
		$this->assertArrayHasKey( 'routine:' . $routine2->getId(), $depResult['_dependencies'] );

		// Verify the dependency entries match the correct entities.
		$dep1 = $depResult['_dependencies']['routine:' . $routine1->getId()];
		$this->assertEquals( 'Routine', $dep1['_entity'] );
		$this->assertEquals( $routine1->getId(), $dep1['id'] );

		$dep2 = $depResult['_dependencies']['routine:' . $routine2->getId()];
		$this->assertEquals( 'Routine', $dep2['_entity'] );
		$this->assertEquals( $routine2->getId(), $dep2['id'] );
	}

	private function createRoutine( string $name ): RoutineModel
	{
		$routine = RoutineModel::create();
		$routine->setName( $name );
		$routine->setConfig( [ 'tasks' => [] ] );
		$routine->save( true );

		return $routine;
	}

	private function createStorage( string $name, string $type, array $data ): StorageModel
	{
		$storage = StorageModel::create();
		$storage->setName( $name );
		$storage->setType( $type );
		$config = [ 'type' => $type ];

		if ( $type === 'schema' ) {
			$config['schema'] = [ 'columns' => $data ];
		} elseif ( $type === 'mapper' ) {
			$rows = [];
			foreach ( $data as $source => $target ) {
				$rows[] = [ 'source' => (string) $source, 'target' => (string) $target ];
			}
			$storage->setData( $rows );
		}

		$storage->setConfig( $config );
		$storage->save( true );

		return $storage;
	}
}
