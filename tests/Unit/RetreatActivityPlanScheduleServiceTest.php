<?php

namespace Tests\Unit;

use App\Models\RetreatActivityPlan;
use App\Models\RetreatSession;
use App\Services\RetreatActivityPlanScheduleService;
use Carbon\Carbon;
use Tests\TestCase;

class RetreatActivityPlanScheduleServiceTest extends TestCase
{
    private RetreatActivityPlanScheduleService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-05-21 10:00:00');
        $this->service = new RetreatActivityPlanScheduleService;
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_resolves_attendance_deadline(): void
    {
        $session = new RetreatSession(['start_at' => Carbon::parse('2026-05-21 08:00:00')]);
        $plan = new RetreatActivityPlan([
            'starts_at' => '09:00:00',
            'attendance_window_minutes' => 30,
        ]);
        $plan->setRelation('session', $session);

        $deadline = $this->service->resolveAttendanceDeadline($plan);

        $this->assertNotNull($deadline);
        $this->assertSame('2026-05-21 09:30:00', $deadline->format('Y-m-d H:i:s'));
    }

    public function test_approaching_and_past_deadline(): void
    {
        $session = new RetreatSession(['start_at' => Carbon::parse('2026-05-21 08:00:00')]);
        $plan = new RetreatActivityPlan([
            'starts_at' => '09:30:00',
            'attendance_window_minutes' => 30,
        ]);
        $plan->setRelation('session', $session);

        Carbon::setTestNow('2026-05-21 09:57:00');

        $this->assertTrue($this->service->isApproachingDeadline($plan));
        $this->assertFalse($this->service->isPastDeadline($plan));
        $this->assertTrue($this->service->isAttendanceWindowOpen($plan));

        Carbon::setTestNow('2026-05-21 10:01:00');

        $this->assertFalse($this->service->isApproachingDeadline($plan));
        $this->assertTrue($this->service->isPastDeadline($plan));
        $this->assertFalse($this->service->isAttendanceWindowOpen($plan));
    }
}
