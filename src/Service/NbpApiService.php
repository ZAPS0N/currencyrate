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

use Exception;
use PrestaShopLogger;

/**
 * Service for interacting with NBP (National Bank of Poland) API
 */
class NbpApiService
{
    private const API_BASE_URL = 'https://api.nbp.pl/api/exchangerates';
    private const TIMEOUT = 10;
    private const MAX_RETRIES = 3;
    private const MAX_RATES_COUNT = 255;

    /**
     * @var CacheService
     */
    private $cacheService;

    /**
     * @var HttpClientInterface
     */
    private $httpClient;

    /**
     * @param CacheService $cacheService
     * @param HttpClientInterface|null $httpClient
     */
    public function __construct(CacheService $cacheService, ?HttpClientInterface $httpClient = null)
    {
        $this->cacheService = $cacheService;
        $this->httpClient = $httpClient ?? HttpClientFactory::create();
    }

    /**
     * Get exchange rates table
     *
     * @param string $tableType Table type: A, B, or C
     * @param int $count Number of last rates to fetch (max 255)
     *
     * @return array|false
     */
    public function getTable(string $tableType = 'A', int $count = 30)
    {
        $tableType = strtoupper($tableType);

        if (!$this->isValidTableType($tableType)) {
            PrestaShopLogger::addLog('Invalid table type: ' . $tableType, 3);

            return false;
        }

        $count = $this->normalizeCount($count);
        $cacheKey = sprintf('nbp_table_%s_last_%d', $tableType, $count);

        $cached = $this->cacheService->get($cacheKey);

        if ($cached !== false) {
            return $cached;
        }

        $url = $this->buildUrl(sprintf('/tables/%s/last/%d/', $tableType, $count));
        $response = $this->makeRequest($url);

        if ($response === false) {
            return false;
        }

        $this->cacheService->set($cacheKey, $response);

        return $response;
    }

    /**
     * Get specific currency rates
     *
     * @param string $currencyCode Currency code (e.g., EUR, USD)
     * @param string $tableType Table type: A, B, or C
     * @param int $count Number of last rates to fetch
     *
     * @return array|false
     */
    public function getCurrencyRates(string $currencyCode, string $tableType = 'A', int $count = 30)
    {
        $currencyCode = strtoupper($currencyCode);
        $tableType = strtoupper($tableType);

        if (!$this->isValidTableType($tableType)) {
            PrestaShopLogger::addLog('Invalid table type: ' . $tableType, 3);

            return false;
        }

        $count = $this->normalizeCount($count);
        $cacheKey = sprintf('nbp_currency_%s_%s_last_%d', $currencyCode, $tableType, $count);

        $cached = $this->cacheService->get($cacheKey);

        if ($cached !== false) {
            return $cached;
        }

        $url = $this->buildUrl(sprintf('/rates/%s/%s/last/%d/', $tableType, $currencyCode, $count));
        $response = $this->makeRequest($url);

        if ($response === false) {
            return false;
        }

        $this->cacheService->set($cacheKey, $response);

        return $response;
    }

    /**
     * Get current exchange rate for specific currency
     *
     * @param string $currencyCode Currency code
     * @param string $tableType Table type
     *
     * @return array|false
     */
    public function getCurrentRate(string $currencyCode, string $tableType = 'A')
    {
        $currencyCode = strtoupper($currencyCode);
        $tableType = strtoupper($tableType);

        $cacheKey = sprintf('nbp_current_%s_%s', $currencyCode, $tableType);

        $cached = $this->cacheService->get($cacheKey);

        if ($cached !== false) {
            return $cached;
        }

        $url = $this->buildUrl(sprintf('/rates/%s/%s/', $tableType, $currencyCode));
        $response = $this->makeRequest($url);

        if ($response === false) {
            return false;
        }

        $this->cacheService->set($cacheKey, $response);

        return $response;
    }

    /**
     * Get available currencies for table type
     *
     * @param string $tableType Table type
     *
     * @return array
     */
    public function getAvailableCurrencies(string $tableType = 'A'): array
    {
        $table = $this->getTable($tableType, 1);

        if (!$table || !isset($table[0]['rates'])) {
            return [];
        }

        $currencies = [];
        
        foreach ($table[0]['rates'] as $rate) {
            $currencies[] = [
                'code' => $rate['code'],
                'name' => $rate['currency'],
            ];
        }

        return $currencies;
    }

    /**
     * Make HTTP request to NBP API with retry logic
     *
     * @param string $url API endpoint URL
     *
     * @return array|false
     */
    private function makeRequest(string $url)
    {
        $retries = 0;

        while ($retries < self::MAX_RETRIES) {
            try {
                return $this->httpClient->get($url, [], self::TIMEOUT);
            } catch (Exception $e) {
                ++$retries;

                PrestaShopLogger::addLog(
                    sprintf('NBP API request failed (attempt %d): %s', $retries, $e->getMessage()),
                    3
                );

                if ($retries >= self::MAX_RETRIES) {
                    return false;
                }

                sleep((int) pow(2, $retries));
            }
        }

        return false;
    }

    /**
     * Build full API URL
     *
     * @param string $path URL path
     *
     * @return string
     */
    private function buildUrl(string $path): string
    {
        return self::API_BASE_URL . $path . '?format=json';
    }

    /**
     * Validate table type
     *
     * @param string $tableType
     *
     * @return bool
     */
    private function isValidTableType(string $tableType): bool
    {
        return in_array($tableType, ['A', 'B', 'C'], true);
    }

    /**
     * Normalize count to valid range
     *
     * @param int $count
     *
     * @return int
     */
    private function normalizeCount(int $count): int
    {
        return min(self::MAX_RATES_COUNT, max(1, $count));
    }
}
