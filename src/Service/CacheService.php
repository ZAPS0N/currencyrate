<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://devdocs.prestashop.com/ for more information.
 *
 * @author    Bartosz Żabicki
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

declare(strict_types=1);

namespace CurrencyRate\Service;

use Cache;

/**
 * Cache service for storing and retrieving data with TTL support
 *
 * Since PrestaShop's Cache::store() doesn't support TTL natively,
 * this service implements TTL by wrapping cached values with expiration timestamps.
 * Expired entries are automatically detected and removed on retrieval.
 */
class CacheService
{
    private const DEFAULT_TTL = 86400; // 24 hours
    private const CACHE_PREFIX = 'currencyrate_';

    /**
     * @var int Time to live in seconds
     */
    private $ttl;

    /**
     * @var string Cache key prefix
     */
    private $prefix;

    /**
     * @param int $ttl Time to live in seconds (default: 86400 = 24 hours)
     * @param string $prefix Cache key prefix for namespacing
     */
    public function __construct(int $ttl = self::DEFAULT_TTL, string $prefix = self::CACHE_PREFIX)
    {
        $this->ttl = $ttl;
        $this->prefix = $prefix;
    }

    /**
     * Get value from cache
     *
     * Retrieves cached value and checks if it has expired based on TTL.
     * If expired, the entry is automatically removed and false is returned.
     *
     * @param string $key Cache key
     *
     * @return mixed|false Returns cached value or false if not found/expired
     */
    public function get(string $key)
    {
        $cacheKey = $this->buildCacheKey($key);

        if (!Cache::isStored($cacheKey)) {
            return false;
        }

        $cached = Cache::retrieve($cacheKey);

        // If cache was stored without TTL wrapper (legacy), return as-is
        if (!is_array($cached) || !isset($cached['expires_at']) || !isset($cached['data'])) {
            return $cached;
        }

        // Check if cache has expired
        if (time() > $cached['expires_at']) {
            // Cache expired, remove it
            $this->delete($key);

            return false;
        }

        // Cache is valid, return the data
        return $cached['data'];
    }

    /**
     * Set value in cache with TTL
     *
     * Wraps the value with expiration timestamp to enable TTL functionality.
     * The actual expiration check happens in get() method.
     *
     * @param string $key Cache key
     * @param mixed $value Value to cache
     * @param int|null $ttl Optional custom TTL in seconds for this entry (overrides default)
     *
     * @return bool Always returns true
     */
    public function set(string $key, $value, ?int $ttl = null): bool
    {
        $cacheKey = $this->buildCacheKey($key);
        $cacheTtl = $ttl ?? $this->ttl;

        // Wrap value with expiration timestamp
        $wrappedValue = [
            'data' => $value,
            'expires_at' => time() + $cacheTtl,
        ];

        Cache::store($cacheKey, $wrappedValue);

        return true;
    }

    /**
     * Delete value from cache
     *
     * @param string $key Cache key
     *
     * @return bool Always returns true
     */
    public function delete(string $key): bool
    {
        $cacheKey = $this->buildCacheKey($key);

        Cache::clean($cacheKey);

        return true;
    }

    /**
     * Clear all module cache entries
     *
     * @return bool Always returns true
     */
    public function clearAll(): bool
    {
        Cache::clean($this->prefix . '*');

        return true;
    }

    /**
     * Check if cache key exists
     *
     * @param string $key Cache key
     *
     * @return bool True if key exists, false otherwise
     */
    public function has(string $key): bool
    {
        $cacheKey = $this->buildCacheKey($key);

        return Cache::isStored($cacheKey);
    }

    /**
     * Set TTL for future cache operations
     *
     * @param int $ttl Time to live in seconds
     *
     * @return void
     */
    public function setTtl(int $ttl): void
    {
        $this->ttl = $ttl;
    }

    /**
     * Get current TTL
     *
     * @return int Time to live in seconds
     */
    public function getTtl(): int
    {
        return $this->ttl;
    }

    /**
     * Set cache key prefix
     *
     * @param string $prefix Cache key prefix
     *
     * @return void
     */
    public function setPrefix(string $prefix): void
    {
        $this->prefix = $prefix;
    }

    /**
     * Get cache key prefix
     *
     * @return string Cache key prefix
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * Build full cache key with prefix
     *
     * @param string $key Base cache key
     *
     * @return string Full cache key with prefix
     */
    private function buildCacheKey(string $key): string
    {
        return $this->prefix . $key;
    }

    /**
     * Delete multiple cache entries by pattern
     *
     * @param string $pattern Pattern to match (without prefix)
     *
     * @return bool Always returns true
     */
    public function deleteByPattern(string $pattern): bool
    {
        Cache::clean($this->prefix . $pattern);

        return true;
    }

    /**
     * Check if caching is enabled
     *
     * @return bool True if caching is enabled
     */
    public function isEnabled(): bool
    {
        return defined('_PS_CACHE_ENABLED_') && _PS_CACHE_ENABLED_;
    }

    /**
     * Get remaining TTL for a cached entry
     *
     * Returns the number of seconds until the cache entry expires.
     * Returns 0 if entry doesn't exist or has already expired.
     *
     * @param string $key Cache key
     *
     * @return int Remaining seconds until expiration, or 0 if not found/expired
     */
    public function getRemainingTtl(string $key): int
    {
        $cacheKey = $this->buildCacheKey($key);

        if (!Cache::isStored($cacheKey)) {
            return 0;
        }

        $cached = Cache::retrieve($cacheKey);

        // If cache was stored without TTL wrapper, we can't determine TTL
        if (!is_array($cached) || !isset($cached['expires_at'])) {
            return 0;
        }

        $remaining = $cached['expires_at'] - time();

        return max(0, $remaining);
    }

    /**
     * Check if cache entry exists and is not expired
     *
     * This is more accurate than has() as it checks expiration.
     *
     * @param string $key Cache key
     *
     * @return bool True if entry exists and is valid
     */
    public function isValid(string $key): bool
    {
        return $this->get($key) !== false;
    }

    /**
     * Get cache statistics for debugging
     *
     * Returns information about a specific cache entry including
     * expiration time and remaining TTL.
     *
     * @param string $key Cache key
     *
     * @return array<string, mixed>|null Cache info or null if not found
     */
    public function getInfo(string $key): ?array
    {
        $cacheKey = $this->buildCacheKey($key);

        if (!Cache::isStored($cacheKey)) {
            return null;
        }

        $cached = Cache::retrieve($cacheKey);

        if (!is_array($cached) || !isset($cached['expires_at']) || !isset($cached['data'])) {
            return [
                'exists' => true,
                'has_ttl' => false,
                'expires_at' => null,
                'remaining_ttl' => null,
                'is_expired' => false,
            ];
        }

        $now = time();
        $isExpired = $now > $cached['expires_at'];

        return [
            'exists' => true,
            'has_ttl' => true,
            'expires_at' => $cached['expires_at'],
            'expires_at_formatted' => date('Y-m-d H:i:s', $cached['expires_at']),
            'remaining_ttl' => max(0, $cached['expires_at'] - $now),
            'is_expired' => $isExpired,
        ];
    }
}
