<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileSensitiveFieldsGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_superadmin_cannot_change_sensitive_identity_fields_directly(): void
    {
        $user = User::factory()->create([
            'role' => 'user', 'status' => 'active',
            'name' => 'Juan', 'last_name' => 'Perez', 'dni' => '11111111', 'masonic_id' => 'M-1',
        ]);

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/profile', [
                'name' => 'Hackeado',
                'last_name' => 'Alterado',
                'dni' => '99999999',
                'masonic_id' => 'M-999',
                'phone' => '1122334455', // campo no sensible: sí se aplica
            ])
            ->assertOk();

        $fresh = $user->fresh();
        // Los sensibles quedan intactos.
        $this->assertSame('Juan', $fresh->name);
        $this->assertSame('Perez', $fresh->last_name);
        $this->assertSame('11111111', $fresh->dni);
        $this->assertSame('M-1', $fresh->masonic_id);
        // El campo no sensible se guarda.
        $this->assertSame('1122334455', $fresh->phone);
    }

    public function test_superadmin_can_change_sensitive_identity_fields_directly(): void
    {
        $admin = User::factory()->create([
            'role' => 'superadmin', 'status' => 'active',
            'name' => 'Admin', 'dni' => '11111111',
        ]);

        $this->actingAs($admin, 'sanctum')
            ->patchJson('/api/profile', ['name' => 'Nuevo Nombre', 'dni' => '22222222'])
            ->assertOk();

        $fresh = $admin->fresh();
        $this->assertSame('Nuevo Nombre', $fresh->name);
        $this->assertSame('22222222', $fresh->dni);
    }
}
