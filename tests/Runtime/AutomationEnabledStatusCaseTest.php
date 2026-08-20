<?php

namespace SyncEngine\Tests\Runtime;

use SyncEngine\Model\Enum\EntityStatus;
use SyncEngine\Tests\TestCase\RuntimeScenarioTestCase;
use SyncEngine\Runtime\ExecuteScheduler;

class AutomationEnabledStatusCaseTest extends RuntimeScenarioTestCase
{
	public function test_automation_can_schedule_when_enabled(): void
	{
		// Create a basic automation in the default enabled state.
		$automation = $this->createAutomationScenario( 'test_automation_enabled', [] );

		// Verify it is enabled by default.
		$this->assertTrue( $automation->isEnabled() );
		$this->assertFalse( $automation->isDisabled() );

		// Verify the scheduler allows scheduling.
		$result = $this->scheduler()->canSchedule( $automation );

		$this->assertTrue( $result, 'Automation should be schedulable when enabled.' );
	}

	public function test_automation_cannot_schedule_when_disabled(): void
	{
		// Create and persist an automation.
		$automation = $this->createAutomationScenario( 'test_automation_disabled', [] );

		// Disable the automation.
		$automation->disable( true );

		// Verify state.
		$this->assertFalse( $automation->isEnabled() );
		$this->assertTrue( $automation->isDisabled() );
		$this->assertSame( EntityStatus::DISABLED, $automation->getStatus() );

		// Verify the scheduler rejects scheduling.
		$result = $this->scheduler()->canSchedule( $automation );

		$this->assertFalse( $result, 'Automation should not be schedulable when disabled.' );
	}

	public function test_automation_can_schedule_after_re_enable(): void
	{
		// Create, disable, then re-enable.
		$automation = $this->createAutomationScenario( 'test_automation_reenable', [] );
		$automation->disable( true );

		$this->assertFalse( $this->scheduler()->canSchedule( $automation ) );

		$automation->enable( true );

		$this->assertTrue( $this->scheduler()->canSchedule( $automation ), 'Automation should be schedulable after re-enabling.' );
	}

	public function test_can_accept_new_requests_when_disabled(): void
	{
		$automation = $this->createAutomationScenario( 'test_automation_can_accept', [] );

		// Default: enabled, so can accept.
		$this->assertTrue( $automation->canAcceptNewRequests() );

		// Disable.
		$automation->disable( true );
		$this->assertFalse( $automation->canAcceptNewRequests() );

		// Re-enable.
		$automation->enable( true );
		$this->assertTrue( $automation->canAcceptNewRequests() );
	}

	public function test_can_run_now_when_disabled(): void
	{
		$automation = $this->createAutomationScenario( 'test_automation_can_run', [] );

		// Default: enabled, no active runs.
		$this->assertTrue( $automation->canRunNow() );

		// Disable.
		$automation->disable( true );
		$this->assertFalse( $automation->canRunNow() );

		// Re-enable.
		$automation->enable( true );
		$this->assertTrue( $automation->canRunNow() );
	}

	public function test_trashed_automation_cannot_schedule(): void
	{
		$automation = $this->createAutomationScenario( 'test_automation_trashed', [] );

		// Trash the automation.
		$automation->trash( true );

		$this->assertTrue( $automation->isTrashed() );
		$this->assertFalse( $automation->isEnabled() );
		$this->assertFalse( $this->scheduler()->canSchedule( $automation ) );
	}

	public function test_schedule_new_trace_rejected_when_disabled(): void
	{
		// Create automation + routine.
		$routine = $this->createRoutineScenario( 'test_routine', [] );

		$automation = $this->createAutomationScenario(
			'test_automation_schedule_reject',
			[
				'routines' => [ 'id' => $routine->getId() ],
			]
		);

		// Disable.
		$automation->disable( true );

		// Attempt to schedule a new trace.
		$result = $this->scheduler()->scheduleNewTrace( $automation, $this->getContext() );

		$this->assertTrue( $result->isRejected(), 'Schedule result should be rejected.' );
		$this->assertSame( 'automation_not_enabled', $result->getReason() );
	}

	private function scheduler(): ExecuteScheduler
	{
		return $this->getContainer()->get( ExecuteScheduler::class );
	}
}
