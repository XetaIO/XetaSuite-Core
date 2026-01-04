<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Hash;
use XetaSuite\Models\User;

beforeEach(function (): void {
    $this->user = User::factory()->create([
        'password' => Hash::make('current-password'),
    ]);

    $this->actingAs($this->user);
});

describe('PUT /api/v1/user/password', function (): void {
    it('updates the password successfully', function (): void {
        $response = $this->putJson('/api/v1/user/password', [
            'current_password' => 'current-password',
            'password' => 'new-secure-password',
            'password_confirmation' => 'new-secure-password',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['message']);

        // Verify password was updated
        $this->user->refresh();
        expect(Hash::check('new-secure-password', $this->user->password))->toBeTrue();
    });

    it('fails with incorrect current password', function (): void {
        $response = $this->putJson('/api/v1/user/password', [
            'current_password' => 'wrong-password',
            'password' => 'new-secure-password',
            'password_confirmation' => 'new-secure-password',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['current_password']);
    });

    it('fails when password confirmation does not match', function (): void {
        $response = $this->putJson('/api/v1/user/password', [
            'current_password' => 'current-password',
            'password' => 'new-secure-password',
            'password_confirmation' => 'different-password',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    });

    it('fails when current password is missing', function (): void {
        $response = $this->putJson('/api/v1/user/password', [
            'password' => 'new-secure-password',
            'password_confirmation' => 'new-secure-password',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['current_password']);
    });

    it('fails when new password is missing', function (): void {
        $response = $this->putJson('/api/v1/user/password', [
            'current_password' => 'current-password',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    });

    it('requires authentication', function (): void {
        auth()->logout();

        $response = $this->putJson('/api/v1/user/password', [
            'current_password' => 'current-password',
            'password' => 'new-secure-password',
            'password_confirmation' => 'new-secure-password',
        ]);

        $response->assertUnauthorized();
    });
});
