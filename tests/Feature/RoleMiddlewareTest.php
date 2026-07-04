<?php

use App\Models\Role;
use App\Models\User;
use Database\Seeders\UserSeeder;

test('guests are redirected to login', function () {
    $this->get('/dashboard')->assertRedirect('/login');
    $this->get('/admin')->assertRedirect('/login');
    $this->get('/operasional')->assertRedirect('/login');
});

test('unverified users are redirected to verify email screen', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)->get('/dashboard')->assertRedirect('/verify-email');
    $this->actingAs($user)->get('/admin')->assertRedirect('/verify-email');
    $this->actingAs($user)->get('/operasional')->assertRedirect('/verify-email');
});

test('karyawan can access dashboard and riwayat but forbidden from admin and operasional', function () {
    $roleKaryawan = Role::where('name', 'karyawan')->first();
    $user = User::factory()->create(['role_id' => $roleKaryawan->id]);

    $this->actingAs($user)->get('/dashboard')->assertStatus(200);
    $this->actingAs($user)->get('/riwayat-peminjaman')->assertStatus(200);
    $this->actingAs($user)->get('/admin')->assertStatus(403);
    $this->actingAs($user)->get('/operasional')->assertStatus(403);
});

test('staff can access operasional but forbidden from admin', function () {
    $roleStaff = Role::where('name', 'staff')->first();
    $user = User::factory()->create(['role_id' => $roleStaff->id]);

    $this->actingAs($user)->get('/dashboard')->assertStatus(200);
    $this->actingAs($user)->get('/operasional')->assertStatus(200);
    $this->actingAs($user)->get('/admin')->assertStatus(403);
});

test('admin can access everything', function () {
    $roleAdmin = Role::where('name', 'admin')->first();
    $user = User::factory()->create(['role_id' => $roleAdmin->id]);

    $this->actingAs($user)->get('/dashboard')->assertStatus(200);
    $this->actingAs($user)->get('/admin')->assertStatus(200);
    $this->actingAs($user)->get('/operasional')->assertStatus(200);
});

test('database seeder seeds roles and testing accounts', function () {
    // Roles are already seeded by beforeEach; only run UserSeeder here
    $this->seed(UserSeeder::class);

    // Verify roles exist
    $roles = Role::pluck('name')->toArray();
    expect($roles)->toContain('admin', 'staff', 'manager', 'karyawan');

    // Verify 4 testing users exist with correct role relations
    $users = User::with('role')->get();
    expect($users->count())->toBe(4);

    $admin = $users->firstWhere('email', 'admin@telkomsel.test');
    expect($admin)->not->toBeNull()
        ->and($admin->role->name)->toBe('admin')
        ->and($admin->email_verified_at)->not->toBeNull();

    $staff = $users->firstWhere('email', 'staff@telkomsel.test');
    expect($staff)->not->toBeNull()
        ->and($staff->role->name)->toBe('staff')
        ->and($staff->email_verified_at)->not->toBeNull();

    $manager = $users->firstWhere('email', 'manager@telkomsel.test');
    expect($manager)->not->toBeNull()
        ->and($manager->role->name)->toBe('manager')
        ->and($manager->email_verified_at)->not->toBeNull();

    $karyawan = $users->firstWhere('email', 'karyawan@telkomsel.test');
    expect($karyawan)->not->toBeNull()
        ->and($karyawan->role->name)->toBe('karyawan')
        ->and($karyawan->email_verified_at)->not->toBeNull();
});
