<?php

namespace SyncEngine\Tests\Service;

use SyncEngine\Model\AutomationModel;
use SyncEngine\Model\FlowModel;
use SyncEngine\Model\RoutineModel;
use SyncEngine\Model\StorageModel;
use SyncEngine\Service\ModelDependencyManager;
use SyncEngine\Tests\TestCase\BaseTestCase;

class ModelDependencyManagerExtensiveTest extends BaseTestCase
{
    public function testGetDependenciesWithFullAutomationConfig(): void
    {
	    $flowRoutine = RoutineModel::create();
	    $flowRoutine->setName( 'flow_step_' . uniqid() );
	    $flowRoutine->setConfig( [] );
	    $flowRoutine->save( true );

        // Create referenced entities
        $flow = FlowModel::create();
        $flow->setName( 'automation_flow_' . uniqid() );
        $flow->setConfig( [ 'steps' => [ $flowRoutine->getId() ] ] );
        $flow->save( true );

		$storage = StorageModel::create();
		$storage->setName( 'automation_storage_' . uniqid() );
		$storage->setConfig( [] );
		$storage->save( true );

        $routine = RoutineModel::create();
        $routine->setName( 'automation_trigger_' . uniqid() );
        $routine->setConfig( [
            'tasks' => [
                [
                    '_class' => 'Store',
                    'storage' => $storage->getId(),
                ],
            ],
        ] );
        $routine->save( true );

        // Build automation config matching the user's structure exactly.
        $automation = AutomationModel::create();
        $automation->setName( 'full_config_auto_' . uniqid() );
        $automation->setEndpoint( 'full-config-ep-' . uniqid() );
        $automation->setConfig( [
            'source'    => [ 'retrieve' ],
			'actions'   => 'flow',
            'retrieve'  => [
                [
                    '_class' => 'Trigger',
					'action' => 'routine',
                    'routine' => $routine->getId(),
                ],
            ],
            'flow'     => $flow->getId(),
        ] );
        $automation->save( true );

        $manager = static::getContainer()->get( ModelDependencyManager::class );

        // Direct scan by manager: should find flow:ID and routine:ID.
        $deps = $manager->getConfigDependencies(
            $automation->getConfig(),
            $automation->getFields(),
            recurse: false
        );

        $this->assertArrayHasKey( 'flow:' . $flow->getId(), $deps, 'Should find the automation flow dependency' );
	    $this->assertArrayHasKey( 'routine:' . $routine->getId(), $deps, 'Should find the automation routine dependency' );
	    $this->assertArrayNotHasKey( 'routine:' . $flowRoutine->getId(), $deps, 'Should not include the flow step routine' );
	    $this->assertArrayNotHasKey( 'storage:' . $storage->getId(), $deps, 'Should not find the storage dependency in direct scan' );

	    // Direct scan by model: should find flow:ID and routine:ID. Should not recurse by default.
	    $deps = $automation->getConfigDependencies();

	    $this->assertArrayHasKey( 'flow:' . $flow->getId(), $deps, 'Should find the automation flow dependency' );
	    $this->assertArrayHasKey( 'routine:' . $routine->getId(), $deps, 'Should find the automation routine dependency' );
	    $this->assertArrayNotHasKey( 'routine:' . $flowRoutine->getId(), $deps, 'Should not include the flow step routine' );
	    $this->assertArrayNotHasKey( 'storage:' . $storage->getId(), $deps, 'Should not find the storage dependency in direct scan' );

        // Full tree scan: should find flow:ID, routine:ID, and storage:ID (recursed through routine).
        $allDeps = $manager->getDependencies( $automation );

        $this->assertArrayHasKey( 'flow:' . $flow->getId(), $allDeps, 'Full tree should include the automation flow' );
		$this->assertArrayHasKey( 'routine:' . $flowRoutine->getId(), $allDeps, 'Full tree should include the flow step routine' );
	    $this->assertArrayHasKey( 'routine:' . $routine->getId(), $allDeps, 'Full tree should include the automation routine' );
		$this->assertArrayHasKey( 'storage:' . $storage->getId(), $allDeps, 'Full tree should include the storage since it is referenced by the routine' );
    }
}
