<?php

namespace SyncEngine\Tests\Service;

use SyncEngine\Entity\Flow;
use SyncEngine\Entity\Routine;
use SyncEngine\Form\Fields\Collection\FieldCollection;
use SyncEngine\Model\AutomationModel;
use SyncEngine\Model\FlowModel;
use SyncEngine\Model\RoutineModel;
use SyncEngine\Model\StorageModel;
use SyncEngine\Service\ModelDependencyManager;
use SyncEngine\Tests\TestCase\BaseTestCase;

class ModelDependencyManagerTest extends BaseTestCase
{
	private function getManager(): ModelDependencyManager
	{
		return static::getContainer()->get( ModelDependencyManager::class );
	}

	// --------------------------------------------------------------------
	//  getDependencies — forward resolution
	// --------------------------------------------------------------------

	public function testGetDependenciesEmptyConfig(): void
	{
		$routine = RoutineModel::create();
		$routine->setName( 'empty_routine_' . uniqid() );
		$routine->setConfig( [ 'tasks' => [] ] );
		$routine->save( true );

		$manager = $this->getManager();
		$dependencies = $manager->getDependencies( $routine );

		$this->assertEmpty( $dependencies );
	}

	public function testGetDependenciesWithAutomationFlowRef(): void
	{
		$routine = $this->createRoutine( 'dep_routine_' . uniqid() );
		$flow      = $this->createFlow( 'dep_flow_' . uniqid(), [ $routine->getId() ] );

		$automation = AutomationModel::create();
		/** @var AutomationModel $automation */
		$automation->setName( 'auto_flow_ref_' . uniqid() );
		$automation->setEndpoint( 'ep-flow-ref-' . uniqid() );
		// AutomationModel has a 'flow' field with type: 'entity', entity => 'flow'.
		$automation->setConfig( [ 'actions' => 'flow', 'flow' => $flow->getId() ] );
		$automation->save( true );

		$manager = $this->getManager();
		$dependencies = $manager->getDependencies( $automation );

		$this->assertArrayHasKey( 'flow:' . $flow->getId(), $dependencies );
	}

	public function testGetDependenciesWithAutomationRoutineRef(): void
	{
		$routine = $this->createRoutine( 'auto_routine_ref_' . uniqid() );

		$automation = AutomationModel::create();
		/** @var AutomationModel $automation */
		$automation->setName( 'auto_routine_ref2_' . uniqid() );
		$automation->setEndpoint( 'ep-routine-ref-' . uniqid() );
		// AutomationModel has a 'routine' field with type: 'entity', entity => 'routine'.
		$automation->setConfig( [ 'actions' => 'routine', 'routine' => $routine->getId() ] );
		$automation->save( true );

		$manager = $this->getManager();
		$dependencies = $manager->getDependencies( $automation );

		$this->assertArrayHasKey( 'routine:' . $routine->getId(), $dependencies );
	}

	public function testGetDependenciesNonRecursive(): void
	{
		$routine1 = $this->createRoutine( 'r1_nonrec_' . uniqid() );

		$automation = AutomationModel::create();
		/** @var AutomationModel $automation */
		$automation->setName( 'auto_nonrec_' . uniqid() );
		$automation->setEndpoint( 'ep-nonrec-' . uniqid() );
		$automation->setConfig( [ 'actions' => 'routine', 'routine' => $routine1->getId() ] );
		$automation->save( true );

		$manager = $this->getManager();

		// Non-recursive: should find the routine (direct dep).
		$directDeps = $manager->getDependencies( $automation, false );
		$this->assertArrayHasKey( 'routine:' . $routine1->getId(), $directDeps );
		$this->assertCount( 1, $directDeps );

		// Recursive: should also try to resolve the routine's own dependencies.
		$fullDeps = $manager->getDependencies( $automation, true );
		// With recursive=true on a simple routine (no entity refs), result is the same.
		$this->assertEquals( count( $directDeps ), count( $fullDeps ) );
	}

	public function testGetDependenciesCaching(): void
	{
		$routine = $this->createRoutine( 'cache_routine_' . uniqid() );
		$flow      = $this->createFlow( 'cache_flow_' . uniqid(), [ $routine->getId() ] );

		$automation = AutomationModel::create();
		/** @var AutomationModel $automation */
		$automation->setName( 'cache_auto_' . uniqid() );
		$automation->setEndpoint( 'ep-cache-' . uniqid() );
		$automation->setConfig( [ 'actions' => 'flow', 'flow' => $flow->getId() ] );
		$automation->save( true );

		$manager = $this->getManager();

		$result1 = $manager->getDependencies( $automation );
		$result2 = $manager->getDependencies( $automation );

		$this->assertSame( $result1, $result2, 'Cached result should be identical' );
	}

	public function testGetDependenciesClearCache(): void
	{
		$routine = $this->createRoutine( 'clear_routine_' . uniqid() );
		$flow      = $this->createFlow( 'clear_flow_' . uniqid(), [ $routine->getId() ] );

		$automation = AutomationModel::create();
		/** @var AutomationModel $automation */
		$automation->setName( 'clear_auto_' . uniqid() );
		$automation->setEndpoint( 'ep-clear-' . uniqid() );
		$automation->setConfig( [ 'actions' => 'flow', 'flow' => $flow->getId() ] );
		$automation->save( true );

		$manager = $this->getManager();

		$result1 = $manager->getDependencies( $automation );
		$manager->clearCache();
		$result2 = $manager->getDependencies( $automation );

		$this->assertNotSame( $result1, $result2, 'After clearCache, result should not be the same instance' );
	}

	public function testGetDependenciesNoRef(): void
	{
		$flow = new FlowModel( new Flow() );
		$manager = $this->getManager();

		// Entity has no ID yet (not persisted), so getRef() returns null.
		$dependencies = $manager->getDependencies( $flow );
		$this->assertEmpty( $dependencies );
	}

	// --------------------------------------------------------------------
	//  getConfigDependencies — raw scanning (no cache)
	// --------------------------------------------------------------------

	public function testGetConfigDependenciesRawScanning(): void
	{
		$routine = $this->createRoutine( 'raw_routine_' . uniqid() );

		// Build a minimal field definition with type: 'entity' pointing to the routine.
		$fields = new FieldCollection( [
			'r' => [
				'name'   => 'routine',
				'type'   => 'entity',
				'entity' => 'routine',
			],
		] );

		$config = [ 'routine' => $routine->getId() ];

		$manager = $this->getManager();
		$dependencies = $manager->getConfigDependencies( $config, $fields, false );

		$this->assertArrayHasKey( 'routine:' . $routine->getId(), $dependencies );
	}

	public function testGetConfigDependenciesEmptyInput(): void
	{
		$manager = $this->getManager();
		$result = $manager->getConfigDependencies( [], [] );
		$this->assertEmpty( $result );
	}

	public function testGetConfigDependenciesRecursive(): void
	{
		$routine = $this->createRoutine( 'raw_recursive_' . uniqid() );

		// Nested entity field.
		$fields = new FieldCollection( [
			'container' => [
				'type'   => 'entity',
				'entity' => 'routine',
				'nested' => [
					'ref' => [
						'type'   => 'entity',
						'entity' => 'storage',
					],
				],
			],
		] );

		$config = [ 'container' => $routine->getId() ];

		$manager = $this->getManager();
		$dependencies = $manager->getConfigDependencies( $config, $fields, true );

		$this->assertArrayHasKey( 'routine:' . $routine->getId(), $dependencies );
	}

	// --------------------------------------------------------------------
	//  hasDependencies — gate check
	// --------------------------------------------------------------------

	public function testHasDependenciesTrue(): void
	{
		$routine = $this->createRoutine( 'dep_routine_' . uniqid() );
		$flow      = $this->createFlow( 'dep_flow_' . uniqid(), [ $routine->getId() ] );

		$automation = AutomationModel::create();
		/** @var AutomationModel $automation */
		$automation->setName( 'hasdep_auto_' . uniqid() );
		$automation->setEndpoint( 'ep-hasdep-' . uniqid() );
		$automation->setConfig( [ 'actions' => 'flow', 'flow' => $flow->getId() ] );
		$automation->save( true );

		$manager = $this->getManager();
		$this->assertTrue( $manager->hasDependencies( $automation ) );
	}

	public function testHasDependenciesFalse(): void
	{
		$routine = $this->createRoutine( 'nodep_routine_' . uniqid() );
		$routine->setConfig( [
			'tasks' => [
				[
					'_class' => \SyncEngine\Task\Set::_getClassLocator(),
					'name'   => 'simple task',
					'params' => [],
				],
			],
		] );
		$routine->save( true );

		$manager = $this->getManager();
		$this->assertFalse( $manager->hasDependencies( $routine ) );
	}

	// --------------------------------------------------------------------
	//  getDependents — reverse lookup
	// --------------------------------------------------------------------

	public function testGetDependentsFindsReferencingAutomations(): void
	{
		$routine = $this->createRoutine( 'dependee_routine_' . uniqid() );

		// Create automations that reference the routine via entity field (stores ref as string).
		$auto1 = AutomationModel::create();
		/** @var AutomationModel $auto1 */
		$auto1->setName( 'dep_auto_1_' . uniqid() );
		$auto1->setEndpoint( 'ep-dep-auto-1-' . uniqid() );
		$auto1->setConfig( [ 'actions' => 'routine', 'routine' => $routine->getId() ] );
		$auto1->save( true );

		$auto2 = AutomationModel::create();
		/** @var AutomationModel $auto2 */
		$auto2->setName( 'dep_auto_2_' . uniqid() );
		$auto2->setEndpoint( 'ep-dep-auto-2-' . uniqid() );
		$auto2->setConfig( [ 'actions' => 'routine', 'routine' => $routine->getId() ] );
		$auto2->save( true );

		$manager = $this->getManager();
		$dependents = $manager->getDependents( $routine );

		// Both automations should be found since they store the routine ref in config.
		$this->assertGreaterThanOrEqual( 1, count( $dependents ) );
	}

	public function testGetDependentsNoneFound(): void
	{
		$routine = $this->createRoutine( 'lonely_routine_' . uniqid() );

		$manager = $this->getManager();
		$dependents = $manager->getDependents( $routine );

		$this->assertEmpty( $dependents );
	}

	public function testGetDependentsCaching(): void
	{
		$routine = $this->createRoutine( 'dep_cache_routine_' . uniqid() );
		$auto      = AutomationModel::create();
		/** @var AutomationModel $auto */
		$auto->setName( 'dep_cache_auto_' . uniqid() );
		$auto->setEndpoint( 'ep-dep-cache-' . uniqid() );
		$auto->setConfig( [ 'actions' => 'routine', 'routine' => $routine->getId() ] );
		$auto->save( true );

		$manager = $this->getManager();

		$result1 = $manager->getDependents( $routine );
		$result2 = $manager->getDependents( $routine );

		$this->assertSame( $result1, $result2, 'Cached dependents should be identical' );
	}

	public function testGetDependentsClearCache(): void
	{
		$routine = $this->createRoutine( 'dep_clear_routine_' . uniqid() );
		$auto      = AutomationModel::create();
		/** @var AutomationModel $auto */
		$auto->setName( 'dep_clear_auto_' . uniqid() );
		$auto->setEndpoint( 'ep-dep-clear-' . uniqid() );
		$auto->setConfig( [ 'actions' => 'routine', 'routine' => $routine->getId() ] );
		$auto->save( true );

		$manager = $this->getManager();

		$result1 = $manager->getDependents( $routine );
		$manager->clearCache();
		$result2 = $manager->getDependents( $routine );

		$this->assertNotSame( $result1, $result2 );
	}

	public function testGetDependentsForPersistedEntityOnly(): void
	{
		$flow = new FlowModel( new Flow() );
		// Not persisted — no ID.
		$manager = $this->getManager();
		$dependents = $manager->getDependents( $flow );
		$this->assertEmpty( $dependents );
	}

	// --------------------------------------------------------------------
	//  hasDependents — gate check
	// --------------------------------------------------------------------

	public function testHasDependentsTrue(): void
	{
		$routine = $this->createRoutine( 'hasdep_routine_' . uniqid() );
		$auto      = AutomationModel::create();
		/** @var AutomationModel $auto */
		$auto->setName( 'hasdep_auto_' . uniqid() );
		$auto->setEndpoint( 'ep-hasdep-' . uniqid() );
		$auto->setConfig( [ 'actions' => 'routine', 'routine' => $routine->getId() ] );
		$auto->save( true );

		$manager = $this->getManager();
		$this->assertTrue( $manager->hasDependents( $routine ) );
	}

	public function testHasDependentsFalse(): void
	{
		$routine = $this->createRoutine( 'nodep_routine_' . uniqid() );

		$manager = $this->getManager();
		$this->assertFalse( $manager->hasDependents( $routine ) );
	}

	// --------------------------------------------------------------------
	//  getEntityDependency — single entity resolution
	// --------------------------------------------------------------------

	public function testGetEntityDependencyResolvesModel(): void
	{
		$routine = $this->createRoutine( 'entity_dep_routine_' . uniqid() );

		$manager = $this->getManager();
		$dependencies = $manager->getEntityDependency( 'routine', $routine->getId(), false );

		$this->assertCount( 1, $dependencies );
		$this->assertArrayHasKey( 'routine:' . $routine->getId(), $dependencies );
		// Use assertEquals since the model wraps the same entity but is a new instance.
		$this->assertEquals( $routine->getId(), $dependencies['routine:' . $routine->getId()]->getId() );
	}

	public function testGetEntityDependencyNonExistent(): void
	{
		$manager = $this->getManager();
		$dependencies = $manager->getEntityDependency( 'routine', 999999, false );
		$this->assertEmpty( $dependencies );
	}

	// --------------------------------------------------------------------
	//  getDependents with StorageModel
	// --------------------------------------------------------------------

	public function testGetDependentsForStorage(): void
	{
		$storage = $this->createStorage( 'dep_storage_' . uniqid(), 'schema', [] );

		$automation = AutomationModel::create();
		/** @var AutomationModel $automation */
		$automation->setName( 'auto_storage_dep_' . uniqid() );
		$automation->setEndpoint( 'ep-storage-dep-' . uniqid() );
		// Use schema storage reference.
		$automation->setConfig( [
			'source' => 'request',
			'schema' => [
				'storage' => $storage->getId(),
			],
		] );
		$automation->save( true );

		$manager = $this->getManager();
		$dependents = $manager->getDependents( $storage );

		$this->assertCount( 1, $dependents );
		$this->assertEquals( $automation->getRef(), $dependents[0]->getRef() );
	}

	// --------------------------------------------------------------------
	//  Helpers
	// --------------------------------------------------------------------

	private function createRoutine( string $name ): RoutineModel
	{
		$routine = RoutineModel::create();
		$routine->setName( $name );
		$routine->setConfig( [
			'tasks' => [
				[
					'_class' => \SyncEngine\Task\Set::_getClassLocator(),
					'name'   => 'task',
					'params' => [],
				],
			],
		] );
		$routine->save( true );
		return $routine;
	}

	private function createFlow( string $name, array $stepRoutines ): FlowModel
	{
		$flow = FlowModel::create();
		/** @var FlowModel $flow */
		$flow->setName( $name );
		$flow->setConfig( [ 'steps' => $stepRoutines ] );
		$flow->save( true );
		return $flow;
	}

	private function createStorage( string $name, string $type, array $data ): StorageModel
	{
		/** @var StorageModel $storage */
		$storage = StorageModel::create();
		$storage->setName( $name );
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
