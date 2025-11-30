<?php

declare(strict_types=1);

namespace XetaSuite\Settings;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use XetaSuite\Models\Setting;

class Settings
{
    protected ?array $context = [
        'model_type' => null,
        'model_id' => null,
    ];

    public function __construct(
        protected Cache $cache,
    ) {}

    /**
     * Generate the key used by the cache driver to store the value.
     */
    protected function getCacheKey(string $key): string
    {
        $cacheKey = $this->normalizeKey($key);

        // Add context to the cache key.
        $context = serialize($this->context);
        $cacheKey .= "::c::{$context}";

        return $cacheKey;
    }

    protected function normalizeKey(string $key): string
    {
        // We want to preserve period characters in the key, however everything else is fair game
        // to convert to a slug.
        return Str::of($key)
            ->replace('.', '-dot-')
            ->slug()
            ->replace('-dot-', '.')
            ->__toString();
    }

    /**
     * Get the value for the given key, siteId and context from the cache or from the database if no cache key.
     */
    public function get(string $key): mixed
    {
        $cacheKey = $this->getCacheKey(key: $key);

        $value = $this->cache->rememberForever($cacheKey, function () use ($key) {
            $query = Setting::query()
                ->where('key', $key)
                ->where('model_type', $this->context['model_type'])
                ->where('model_id', $this->context['model_id']);

            return serialize($query->value('value'));
        });

        return $value ? unserialize($value) : null;
    }

    /**
     * Remove the specified key.
     *
     * @param  string  $key  The key to flush.
     */
    public function remove(string $key): bool
    {
        $cacheKey = $this->getCacheKey(key: $key);

        return $this->cache->forget($cacheKey);
    }

    /**
     * Set the context to the setting.
     *
     *
     *
     * @param  Model|array|null  $context
     *                                     Pattern :
     *                                     [
     *                                     'type' => 'XetaSuite\Models\User',
     *                                     'id' => 1
     *                                     ]
     * @return $this
     */
    public function setContext(Model|array|null $context = null): self
    {
        if ($context instanceof Model) {
            $this->context['model_type'] = get_class($context);
            $this->context['model_id'] = $context->getKey();

            return $this;
        }
        $this->context = [
            'model_type' => $context['type'] ?? null,
            'model_id' => $context['id'] ?? null,
        ];

        return $this;
    }

    /**
     * Reset the context.
     *
     * @return $this
     */
    public function withoutContext(): self
    {
        $this->context = [
            'model_type' => null,
            'model_id' => null,
        ];

        return $this;
    }
}
