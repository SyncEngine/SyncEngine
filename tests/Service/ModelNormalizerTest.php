<?php

namespace SyncEngine\Tests\Service;

use SyncEngine\Model\FlowModel;
use SyncEngine\Model\RoutineModel;
use SyncEngine\Model\StorageModel;
use SyncEngine\Service\ModelNormalizer;
use SyncEngine\Tests\TestCase\BaseTestCase;

class ModelNormalizerTest extends BaseTestCase
{
	private function getNormalizer(): ModelNormalizer
	{
		return static::getContainer()->get( ModelNormalizer::class );
	}

	public function testNormalizeRoutineIncludesEntityAndSupports(): void
	{
		$routine = $this->createRoutine( 'norm_routine_' . uniqid() );

		$normalizer = $this->getNormalizer();
		$result     = $normalizer->normalize( $routine );

		$this->assertEquals( 'Routine', $result['_entity'] );
		$this->assertIsArray( $result['_supports'] );
		$this->assertTrue( $result['_supports']['config'] );
		$this->assertTrue( $result['_supports']['tags'] );
		$this->assertTrue( $result['_supports']['supervisor'] );

		// Dependencies and dependents should not be included when not requested.
		$this->assertArrayNotHasKey( '_dependencies', $result );
		$this->assertArrayNotHasKey( '_dependents', $result );

		// Tags should be present for Taggable models.
		$this->assertArrayHasKey( 'tags', $result );
	}

	public function testNormalizeRoutineWithDependencies(): void
	{
		$routine = $this->createRoutine( 'norm_dep_routine_' . uniqid() );

		$flow = FlowModel::create();
		$flow->setName( 'norm_flow_' . uniqid() );
		$flow->setConfig( [ 'steps' => [ $routine->getId() ] ] );
		$flow->save( true );

		$normalizer = $this->getNormalizer();
		$result     = $normalizer->normalize( $flow, true );

		$this->assertArrayHasKey( '_dependencies', $result );
		$this->assertIsArray( $result['_dependencies'] );
		// Dependencies use entity:id key format.
		$this->assertArrayHasKey( 'routine:' . $routine->getId(), $result['_dependencies'] );

		// The dependency should be the correct routine.
		$dep = $result['_dependencies']['routine:' . $routine->getId()];
		$this->assertEquals( 'Routine', $dep['_entity'] );
		$this->assertEquals( $routine->getId(), $dep['id'] );
	}

	public function testNormalizeRoutineWithDependents(): void
	{
		$routine = $this->createRoutine( 'norm_dependee_' . uniqid() );

		$flow = FlowModel::create();
		$flow->setName( 'norm_dependent_flow_' . uniqid() );
		$flow->setConfig( [ 'steps' => [ $routine->getId() ] ] );
		$flow->save( true );

		$normalizer = $this->getNormalizer();
		$result     = $normalizer->normalize( $routine, false, true );

		$this->assertArrayHasKey( '_dependents', $result );
		$this->assertIsArray( $result['_dependents'] );
		// Dependents use entity:id key format.
		$this->assertArrayHasKey( 'flow:' . $flow->getId(), $result['_dependents'] );
		$this->assertEquals( $flow->getId(), $result['_dependents']['flow:' . $flow->getId()]['id'] );
	}

	public function testNormalizeStorageWithSchema(): void
	{
		$storage = $this->createStorage(
			'norm_storage_' . uniqid(),
			'schema',
			[ 'col1' => 'text', 'col2' => 'integer' ]
		);

		$normalizer = $this->getNormalizer();
		$result     = $normalizer->normalize( $storage );

		$this->assertEquals( 'Storage', $result['_entity'] );
		$this->assertArrayHasKey( 'config', $result );
		$this->assertEquals( 'schema', $result['config']['type'] );
		$this->assertArrayHasKey( 'schema', $result['config'] );
		$this->assertArrayHasKey( 'columns', $result['config']['schema'] );
		$this->assertArrayHasKey( 'col1', $result['config']['schema']['columns'] );
		$this->assertArrayHasKey( 'col2', $result['config']['schema']['columns'] );
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
		$config  = [ 'type' => $type ];

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
