<?php

declare(strict_types=1);

/**
 * Tests for AppConfigController - Public application configuration endpoint.
 * This endpoint does NOT require authentication.
 */

describe('AppConfigController', function (): void {
    it('returns app configuration without authentication', function (): void {
        $response = $this->getJson('/api/v1/app/config');

        $response->assertSuccessful()
            ->assertJsonStructure([
                'demo_mode',
                'app_name',
            ]);
    });

    it('returns demo_mode as boolean', function (): void {
        $response = $this->getJson('/api/v1/app/config');

        $response->assertSuccessful();

        $data = $response->json();
        expect($data['demo_mode'])->toBeBool();
    });

    it('returns app_name from configuration', function (): void {
        $response = $this->getJson('/api/v1/app/config');

        $response->assertSuccessful()
            ->assertJsonPath('app_name', config('app.name'));
    });
});
