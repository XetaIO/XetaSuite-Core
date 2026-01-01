<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use XetaSuite\Models\Site;
use XetaSuite\Models\User;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->headquarters = Site::factory()->create(['is_headquarters' => true]);
    $this->user = User::factory()->withSite($this->headquarters)->create();
});

/**
 * Create a test notification for a user.
 */
function createNotification(User $user, array $data = [], bool $read = false): DatabaseNotification
{
    return DatabaseNotification::create([
        'id' => \Illuminate\Support\Str::uuid()->toString(),
        'type' => 'XetaSuite\\Notifications\\TestNotification',
        'notifiable_type' => User::class,
        'notifiable_id' => $user->id,
        'data' => array_merge([
            'title' => 'Test Notification',
            'message' => 'This is a test notification message.',
            'icon' => 'info',
            'link' => '/test-link',
        ], $data),
        'read_at' => $read ? now() : null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

// ============================================================================
// INDEX TESTS
// ============================================================================

describe('index', function () {
    it('returns paginated list of notifications for authenticated user', function () {
        createNotification($this->user);
        createNotification($this->user, ['title' => 'Second Notification']);
        createNotification($this->user, ['title' => 'Third Notification']);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/notifications');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'type', 'data', 'read_at', 'is_read', 'created_at', 'time_ago'],
                ],
                'links',
                'meta',
            ])
            ->assertJsonCount(3, 'data');
    });

    it('returns notifications ordered by unread first then by created_at desc', function () {
        // Create read notification first (older)
        $read = createNotification($this->user, ['title' => 'Read'], true);

        // Create unread notification after (newer)
        $unread = createNotification($this->user, ['title' => 'Unread'], false);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/notifications');

        $response->assertOk();
        $data = $response->json('data');
        // Unread (read_at = null) should come first with ASC ordering (nulls first in PostgreSQL)
        expect($data[0]['data']['title'])->toBe('Unread');
        expect($data[1]['data']['title'])->toBe('Read');
    });

    it('requires authentication', function () {
        $response = $this->getJson('/api/v1/notifications');

        $response->assertUnauthorized();
    });
});

// ============================================================================
// UNREAD TESTS
// ============================================================================

describe('unread', function () {
    it('returns only unread notifications', function () {
        createNotification($this->user, ['title' => 'Unread 1']);
        createNotification($this->user, ['title' => 'Read 1'], true);
        createNotification($this->user, ['title' => 'Unread 2']);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/notifications/unread');

        $response->assertOk()
            ->assertJsonCount(2, 'data');

        $titles = collect($response->json('data'))->pluck('title');
        expect($titles->contains('Read 1'))->toBeFalse();
    });

    it('limits to 20 notifications', function () {
        for ($i = 0; $i < 25; $i++) {
            createNotification($this->user, ['title' => "Notification $i"]);
        }

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/notifications/unread');

        $response->assertOk()
            ->assertJsonCount(20, 'data');
    });
});

// ============================================================================
// UNREAD COUNT TESTS
// ============================================================================

describe('unread count', function () {
    it('returns count of unread notifications', function () {
        createNotification($this->user);
        createNotification($this->user);
        createNotification($this->user, read: true);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/notifications/unread-count');

        $response->assertOk()
            ->assertJson(['count' => 2]);
    });

    it('returns zero when no unread notifications', function () {
        createNotification($this->user, read: true);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/notifications/unread-count');

        $response->assertOk()
            ->assertJson(['count' => 0]);
    });
});

// ============================================================================
// MARK AS READ TESTS
// ============================================================================

describe('mark as read', function () {
    it('marks a notification as read', function () {
        $notification = createNotification($this->user);

        expect($notification->read_at)->toBeNull();

        $response = $this->actingAs($this->user)
            ->patchJson("/api/v1/notifications/{$notification->id}/read");

        $response->assertOk()
            ->assertJsonStructure(['message']);

        $notification->refresh();
        expect($notification->read_at)->not->toBeNull();
    });

    it('returns 404 for non-existent notification', function () {
        $fakeUuid = \Illuminate\Support\Str::uuid()->toString();

        $response = $this->actingAs($this->user)
            ->patchJson("/api/v1/notifications/{$fakeUuid}/read");

        $response->assertNotFound();
    });

    it('cannot mark another user notification as read', function () {
        $otherUser = User::factory()->withSite($this->headquarters)->create();
        $notification = createNotification($otherUser);

        $response = $this->actingAs($this->user)
            ->patchJson("/api/v1/notifications/{$notification->id}/read");

        $response->assertNotFound();
    });
});

// ============================================================================
// MARK ALL AS READ TESTS
// ============================================================================

describe('mark all as read', function () {
    it('marks all notifications as read', function () {
        createNotification($this->user);
        createNotification($this->user);
        createNotification($this->user);

        expect($this->user->unreadNotifications()->count())->toBe(3);

        $response = $this->actingAs($this->user)
            ->patchJson('/api/v1/notifications/read-all');

        $response->assertOk();

        expect($this->user->fresh()->unreadNotifications()->count())->toBe(0);
    });
});

// ============================================================================
// DELETE TESTS
// ============================================================================

describe('destroy', function () {
    it('deletes a notification', function () {
        $notification = createNotification($this->user);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/notifications/{$notification->id}");

        $response->assertOk()
            ->assertJsonStructure(['message']);

        expect(DatabaseNotification::find($notification->id))->toBeNull();
    });

    it('returns 404 for non-existent notification', function () {
        $fakeUuid = \Illuminate\Support\Str::uuid()->toString();

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/notifications/{$fakeUuid}");

        $response->assertNotFound();
    });

    it('cannot delete another user notification', function () {
        $otherUser = User::factory()->withSite($this->headquarters)->create();
        $notification = createNotification($otherUser);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/notifications/{$notification->id}");

        $response->assertNotFound();
    });
});

// ============================================================================
// DELETE ALL TESTS
// ============================================================================

describe('destroy all', function () {
    it('deletes all notifications for the user', function () {
        createNotification($this->user);
        createNotification($this->user);
        createNotification($this->user);

        $otherUser = User::factory()->withSite($this->headquarters)->create();
        createNotification($otherUser);

        expect($this->user->notifications()->count())->toBe(3);

        $response = $this->actingAs($this->user)
            ->deleteJson('/api/v1/notifications');

        $response->assertOk();

        expect($this->user->fresh()->notifications()->count())->toBe(0);
        // Other user's notification should remain
        expect($otherUser->fresh()->notifications()->count())->toBe(1);
    });
});
