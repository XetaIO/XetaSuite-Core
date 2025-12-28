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
            'key' => $this->faker->unique()->slug,
            'value' => $this->faker->text,
            'model_type' => null,
            'model_id' => null,
            'text' => $this->faker->sentence,
            'label' => $this->faker->word,
            'label_info' => $this->faker->sentence,
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
