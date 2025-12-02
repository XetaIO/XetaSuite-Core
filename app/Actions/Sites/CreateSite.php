<?php

declare(strict_types=1);

namespace XetaSuite\Actions\Sites;

use XetaSuite\Models\Site;
use XetaSuite\Services\SiteService;

class CreateSite
{
    public function __construct(
        private readonly SiteService $siteService
    ) {
    }

    /**
     * Create a new site.
     *
     * @param  array{name: string, is_headquarters?: bool, email?: string|null, office_phone?: string|null, cell_phone?: string|null, address_line_1?: string|null, address_line_2?: string|null, postal_code?: string|null, city?: string|null, country?: string|null}  $data
     * @return array{success: bool, site?: Site, message?: string}
     */
    public function handle(array $data): array
    {
        // Check if trying to create a headquarters when one already exists
        if (($data['is_headquarters'] ?? false) && $this->siteService->headquartersExists()) {
            return [
                'success' => false,
                'message' => __('sites.headquarters_already_exists'),
            ];
        }

        $site = Site::create([
            'name' => $data['name'],
            'is_headquarters' => $data['is_headquarters'] ?? false,
            'email' => $data['email'] ?? null,
            'office_phone' => $data['office_phone'] ?? null,
            'cell_phone' => $data['cell_phone'] ?? null,
            'address_line_1' => $data['address_line_1'] ?? null,
            'address_line_2' => $data['address_line_2'] ?? null,
            'postal_code' => $data['postal_code'] ?? null,
            'city' => $data['city'] ?? null,
            'country' => $data['country'] ?? null,
        ]);

        return [
            'success' => true,
            'site' => $site,
        ];
    }
}
