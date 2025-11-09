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

use Configuration;
use Context;
use CurrencyRate\Config\ModuleConfig;
use Exception;
use PrestaShopLogger;

/**
 * Service for scheduled currency rate updates (cron jobs)
 */
class CronService
{
    /**
     * @var NbpApiService
     */
    private $nbpApiService;

    /**
     * @var RateProcessor
     */
    private $rateProcessor;

    /**
     * @var DataCleanupService
     */
    private $cleanupService;

    /**
     * @var CacheService
     */
    private $cacheService;

    /**
     * @param NbpApiService $nbpApiService
     * @param RateProcessor $rateProcessor
     * @param DataCleanupService $cleanupService
     * @param CacheService $cacheService
     */
    public function __construct(
        NbpApiService $nbpApiService,
        RateProcessor $rateProcessor,
        DataCleanupService $cleanupService,
        CacheService $cacheService
    ) {
        $this->nbpApiService = $nbpApiService;
        $this->rateProcessor = $rateProcessor;
        $this->cleanupService = $cleanupService;
        $this->cacheService = $cacheService;
    }

    /**
     * Update currency rates from NBP API
     *
     * @return array Status array with success/error info
     */
    public function updateRates(): array
    {
        $result = $this->initializeResult();

        try {
            $config = $this->getConfiguration();
            $result['updated_count'] = $this->updateAllCurrencies($config, $result);

            if ($config['auto_cleanup']) {
                $this->cleanupService->cleanupOldData();
            }

            $this->cacheService->clearAll();
            $this->updateLastUpdateTimestamp();

            $result['success'] = true;
            $result['message'] = $this->buildSuccessMessage($result['errors']);

            $this->logSuccess($result['updated_count']);
        } catch (Exception $e) {
            $result['message'] = sprintf('Fatal error: %s', $e->getMessage());
            $this->logFatalError($e);
        }

        return $result;
    }

    /**
     * Get cron URL for external execution
     *
     * @return string Cron URL
     */
    public function getCronUrl(): string
    {
        $context = Context::getContext();

        return $context->link->getModuleLink('currencyrate', 'cron', [], true);
    }

    /**
     * Initialize result array
     *
     * @return array
     */
    private function initializeResult(): array
    {
        return [
            'success' => false,
            'message' => '',
            'updated_count' => 0,
            'errors' => [],
        ];
    }

    /**
     * Get configuration from PrestaShop
     *
     * @return array
     */
    private function getConfiguration(): array
    {
        $enabledCurrenciesStr = Configuration::get(ModuleConfig::CONFIG_KEY_ENABLED_CURRENCIES);
        $enabledCurrencies = !empty($enabledCurrenciesStr) ? explode(',', $enabledCurrenciesStr) : [];

        return [
            'currencies' => $enabledCurrencies,
            'table_type' => Configuration::get(ModuleConfig::CONFIG_KEY_TABLE_TYPE),
            'auto_cleanup' => (bool) Configuration::get(ModuleConfig::CONFIG_KEY_AUTO_CLEANUP),
        ];
    }

    /**
     * Update all enabled currencies
     *
     * @param array $config Configuration array
     * @param array &$result Result array to collect errors
     *
     * @return int Total number of updated rates
     */
    private function updateAllCurrencies(array $config, array &$result): int
    {
        $totalUpdated = 0;

        foreach ($config['currencies'] as $currencyCode) {
            $currencyCode = trim($currencyCode);

            if (empty($currencyCode)) {
                continue;
            }

            try {
                $updatedCount = $this->updateSingleCurrency($currencyCode, $config['table_type']);
                $totalUpdated += $updatedCount;

                if ($updatedCount === 0) {
                    $result['errors'][] = sprintf('No data available for %s', $currencyCode);
                }
            } catch (Exception $e) {
                $result['errors'][] = sprintf('Error updating %s: %s', $currencyCode, $e->getMessage());
                $this->logCurrencyError($currencyCode, $e);
            }
        }

        return $totalUpdated;
    }

    /**
     * Update single currency from API
     *
     * @param string $currencyCode Currency code
     * @param string $tableType Table type
     *
     * @return int Number of saved rates
     */
    private function updateSingleCurrency(string $currencyCode, string $tableType): int
    {
        // Fetch last 30 rates from API
        $apiResponse = $this->nbpApiService->getCurrencyRates($currencyCode, $tableType, 30);

        if ($apiResponse === false) {
            return 0;
        }

        return $this->rateProcessor->processAndSaveRates($apiResponse, $tableType);
    }

    /**
     * Update last update timestamp in configuration
     *
     * @return void
     */
    private function updateLastUpdateTimestamp(): void
    {
        Configuration::updateValue(
            ModuleConfig::CONFIG_KEY_LAST_UPDATE,
            date('Y-m-d H:i:s')
        );
    }

    /**
     * Build success message based on errors
     *
     * @param array $errors Array of error messages
     *
     * @return string Success message
     */
    private function buildSuccessMessage(array $errors): string
    {
        if (count($errors) > 0) {
            return 'Updated with some errors';
        }

        return 'All rates updated successfully';
    }

    /**
     * Log successful update
     *
     * @param int $count Number of updated rates
     *
     * @return void
     */
    private function logSuccess(int $count): void
    {
        PrestaShopLogger::addLog(
            sprintf('Currency rates updated: %d rates saved', $count),
            1
        );
    }

    /**
     * Log currency-specific error
     *
     * @param string $currencyCode Currency code
     * @param Exception $exception Exception instance
     *
     * @return void
     */
    private function logCurrencyError(string $currencyCode, Exception $exception): void
    {
        PrestaShopLogger::addLog(
            sprintf('CronService error for %s: %s', $currencyCode, $exception->getMessage()),
            3
        );
    }

    /**
     * Log fatal error
     *
     * @param Exception $exception Exception instance
     *
     * @return void
     */
    private function logFatalError(Exception $exception): void
    {
        PrestaShopLogger::addLog(
            sprintf('CronService fatal error: %s', $exception->getMessage()),
            4
        );
    }
}
