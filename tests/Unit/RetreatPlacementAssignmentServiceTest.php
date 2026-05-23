<?php

namespace Tests\Unit;

use App\Models\RetreatAtelier;
use App\Models\RetreatParticipant;
use App\Services\RetreatPlacementAssignmentService;
use PHPUnit\Framework\TestCase;

class RetreatPlacementAssignmentServiceTest extends TestCase
{
    private RetreatPlacementAssignmentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new RetreatPlacementAssignmentService;
    }

    public function test_participant_type_from_hebergement(): void
    {
        $this->assertSame('external', $this->service->participantTypeFromHebergement('externe'));
        $this->assertSame('internal', $this->service->participantTypeFromHebergement('interne'));
        $this->assertSame('internal', $this->service->participantTypeFromHebergement(null));
    }

    public function test_is_external_participant(): void
    {
        $external = new RetreatParticipant([
            'participant_type' => 'external',
            'hebergement_choice' => 'externe',
        ]);

        $internal = new RetreatParticipant([
            'participant_type' => 'internal',
            'hebergement_choice' => 'interne',
        ]);

        $this->assertTrue($this->service->isExternalParticipant($external));
        $this->assertFalse($this->service->requiresChambrePlacement($external));
        $this->assertFalse($this->service->isExternalParticipant($internal));
        $this->assertTrue($this->service->requiresChambrePlacement($internal));
    }

    public function test_age_band_and_atelier_numbers(): void
    {
        $this->assertSame('15-19', $this->service->ageBand(17));
        $this->assertSame('20-24', $this->service->ageBand(22));
        $this->assertContains(8, $this->service->atelierNumbersForAge(22));
        $this->assertNotContains(8, $this->service->atelierNumbersForAge(35));
    }

    public function test_matches_atelier_age_range(): void
    {
        $atelier = new RetreatAtelier(['age_min' => 20, 'age_max' => 24]);

        $this->assertTrue($this->service->matchesAtelierAgeRange($atelier, 22));
        $this->assertFalse($this->service->matchesAtelierAgeRange($atelier, 18));
        $this->assertTrue($this->service->hasAgeRangeDefined($atelier));
    }
}
