<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Models\User;

class UserTest extends TestCase
{
    /**
     * Test that the User model has the correct fillable attributes.
     */
    public function test_user_has_correct_fillable_attributes(): void
    {
        $user = new User();
        $fillable = $user->getFillable();

        $expectedFillable = [
            'first_name',
            'last_name',
            'email',
            'password',
            'role',
            'college_id',
            'is_active',
            'avatar',
        ];

        $this->assertEquals($expectedFillable, $fillable);
    }

    /**
     * Test that the User model hides sensitive attributes.
     */
    public function test_user_has_correct_hidden_attributes(): void
    {
        $user = new User();
        $hidden = $user->getHidden();

        $this->assertContains('password', $hidden);
        $this->assertContains('remember_token', $hidden);
        $this->assertCount(2, $hidden);
    }

    /**
     * Test that the name attribute returns the full name correctly.
     */
    public function test_get_name_attribute_returns_full_name(): void
    {
        $user = new User([
            'first_name' => 'John',
            'last_name' => 'Doe'
        ]);

        $this->assertEquals('John Doe', $user->name);
    }

    /**
     * Test that the name attribute handles empty last name.
     */
    public function test_get_name_attribute_with_only_first_name(): void
    {
        $user = new User([
            'first_name' => 'John',
            'last_name' => ''
        ]);

        $this->assertEquals('John ', $user->name);
    }

    /**
     * Test that the name attribute handles names with special characters.
     */
    public function test_get_name_attribute_with_special_characters(): void
    {
        $user = new User([
            'first_name' => 'María',
            'last_name' => "O'Brien"
        ]);

        $this->assertEquals("María O'Brien", $user->name);
    }

    /**
     * Test that the User model has the correct audit exclude attributes.
     */
    public function test_user_has_correct_audit_exclude_attributes(): void
    {
        $user = new User();
        $reflection = new \ReflectionClass($user);
        $property = $reflection->getProperty('auditExclude');
        $property->setAccessible(true);
        $auditExclude = $property->getValue($user);

        $expectedExclude = ['password', 'remember_token', 'updated_at', 'created_at'];

        $this->assertEquals($expectedExclude, $auditExclude);
    }

    /**
     * Test that the User model uses the correct traits.
     */
    public function test_user_uses_required_traits(): void
    {
        $user = new User();
        $traits = class_uses_recursive($user);

        $this->assertContains('Laravel\Sanctum\HasApiTokens', $traits);
        $this->assertContains('Illuminate\Database\Eloquent\Factories\HasFactory', $traits);
        $this->assertContains('Illuminate\Notifications\Notifiable', $traits);
        $this->assertContains('App\Traits\Auditable', $traits);
    }

    /**
     * Test that the User model has casts method defined.
     */
    public function test_user_has_casts_method(): void
    {
        $user = new User();
        $this->assertTrue(method_exists($user, 'casts'));
    }

    /**
     * Test that the User model uses the correct table name (default).
     */
    public function test_user_uses_correct_table_name(): void
    {
        $user = new User();
        $this->assertEquals('users', $user->getTable());
    }

    /**
     * Test that fillable attributes can be mass assigned.
     */
    public function test_fillable_attributes_can_be_mass_assigned(): void
    {
        $attributes = [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane@example.com',
            'role' => 'admin',
            'college_id' => 1,
            'avatar' => 'avatar.jpg',
        ];

        $user = new User($attributes);

        $this->assertEquals('Jane', $user->first_name);
        $this->assertEquals('Smith', $user->last_name);
        $this->assertEquals('jane@example.com', $user->email);
        $this->assertEquals('admin', $user->role);
        $this->assertEquals(1, $user->college_id);
        $this->assertEquals('avatar.jpg', $user->avatar);
    }

    /**
     * Test that non-fillable attributes cannot be mass assigned.
     */
    public function test_non_fillable_attributes_cannot_be_mass_assigned(): void
    {
        $user = new User([
            'first_name' => 'John',
            'id' => 999, // Not fillable
            'created_at' => now(), // Not fillable
        ]);

        $this->assertEquals('John', $user->first_name);
        $this->assertNull($user->id); // Should not be set
        $this->assertNull($user->created_at); // Should not be set
    }

    /**
     * Test that the User model extends Authenticatable.
     */
    public function test_user_extends_authenticatable(): void
    {
        $user = new User();
        $this->assertInstanceOf(\Illuminate\Foundation\Auth\User::class, $user);
    }
}
