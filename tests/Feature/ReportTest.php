<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\TechTransfer;
use App\Models\Award;
use App\Models\Engagement;
use App\Models\ImpactAssessment;
use App\Models\Modality;
use App\Models\Resolution;
use App\Models\Campus;
use App\Models\College;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that admin can access modalities report.
     */
    public function test_admin_can_access_modalities_report(): void
    {
        $admin = $this->authenticateAsAdmin();

        Modality::factory()->count(3)->create([
            'status' => 'approved',
            'is_archived' => false,
        ]);

        $response = $this->getJson('/api/reports/modalities');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'modalities' => [
                        'data',
                    ],
                    'statistics',
                ],
            ]);
    }

    /**
     * Test that admin can access resolutions report.
     */
    public function test_admin_can_access_resolutions_report(): void
    {
        $admin = $this->authenticateAsAdmin();

        Resolution::factory()->count(3)->create([
            'is_archived' => false,
        ]);

        $response = $this->getJson('/api/reports/resolutions');

        $response->assertStatus(200);
    }

    /**
     * Test that admin can access users report.
     */
    public function test_admin_can_access_users_report(): void
    {
        $admin = $this->authenticateAsAdmin();

        User::factory()->count(5)->create();

        $response = $this->getJson('/api/reports/users');

        $response->assertStatus(200);
    }

    /**
     * Test that admin can download awards PDF.
     */
    public function test_admin_can_download_awards_pdf(): void
    {
        $admin = $this->authenticateAsAdmin();

        Award::factory()->count(2)->create([
            'status' => 'approved',
            'is_archived' => false,
        ]);

        $response = $this->getJson('/api/reports/awards/pdf');

        $response->assertStatus(200);
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
    }

    /**
     * Test that admin can download engagements PDF.
     */
    public function test_admin_can_download_engagements_pdf(): void
    {
        $admin = $this->authenticateAsAdmin();

        Engagement::factory()->count(2)->create([
            'status' => 'approved',
            'is_archived' => false,
        ]);

        $response = $this->getJson('/api/reports/engagements/pdf');

        $response->assertStatus(200);
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
    }

    /**
     * Test that unauthenticated user cannot access reports.
     */
    public function test_unauthenticated_user_cannot_access_reports(): void
    {
        $response = $this->getJson('/api/reports/modalities');

        $response->assertStatus(401);
    }
}
