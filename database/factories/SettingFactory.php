<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use XetaSuite\Models\Setting;

class SettingFactory extends Factory
{
    protected $model = Setting::class;

    public function definition(): array
    {
        return [
            'key' => \fake()->unique()->slug,
            'value' => \fake()->text,
            'model_type' => null,
            'model_id' => null,
            'text' => \fake()->sentence,
            'label' => \fake()->word,
            'label_info' => \fake()->sentence,
            'updated_by_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Associate the setting with a model..
     *
     * @param string $type
     * @param int $id
     *
     * @return SettingFactory
     */
    public function withModel(string $type, int $id): self
    {
        return $this->state(fn () => [
            'model_type' => $type,
            'model_id' => $id,
        ]);
    }

    /**
     * Associate the last setting update with a user.
     *
     * @param int $userId
     *
     * @return SettingFactory
     */
    public function withLastUpdater(int $userId): self
    {
        return $this->state(fn () => [
            'updated_by_id' => $userId,
        ]);
    }
}
