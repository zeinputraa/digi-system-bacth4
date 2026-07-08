<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->role = Role::where('name', 'admin')->first();
    $this->user = User::factory()->create(['role_id' => $this->role->id]);

    $this->category = Category::factory()->create();
    $this->product = Product::factory()->create([
        'category_id' => $this->category->id,
        'kode_produk' => 'API-PROD',
    ]);
});

it('blocks api access without sanctum tokens', function () {
    $response = $this->getJson('/api/v1/barang');
    $response->assertStatus(401); // Unauthorized
});

it('allows api access with valid sanctum tokens and read scope', function () {
    Sanctum::actingAs(
        $this->user,
        ['read']
    );

    $response = $this->getJson('/api/v1/barang');

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'status',
        'data' => [
            '*' => [
                'kode_produk',
                'nama_barang',
                'kategori',
                'total_unit',
                'unit_tersedia',
            ],
        ],
    ]);
});

it('blocks api access if token has incorrect scope abilities', function () {
    Sanctum::actingAs(
        $this->user,
        ['write-only-invalid']
    );

    $response = $this->getJson('/api/v1/barang');
    $response->assertStatus(403); // Forbidden
});
