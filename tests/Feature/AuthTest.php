<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user can register with role', function () {
    $userData = [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'student'
    ];

    $response = $this->postJson('/api/auth/register', $userData);

    $response->assertStatus(201)
             ->assertJsonStructure([
                 'message',
                 'user' => ['id', 'name', 'email', 'role'],
                 'token'
             ]);

    expect($response->json('user.role'))->toBe('student');
});

test('admin can access admin routes', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin, 'sanctum')
                     ->getJson('/api/admin/dashboard');

    $response->assertStatus(200)
             ->assertJsonStructure(['message', 'data']);
});

test('student cannot access admin routes', function () {
    $student = User::factory()->student()->create();

    $response = $this->actingAs($student, 'sanctum')
                     ->getJson('/api/admin/dashboard');

    $response->assertStatus(403);
});

test('user role methods work correctly', function () {
    $admin = User::factory()->admin()->create();
    $student = User::factory()->student()->create();

    expect($admin->isAdmin())->toBeTrue();
    expect($admin->isStudent())->toBeFalse();
    expect($student->isStudent())->toBeTrue();
    expect($student->isAdmin())->toBeFalse();
    expect($admin->hasRole(['admin', 'teacher']))->toBeTrue();
    expect($student->hasRole(['admin', 'teacher']))->toBeFalse();
});

// new test for login behavior (any email/password allowed)

test('login accepts arbitrary email and uses provided role', function () {
    $response = $this->postJson('/api/auth/login', [
        'email' => 'random@example.com',
        'password' => 'whatever',
        'role' => 'teacher',
    ]);

    $response->assertStatus(200)
             ->assertJsonStructure([
                 'message',
                 'role',
                 'token',
             ])
             ->assertJson(['role' => 'teacher']);

    // ensure a user record is created/updated
    $user = User::where('email', 'random@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->role)->toBe('teacher');
});