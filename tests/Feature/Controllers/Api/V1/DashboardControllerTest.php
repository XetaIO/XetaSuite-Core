<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use XetaSuite\Enums\Cleanings\CleaningType;
use XetaSuite\Enums\Incidents\IncidentSeverity;
use XetaSuite\Enums\Incidents\IncidentStatus;
use XetaSuite\Enums\Maintenances\MaintenanceStatus;
use XetaSuite\Models\Cleaning;
use XetaSuite\Models\Incident;
use XetaSuite\Models\Item;
use XetaSuite\Models\Maintenance;
use XetaSuite\Models\Site;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->headquarters = Site::factory()->headquarters()->create();
    $this->regularSite = Site::factory()->create(['name' => 'Site A']);
    $this->otherSite = Site::factory()->create(['name' => 'Site B']);

    // Create role for tests
    $this->role = Role::create(['name' => 'dashboard-viewer', 'guard_name' => 'web']);
});

describe('stats endpoint', function (): void {
    it('returns dashboard stats for HQ user with aggregated data from all sites', function (): void {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        // Create data on multiple sites
        Maintenance::factory()->count(3)->create([
            'site_id' => $this->regularSite->id,
            'started_at' => now(),
            'status' => MaintenanceStatus::COMPLETED,
        ]);

        Maintenance::factory()->count(2)->create([
            'site_id' => $this->otherSite->id,
            'started_at' => now(),
            'status' => MaintenanceStatus::PLANNED,
        ]);

        Incident::factory()->count(4)->create([
            'site_id' => $this->regularSite->id,
            'status' => IncidentStatus::OPEN,
            'severity' => IncidentSeverity::HIGH,
        ]);

        Incident::factory()->count(2)->create([
            'site_id' => $this->otherSite->id,
            'status' => IncidentStatus::IN_PROGRESS,
            'severity' => IncidentSeverity::LOW,
        ]);

        Cleaning::factory()->count(2)->create([
            'site_id' => $this->regularSite->id,
            'type' => CleaningType::CASUAL,
        ]);

        Cleaning::factory()->count(3)->create([
            'site_id' => $this->otherSite->id,
            'type' => CleaningType::CASUAL,
        ]);

        actingAs($user)
            ->getJson('/api/v1/dashboard/stats')
            ->assertOk()
            ->assertJsonStructure([
                'stats' => [
                    'maintenances_this_month',
                    'maintenances_trend',
                    'open_incidents',
                    'incidents_trend',
                    'items_in_stock',
                    'cleanings_this_month',
                    'cleanings_trend',
                ],
                'incidents_summary' => [
                    'total',
                    'open',
                    'in_progress',
                    'resolved',
                    'by_severity' => ['critical', 'high', 'medium', 'low'],
                ],
                'low_stock_items',
                'upcoming_maintenances',
                'recent_activities',
                'is_headquarters',
            ])
            // HQ sees all maintenances (3 + 2 = 5)
            ->assertJsonPath('stats.maintenances_this_month', 5)
            // HQ sees all open incidents (4 OPEN + 2 IN_PROGRESS = 6)
            ->assertJsonPath('stats.open_incidents', 6)
            // HQ sees all cleanings (2 + 3 = 5)
            ->assertJsonPath('stats.cleanings_this_month', 5)
            ->assertJsonPath('is_headquarters', true)
            // Incident summary
            ->assertJsonPath('incidents_summary.total', 6)
            ->assertJsonPath('incidents_summary.open', 4)
            ->assertJsonPath('incidents_summary.in_progress', 2);
    });

    it('returns dashboard stats for regular site user with site-specific data only', function (): void {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        // Create data on current site
        Maintenance::factory()->count(3)->create([
            'site_id' => $this->regularSite->id,
            'started_at' => now(),
            'status' => MaintenanceStatus::COMPLETED,
        ]);

        // Create data on other site (should NOT be included)
        Maintenance::factory()->count(5)->create([
            'site_id' => $this->otherSite->id,
            'started_at' => now(),
        ]);

        Incident::factory()->count(2)->create([
            'site_id' => $this->regularSite->id,
            'status' => IncidentStatus::OPEN,
        ]);

        Incident::factory()->count(10)->create([
            'site_id' => $this->otherSite->id,
            'status' => IncidentStatus::OPEN,
        ]);

        Cleaning::factory()->count(2)->create([
            'site_id' => $this->regularSite->id,
            'type' => CleaningType::CASUAL,
        ]);

        Cleaning::factory()->count(3)->create([
            'site_id' => $this->otherSite->id,
            'type' => CleaningType::CASUAL,
        ]);

        actingAs($user)
            ->getJson('/api/v1/dashboard/stats')
            ->assertOk()
            // Regular site only sees its own maintenances
            ->assertJsonPath('stats.maintenances_this_month', 3)
            // Regular site only sees its own incidents
            ->assertJsonPath('stats.open_incidents', 2)
            // Regular site only sees its own cleanings
            ->assertJsonPath('stats.cleanings_this_month', 2)
            ->assertJsonPath('is_headquarters', false)
            // Incident summary only for current site
            ->assertJsonPath('incidents_summary.total', 2);
    });

    it('returns low stock items for current site', function (): void {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        // Item with low stock (current_stock = item_entry_total - item_exit_total = 10)
        Item::factory()->create([
            'site_id' => $this->regularSite->id,
            'name' => 'Low Stock Item',
            'reference' => 'LSI-001',
            'number_warning_enabled' => true,
            'number_warning_minimum' => 50,
            'item_entry_total' => 10,
            'item_exit_total' => 0,
        ]);

        // Item with sufficient stock (should not appear) - current_stock = 100
        Item::factory()->create([
            'site_id' => $this->regularSite->id,
            'name' => 'Good Stock Item',
            'number_warning_enabled' => true,
            'number_warning_minimum' => 20,
            'item_entry_total' => 100,
            'item_exit_total' => 0,
        ]);

        // Item without warning but with stock >= 10 (should NOT appear)
        Item::factory()->create([
            'site_id' => $this->regularSite->id,
            'name' => 'Normal Stock Item',
            'number_warning_enabled' => false,
            'item_entry_total' => 25,
            'item_exit_total' => 0,
        ]);

        actingAs($user)
            ->getJson('/api/v1/dashboard/stats')
            ->assertOk()
            // Only 1 item should appear: Low Stock Item (below warning threshold)
            ->assertJsonCount(1, 'low_stock_items')
            ->assertJsonPath('low_stock_items.0.name', 'Low Stock Item')
            ->assertJsonPath('low_stock_items.0.reference', 'LSI-001')
            ->assertJsonPath('low_stock_items.0.current_stock', 10)
            ->assertJsonPath('low_stock_items.0.min_stock', 50);
    });

    it('returns upcoming maintenances for current site', function (): void {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        // Upcoming maintenance (should appear)
        Maintenance::factory()->create([
            'site_id' => $this->regularSite->id,
            'description' => 'Future Maintenance',
            'status' => MaintenanceStatus::PLANNED,
            'started_at' => now()->addDays(3),
        ]);

        // Past maintenance (should not appear)
        Maintenance::factory()->create([
            'site_id' => $this->regularSite->id,
            'description' => 'Past Maintenance',
            'status' => MaintenanceStatus::COMPLETED,
            'started_at' => now()->subDays(3),
        ]);

        // In progress maintenance (should not appear)
        Maintenance::factory()->create([
            'site_id' => $this->regularSite->id,
            'description' => 'Current Maintenance',
            'status' => MaintenanceStatus::IN_PROGRESS,
            'started_at' => now(),
        ]);

        actingAs($user)
            ->getJson('/api/v1/dashboard/stats')
            ->assertOk()
            ->assertJsonCount(1, 'upcoming_maintenances')
            ->assertJsonPath('upcoming_maintenances.0.title', 'Future Maintenance');
    });

    it('requires authentication', function (): void {
        $this->getJson('/api/v1/dashboard/stats')
            ->assertUnauthorized();
    });
});

describe('charts endpoint', function (): void {
    it('returns chart data for HQ user with aggregated data', function (): void {
        $user = createUserOnHeadquarters($this->headquarters, $this->role);

        actingAs($user)
            ->getJson('/api/v1/dashboard/charts')
            ->assertOk()
            ->assertJsonStructure([
                'maintenances_evolution',
                'incidents_evolution',
            ])
            ->assertJsonCount(6, 'maintenances_evolution')
            ->assertJsonCount(6, 'incidents_evolution');
    });

    it('returns chart data for regular site user', function (): void {
        $user = createUserOnRegularSite($this->regularSite, $this->role);

        actingAs($user)
            ->getJson('/api/v1/dashboard/charts')
            ->assertOk()
            ->assertJsonStructure([
                'maintenances_evolution',
                'incidents_evolution',
            ]);
    });

    it('requires authentication', function (): void {
        $this->getJson('/api/v1/dashboard/charts')
            ->assertUnauthorized();
    });
});
