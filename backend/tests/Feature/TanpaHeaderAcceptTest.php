<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Permintaan ke /api/... tanpa header "Accept: application/json" (misalnya dari curl,
 * atau yang headernya hilang di tengah jalan) tetap harus dibalas JSON, bukan dialihkan
 * ke halaman login yang tidak ada.
 */
class TanpaHeaderAcceptTest extends TestCase
{
    use RefreshDatabase;

    public function test_belum_login_dibalas_json_401(): void
    {
        $this->get('/api/murid/status-scan')
            ->assertStatus(401)
            ->assertJson(['message' => 'Unauthenticated.']);
    }

    public function test_kesalahan_isian_dibalas_json_422(): void
    {
        $this->post('/api/login', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['identitas', 'password']);
    }

    public function test_halaman_web_biasa_tidak_terpengaruh(): void
    {
        $this->get('/')->assertOk();
    }
}