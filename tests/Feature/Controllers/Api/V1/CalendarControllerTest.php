<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use XetaSuite\Models\CalendarEvent;
use XetaSuite\Models\EventCategory;
use XetaSuite\Models\Incident;
use XetaSuite\Models\Maintenance;
use XetaSuite\Models\Material;
use XetaSuite\Models\Site;
use XetaSuite\Models\User;
use XetaSuite\Models\Zone;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->headquarters = Site::factory()->create(['is_headquarters' => true]);
    $this->site = Site::factory()->create(['is_headquarters' => false]);
    $this->zone = Zone::factory()->forSite($this->site)->create();
    $this->material = Material::factory()->forZone($this->zone)->create();
    $this->user = User::factory()->create(['current_site_id' => $this->site->id]);
});

describe('index', function () {
    it('returns calendar events for the current site', function () {
        // Create events for current site
        $category = EventCategory::factory()->forSite($this->site)->create();
        $event = CalendarEvent::factory()
            ->forSite($this->site)
            ->withCategory($category)
            ->create([
                'start_at' => now()->subDay(),
                'end_at' => now()->addDay(),
            ]);

        // Create event for other site (should not appear)
        $otherSite = Site::factory()->create();
        CalendarEvent::factory()
            ->forSite($otherSite)
            ->create([
                'start_at' => now()->subDay(),
                'end_at' => now()->addDay(),
            ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/calendar?start=' . now()->subWeek()->toDateString() . '&end=' . now()->addWeek()->toDateString());

        $response->assertOk();
        $response->assertJsonCount(1, 'events');
        $response->assertJsonPath('events.0.id', 'event_' . $event->id);
        $response->assertJsonPath('events.0.type', 'event');
    });

    it('includes maintenances when show_maintenances is true', function () {
        $maintenance = Maintenance::factory()
            ->forSite($this->site)
            ->forMaterial($this->material)
            ->create([
                'started_at' => now()->subDay(),
                'resolved_at' => now()->addDay(),
            ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/calendar?start=' . now()->subWeek()->toDateString() . '&end=' . now()->addWeek()->toDateString() . '&show_maintenances=true');

        $response->assertOk();
        $events = collect($response->json('events'));
        $maintenanceEvent = $events->first(fn ($e) => $e['type'] === 'maintenance');

        expect($maintenanceEvent)->not->toBeNull();
        expect($maintenanceEvent['id'])->toBe('maintenance_' . $maintenance->id);
        expect($maintenanceEvent['extendedProps']['type'])->toBe('maintenance');
    });

    it('excludes maintenances when show_maintenances is false', function () {
        Maintenance::factory()
            ->forSite($this->site)
            ->forMaterial($this->material)
            ->create([
                'started_at' => now()->subDay(),
                'resolved_at' => now()->addDay(),
            ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/calendar?start=' . now()->subWeek()->toDateString() . '&end=' . now()->addWeek()->toDateString() . '&show_maintenances=false');

        $response->assertOk();
        $events = collect($response->json('events'));
        $maintenanceEvent = $events->first(fn ($e) => $e['type'] === 'maintenance');

        expect($maintenanceEvent)->toBeNull();
    });

    it('includes incidents when show_incidents is true', function () {
        $incident = Incident::factory()
            ->forSite($this->site)
            ->forMaterial($this->material)
            ->create([
                'started_at' => now()->subDay(),
                'resolved_at' => now()->addDay(),
            ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/calendar?start=' . now()->subWeek()->toDateString() . '&end=' . now()->addWeek()->toDateString() . '&show_incidents=true');

        $response->assertOk();
        $events = collect($response->json('events'));
        $incidentEvent = $events->first(fn ($e) => $e['type'] === 'incident');

        expect($incidentEvent)->not->toBeNull();
        expect($incidentEvent['id'])->toBe('incident_' . $incident->id);
        expect($incidentEvent['extendedProps']['type'])->toBe('incident');
    });

    it('excludes incidents when show_incidents is false', function () {
        Incident::factory()
            ->forSite($this->site)
            ->forMaterial($this->material)
            ->create([
                'started_at' => now()->subDay(),
                'resolved_at' => now()->addDay(),
            ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/calendar?start=' . now()->subWeek()->toDateString() . '&end=' . now()->addWeek()->toDateString() . '&show_incidents=false');

        $response->assertOk();
        $events = collect($response->json('events'));
        $incidentEvent = $events->first(fn ($e) => $e['type'] === 'incident');

        expect($incidentEvent)->toBeNull();
    });

    it('returns proper event structure', function () {
        $category = EventCategory::factory()->forSite($this->site)->create(['color' => '#ff0000']);
        CalendarEvent::factory()
            ->forSite($this->site)
            ->withCategory($category)
            ->create([
                'title' => 'Test Event',
                'description' => 'Test Description',
                'start_at' => now(),
                'end_at' => now()->addHour(),
                'all_day' => false,
            ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/calendar?start=' . now()->subWeek()->toDateString() . '&end=' . now()->addWeek()->toDateString());

        $response->assertOk();
        $response->assertJsonStructure([
            'events' => [
                '*' => [
                    'id',
                    'type',
                    'resourceId',
                    'title',
                    'start',
                    'end',
                    'allDay',
                    'color',
                    'extendedProps' => [
                        'type',
                        'description',
                        'category',
                        'categoryId',
                    ],
                ],
            ],
        ]);
    });
});

describe('today', function () {
    it('returns only events happening today', function () {
        $category = EventCategory::factory()->forSite($this->site)->create();

        // Today's event
        $todayEvent = CalendarEvent::factory()
            ->forSite($this->site)
            ->withCategory($category)
            ->create([
                'title' => 'Today Event',
                'start_at' => now()->startOfDay()->addHour(),
                'end_at' => now()->startOfDay()->addHours(2),
            ]);

        // Tomorrow's event (should not appear)
        CalendarEvent::factory()
            ->forSite($this->site)
            ->withCategory($category)
            ->create([
                'title' => 'Tomorrow Event',
                'start_at' => now()->addDay()->startOfDay(),
                'end_at' => now()->addDay()->startOfDay()->addHour(),
            ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/calendar/today');

        $response->assertOk();
        $response->assertJsonCount(1);
        $response->assertJsonPath('0.id', 'event_' . $todayEvent->id);
    });

    it('returns events spanning today', function () {
        $category = EventCategory::factory()->forSite($this->site)->create();

        // Event spanning multiple days including today
        $spanningEvent = CalendarEvent::factory()
            ->forSite($this->site)
            ->withCategory($category)
            ->create([
                'title' => 'Spanning Event',
                'start_at' => now()->subDay(),
                'end_at' => now()->addDay(),
            ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/calendar/today');

        $response->assertOk();
        $response->assertJsonCount(1);
        $response->assertJsonPath('0.id', 'event_' . $spanningEvent->id);
    });

    it('includes today maintenances when show_maintenances is true', function () {
        $maintenance = Maintenance::factory()
            ->forSite($this->site)
            ->forMaterial($this->material)
            ->create([
                'started_at' => now()->startOfDay(),
                'resolved_at' => now()->endOfDay(),
            ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/calendar/today?show_maintenances=true');

        $response->assertOk();
        $events = collect($response->json());
        $maintenanceEvent = $events->first(fn ($e) => $e['type'] === 'maintenance');

        expect($maintenanceEvent)->not->toBeNull();
        expect($maintenanceEvent['id'])->toBe('maintenance_' . $maintenance->id);
    });

    it('excludes maintenances when show_maintenances is false', function () {
        Maintenance::factory()
            ->forSite($this->site)
            ->forMaterial($this->material)
            ->create([
                'started_at' => now()->startOfDay(),
                'resolved_at' => now()->endOfDay(),
            ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/calendar/today?show_maintenances=false');

        $response->assertOk();
        $events = collect($response->json());
        $maintenanceEvent = $events->first(fn ($e) => $e['type'] === 'maintenance');

        expect($maintenanceEvent)->toBeNull();
    });

    it('includes today incidents when show_incidents is true', function () {
        $incident = Incident::factory()
            ->forSite($this->site)
            ->forMaterial($this->material)
            ->create([
                'started_at' => now()->startOfDay(),
                'resolved_at' => now()->endOfDay(),
            ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/calendar/today?show_incidents=true');

        $response->assertOk();
        $events = collect($response->json());
        $incidentEvent = $events->first(fn ($e) => $e['type'] === 'incident');

        expect($incidentEvent)->not->toBeNull();
        expect($incidentEvent['id'])->toBe('incident_' . $incident->id);
    });

    it('excludes incidents when show_incidents is false', function () {
        Incident::factory()
            ->forSite($this->site)
            ->forMaterial($this->material)
            ->create([
                'started_at' => now()->startOfDay(),
                'resolved_at' => now()->endOfDay(),
            ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/calendar/today?show_incidents=false');

        $response->assertOk();
        $events = collect($response->json());
        $incidentEvent = $events->first(fn ($e) => $e['type'] === 'incident');

        expect($incidentEvent)->toBeNull();
    });

    it('returns empty array when no events today', function () {
        // Create event for tomorrow only
        $category = EventCategory::factory()->forSite($this->site)->create();
        CalendarEvent::factory()
            ->forSite($this->site)
            ->withCategory($category)
            ->create([
                'start_at' => now()->addDay(),
                'end_at' => now()->addDays(2),
            ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/calendar/today');

        $response->assertOk();
        $response->assertJson([]);
    });

    it('only returns events for current site', function () {
        $category = EventCategory::factory()->forSite($this->site)->create();

        // Event for current site
        $myEvent = CalendarEvent::factory()
            ->forSite($this->site)
            ->withCategory($category)
            ->create([
                'start_at' => now()->startOfDay()->addHour(),
                'end_at' => now()->startOfDay()->addHours(2),
            ]);

        // Event for other site
        $otherSite = Site::factory()->create();
        $otherCategory = EventCategory::factory()->forSite($otherSite)->create();
        CalendarEvent::factory()
            ->forSite($otherSite)
            ->withCategory($otherCategory)
            ->create([
                'start_at' => now()->startOfDay()->addHour(),
                'end_at' => now()->startOfDay()->addHours(2),
            ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/calendar/today');

        $response->assertOk();
        $response->assertJsonCount(1);
        $response->assertJsonPath('0.id', 'event_' . $myEvent->id);
    });
});
