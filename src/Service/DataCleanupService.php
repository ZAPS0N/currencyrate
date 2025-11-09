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

use CurrencyRateRepository;
use Exception;
use PrestaShopLogger;

/**
 * Service for cleaning up old currency rate data
 */
class DataCleanupService
{
    private const DEFAULT_RETENTION_DAYS = 30;

    /**
     * @var CurrencyRateRepository
     */
    private $repository;

    /**
     * @var int Number of days to retain data
     */
    private $retentionDays;

    /**
     * @param CurrencyRateRepository $repository
     * @param int $retentionDays Number of days to keep data
     */
    public function __construct(CurrencyRateRepository $repository, int $retentionDays = self::DEFAULT_RETENTION_DAYS)
    {
        $this->repository = $repository;
        $this->retentionDays = $retentionDays;
    }

    /**
     * Clean up old data (older than retention period)
     *
     * @return bool True on success, false on failure
     */
    public function cleanupOldData(): bool
    {
        try {
            $cutoffDate = $this->calculateCutoffDate();

            $deleted = $this->repository->deleteOlderThan($cutoffDate);

            if ($deleted) {
                $this->logSuccess($cutoffDate);
            }

            return $deleted;
        } catch (Exception $e) {
            $this->logError($e);

            return false;
        }
    }

    /**
     * Calculate cutoff date for data retention
     *
     * @return string Date in Y-m-d format
     */
    private function calculateCutoffDate(): string
    {
        return date('Y-m-d', strtotime(sprintf('-%d days', $this->retentionDays)));
    }

    /**
     * Log successful cleanup
     *
     * @param string $cutoffDate Cutoff date
     *
     * @return void
     */
    private function logSuccess(string $cutoffDate): void
    {
        PrestaShopLogger::addLog(
            sprintf('Old currency rates cleaned up (before %s)', $cutoffDate),
            1
        );
    }

    /**
     * Log cleanup error
     *
     * @param Exception $exception Exception instance
     *
     * @return void
     */
    private function logError(Exception $exception): void
    {
        PrestaShopLogger::addLog(
            sprintf('Error cleaning up old data: %s', $exception->getMessage()),
            3
        );
    }

    /**
     * Set retention days
     *
     * @param int $days Number of days
     *
     * @return void
     */
    public function setRetentionDays(int $days): void
    {
        $this->retentionDays = max(1, $days);
    }

    /**
     * Get retention days
     *
     * @return int Number of days
     */
    public function getRetentionDays(): int
    {
        return $this->retentionDays;
    }
}
