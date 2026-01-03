<?php

declare(strict_types=1);

namespace XetaSuite\Actions\Items;

use Illuminate\Support\Facades\DB;
use XetaSuite\Actions\ItemPrices\CreateItemPrice;
use XetaSuite\Models\Company;
use XetaSuite\Models\Item;
use XetaSuite\Models\User;

class CreateItem
{
    /**
     * Create a new item.
     *
     * @param  User  $user  The user creating the item.
     * @param  array  $data  The data for creating the item.
     */
    public function handle(User $user, array $data): Item
    {
        return DB::transaction(function () use ($user, $data) {
            $company = isset($data['company_id']) ? Company::find($data['company_id']) : null;

            $item = Item::create([
                'site_id' => $user->current_site_id,
                'created_by_id' => $user->id,
                'name' => $data['name'],
                'reference' => $data['reference'] ?? null,
                'description' => $data['description'] ?? null,
                'company_id' => $company?->id,
                'company_reference' => $data['company_reference'] ?? null,
                'current_price' => $data['current_price'] ?? 0,
                'number_warning_enabled' => $data['number_warning_enabled'] ?? false,
                'number_warning_minimum' => $data['number_warning_minimum'] ?? 0,
                'number_critical_enabled' => $data['number_critical_enabled'] ?? false,
                'number_critical_minimum' => $data['number_critical_minimum'] ?? 0,
            ]);

            // Attach materials if provided
            if (! empty($data['material_ids'])) {
                $item->materials()->attach($data['material_ids']);
            }

            // Attach recipients for critical alerts if provided
            if (! empty($data['recipient_ids'])) {
                $item->recipients()->attach($data['recipient_ids']);
            }

            // Create initial price history if current_price is set
            if (($data['current_price'] ?? 0) > 0) {
                $data['notes'] = __('items.initial_price');
                $data['company'] = $company;

                app(CreateItemPrice::class)->handle($item, $user, $data);
            }

            return $item;
        });
    }
}
