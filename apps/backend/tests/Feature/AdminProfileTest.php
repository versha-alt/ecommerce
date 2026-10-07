<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_editing_validation_and_access(): void
    {
        $user = User::create(['name' => 'Staff', 'email' => 'staff@example.com', 'password' => Hash::make('ProfileTest!2026'), 'role' => 'Sales', 'status' => 'Active']);
        $other = User::create(['name' => 'Other', 'email' => 'other@example.com', 'password' => Hash::make('ProfileTest!2026'), 'role' => 'Admin', 'status' => 'Active']);
        $this->patchJson('/api/v1/auth/profile', [])->assertUnauthorized();
        $token = $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'ProfileTest!2026'])->assertOk()->json('token');
        $this->withToken($token);
        $version = $user->fresh()->version;
        $this->patchJson('/api/v1/auth/profile', ['name' => 'Updated', 'email' => 'UPDATED@example.com', 'version' => $version, 'id' => $other->id, 'role' => 'Admin', 'status' => 'Inactive'])->assertOk()->assertJsonPath('email', 'updated@example.com')->assertJsonPath('role', 'Sales')->assertJsonPath('status', 'Active')->assertJsonMissingPath('password');
        $this->assertSame('Other', $other->fresh()->name);
        $this->assertSame('Updated', $user->fresh()->name);
        $this->patchJson('/api/v1/auth/profile', ['name' => 'Stale', 'email' => 'updated@example.com', 'version' => $version])->assertStatus(409);
        $version = $user->fresh()->version;
        $this->patchJson('/api/v1/auth/profile', ['name' => '', 'email' => 'invalid', 'version' => $version])->assertUnprocessable()->assertJsonValidationErrors(['name', 'email']);
        $this->patchJson('/api/v1/auth/profile', ['name' => 'Staff', 'email' => 'OTHER@example.com', 'version' => $version])->assertUnprocessable()->assertJsonValidationErrors('email');
    }
}
