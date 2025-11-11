<?php

namespace Tests\Feature\Admin;

use App\Models\College;
use App\Models\TechTransfer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TechnologyTransferTest extends TestCase
{
    use RefreshDatabase;

    protected function createUserWithCollege(string $role = 'user')
    {
        $college = College::factory()->create();
        $user = User::factory()->create([
            'role' => $role,
            'college_id' => $college->id,
            'password' => Hash::make('secret'),
        ]);

        return [$user, $college];
    }

    public function test_admin_can_list_all_approved_tech_transfers()
    {
        [$admin] = $this->createUserWithCollege('admin');
        TechTransfer::factory()->count(3)->create(['status' => 'approved', 'is_archived' => false]);
        TechTransfer::factory()->count(2)->create(['status' => 'pending']);

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/tech-transfers');

        $response->assertStatus(200)->assertJson(['success' => true]);
        $this->assertCount(3, $response->json('data'));
    }

    public function test_user_can_list_their_approved_tech_transfers_only()
    {
        [$user, $college] = $this->createUserWithCollege('user');

        // One transfer belonging to this user
        TechTransfer::factory()->create([
            'user_id' => $user->id,
            'college_id' => $college->id,
            'status' => 'approved',
            'is_archived' => false,
        ]);

        // Another user's transfer
        [$other] = $this->createUserWithCollege('user');
        TechTransfer::factory()->create([
            'user_id' => $other->id,
            'college_id' => $other->college_id,
            'status' => 'approved',
            'is_archived' => false,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/tech-transfers');

        $response->assertStatus(200)->assertJson(['success' => true]);
        $this->assertCount(1, $response->json('data'));
    }

    public function test_user_can_create_tech_transfer()
    {
        Storage::fake('spaces');

        [$user, $college] = $this->createUserWithCollege('user');
        Sanctum::actingAs($user);

        $payload = [
            'name' => 'Project Alpha',
            'description' => 'A test tech transfer',
            'category' => 'Research',
            'purpose' => 'Testing',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
            'tags' => 'test,alpha',
            'leader' => 'Dr. A',
            'members' => 'Team A',
            'deliverables' => 'Report',
            'agency_partner' => 'Partner X',
            'contact_person' => 'John Doe',
            'contact_phone' => '09171234567',
            'copyright' => 'yes',
            'ip_details' => null,
            'attachment_link' => null,
            'sdg_goals' => ['1', '2'],
        ];

        $response = $this->postJson('/api/tech-transfers', $payload);

        $response->assertStatus(201)->assertJson(['success' => true]);
        $this->assertDatabaseHas('tech_transfers', ['name' => 'Project Alpha', 'user_id' => $user->id]);
    }

    public function test_validation_on_create_requires_fields()
    {
        [$user] = $this->createUserWithCollege('user');
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/tech-transfers', []);

        $response->assertStatus(422)->assertJson(['success' => false]);
    }

    public function test_user_cannot_view_another_users_tech_transfer()
    {
        [$owner] = $this->createUserWithCollege('user');
        [$other] = $this->createUserWithCollege('user');

        $tt = TechTransfer::factory()->create(['user_id' => $owner->id, 'college_id' => $owner->college_id]);

        Sanctum::actingAs($other);
        $response = $this->getJson('/api/tech-transfers/' . $tt->id);

        $response->assertStatus(403);
    }

    public function test_user_can_update_own_tech_transfer()
    {
        [$user] = $this->createUserWithCollege('user');
        Sanctum::actingAs($user);

        $tt = TechTransfer::factory()->create(['user_id' => $user->id, 'college_id' => $user->college_id]);

        $response = $this->patchJson('/api/tech-transfers/' . $tt->id, ['name' => 'Updated Name']);

        $response->assertStatus(200)->assertJson(['success' => true]);
        $this->assertDatabaseHas('tech_transfers', ['id' => $tt->id, 'name' => 'Updated Name']);
    }

    public function test_user_cannot_update_another_users_tech_transfer()
    {
        [$owner] = $this->createUserWithCollege('user');
        [$other] = $this->createUserWithCollege('user');

        $tt = TechTransfer::factory()->create(['user_id' => $owner->id, 'college_id' => $owner->college_id]);

        Sanctum::actingAs($other);
        $response = $this->patchJson('/api/tech-transfers/' . $tt->id, ['name' => 'Bad Update']);

        $response->assertStatus(403);
    }

    public function test_archive_requires_password_and_matches()
    {
        [$user] = $this->createUserWithCollege('user');
        Sanctum::actingAs($user);

        $tt = TechTransfer::factory()->create(['user_id' => $user->id, 'college_id' => $user->college_id]);

        // missing password
        $res = $this->patchJson('/api/tech-transfers/' . $tt->id . '/archive', []);
        $res->assertStatus(422);

        // wrong password
        $res2 = $this->patchJson('/api/tech-transfers/' . $tt->id . '/archive', ['password' => 'wrong']);
        $res2->assertStatus(403);

        // correct password
        $res3 = $this->patchJson('/api/tech-transfers/' . $tt->id . '/archive', ['password' => 'secret']);
        $res3->assertStatus(200)->assertJson(['success' => true]);
        $this->assertTrue(TechTransfer::find($tt->id)->is_archived);
    }
    public function test_get_user_tech_transfers()
    {
        [$user] = $this->createUserWithCollege('user');
        Sanctum::actingAs($user);

        TechTransfer::factory()->count(2)->create(['user_id' => $user->id, 'college_id' => $user->college_id, 'is_archived' => false]);

        $res = $this->getJson('/api/user/tech-transfers');
        $res->assertStatus(200)->assertJson(['success' => true]);
        $this->assertCount(2, $res->json('data'));
    }
}
